<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Yardman extends MY_Controller {

    protected $allowed_roles = array('YARDMAN', 'CONTROL_TOWER');

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index() {
        $data['zones'] = $this->db->order_by('depo', 'ASC')->order_by('zone_code', 'ASC')->get('yard_zones')->result();
        $this->load->view('yardman/scanner_view', $data);
    }

    // Truk yang menunggu masuk survey (sudah Gate In) dan yang sedang survey
    public function pending_json() {
        $this->_json(array(
            'waiting_survey' => $this->_pending_queues('IN_YARD'),
            'in_survey'      => $this->_pending_queues('IN_SURVEY')
        ));
    }

    // Scan QR supir / kitir oleh Yardman:
    //   Repo In & Import (Z/A) 2x scan:
    //     scan 1: IN_YARD   -> IN_SURVEY (truk masuk area survey)
    //     scan 2: IN_SURVEY -> AT_YARD   (survey selesai, blok penempatan wajib dipilih)
    //   Export & Repo Out (D/E) tanpa survey, 1x submit muat: IN_YARD -> AT_YARD
    //     Export  : Yardman input nomor container yang dipilih (dicek ke new_sby)
    //     Repo Out: container sudah diplot di kitir, Yardman cukup konfirmasi + blok
    // Gate In & Gate Out hanya boleh dilakukan Security di halaman Gate.

    // Baca data truk dari QR tanpa mengubah status, untuk layar konfirmasi Yardman
    public function lookup() {
        $queue = $this->_find_queue($this->input->post('queue_number'));
        $check = $this->_check_step($queue);
        if (isset($check['status'])) {
            $this->_json($check);
            return;
        }

        $stage = $this->_current_stage($queue);
        $this->_json(array(
            'status' => 'success',
            'step'   => $check['step'],
            'queue'  => array(
                'activity'       => $queue->activity,
                'activity_label' => $this->_activity_label($queue->activity),
                // Export (atau Repo Out yang belum diplot): Yardman wajib input nomor container
                'need_container' => $check['step'] === 3 && ($queue->activity === 'D' || !$queue->container_no),
                'queue_number'   => $queue->queue_number,
                'license_plate'  => $queue->license_plate,
                'container_no'   => $queue->container_no,
                'principal'      => $queue->principal,
                'container_size' => $queue->container_size,
                'customer'       => $queue->customer,
                'stage_minutes'  => (int) floor((time() - strtotime($check['step'] === 2 ? $queue->survey_start_at : $queue->gate_in_at)) / 60),
                'stage_target'   => $stage ? $stage[3] : self::STAGE_KPI_MINUTES
            )
        ));
    }

    // Cek nomor container pilihan Yardman (Export) ke new_sby sebelum submit
    public function check_container() {
        $queue = $this->_find_queue($this->input->post('queue_number'));
        $check = $this->_check_step($queue);
        if (isset($check['status'])) {
            $this->_json($check);
            return;
        }
        if ($check['step'] !== 3) {
            $this->_json(array('status' => 'error', 'message' => 'Antrean ini bukan aktivitas muat (Export / Repo Out).'));
            return;
        }

        $container = $this->_validate_container($queue, $this->input->post('container_no'));
        $this->_json(is_object($container)
            ? array('status' => 'success', 'container' => $container)
            : $container);
    }

    public function process_scan() {
        $queue     = $this->_find_queue($this->input->post('queue_number'));
        $zone_code = strtoupper(trim((string) $this->input->post('zone_code')));
        $user_id   = $this->session->userdata('user_id');

        $check = $this->_check_step($queue);
        if (isset($check['status'])) {
            $this->_json($check);
            return;
        }

        // SCAN 1: truk masuk area survey
        if ($check['step'] === 1) {
            $updated = $this->_transition($queue->id, 'IN_YARD', array(
                'status'          => 'IN_SURVEY',
                'survey_start_at' => $this->_now(),
                'survey_start_by' => $user_id
            ));
            $this->_json($updated
                ? array('status' => 'success', 'message' => "MASUK AREA SURVEY!\nContainer {$queue->container_no} ({$queue->license_plate}).\nScan lagi setelah survey selesai.")
                : array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        // SUBMIT MUAT (Export / Repo Out): langsung AT_YARD tanpa survey
        if ($check['step'] === 3) {
            $this->_process_muat($queue, $zone_code, $user_id);
            return;
        }

        // SCAN 2: survey selesai, blok penempatan ditentukan Yardman
        $zone = NULL;
        if ($zone_code !== '') {
            $zone = $this->db->get_where('yard_zones', array('zone_code' => $zone_code))->row();
            if (!$zone) {
                $this->_json(array('status' => 'error', 'message' => "Blok yard {$zone_code} tidak terdaftar!"));
                return;
            }
        } elseif ($this->db->count_all('yard_zones') > 0) {
            $this->_json(array('status' => 'error', 'message' => 'Survey selesai: pilih Depo & Blok penempatan terlebih dahulu!'));
            return;
        }

        $update = array(
            'status'       => 'AT_YARD',
            'yard_scan_at' => $this->_now(),
            'yard_scan_by' => $user_id
        );
        if ($zone) {
            $update['current_zone_id'] = $zone->id;
        }

        if (!$this->_transition($queue->id, 'IN_SURVEY', $update)) {
            $this->_json(array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        $zone_text = $zone ? "\nPenempatan: {$zone->depo} - Blok {$zone->zone_code}." : '';
        $this->_json(array(
            'status'  => 'success',
            'message' => "SURVEY SELESAI!\nContainer {$queue->container_no} ({$queue->license_plate}).{$zone_text}"
        ));
    }

    private function _process_muat($queue, $zone_code, $user_id) {
        $zone = $zone_code !== '' ? $this->db->get_where('yard_zones', array('zone_code' => $zone_code))->row() : NULL;
        if ($zone_code !== '' && !$zone) {
            $this->_json(array('status' => 'error', 'message' => "Blok yard {$zone_code} tidak terdaftar!"));
            return;
        }
        if (!$zone && $this->db->count_all('yard_zones') > 0) {
            $this->_json(array('status' => 'error', 'message' => 'Pilih Depo & Blok pengambilan container terlebih dahulu!'));
            return;
        }

        $update = array(
            'status'       => 'AT_YARD',
            'yard_scan_at' => $this->_now(),
            'yard_scan_by' => $user_id
        );
        if ($zone) {
            $update['current_zone_id'] = $zone->id;
        }

        // Export: nomor container dipilih Yardman di lapangan; Repo Out: container yang sudah diplot.
        // Keduanya wajib cocok dengan new_sby (status, kondisi, principal, tipe)
        $picked    = ($queue->activity === 'D' || !$queue->container_no) ? $this->input->post('container_no') : $queue->container_no;
        $container = $this->_validate_container($queue, $picked);
        if (!is_object($container)) {
            $this->_json($container);
            return;
        }
        $update['container_no']   = $container->container_no;
        $update['container_size'] = $container->size;
        $queue->container_no      = $container->container_no;

        if (!$this->_transition($queue->id, 'IN_YARD', $update)) {
            $this->_json(array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        $zone_text = $zone ? "\nAmbil di: {$zone->depo} - Blok {$zone->zone_code}." : '';
        $this->_json(array(
            'status'  => 'success',
            'message' => "MUAT CONTAINER SELESAI!\n" . $this->_activity_label($queue->activity) . " - Container {$queue->container_no} ({$queue->license_plate}).{$zone_text}\nSelanjutnya scan kitir di Gate Out."
        ));
    }

    // Container yang dimuat: terdaftar di new_sby, status & kondisi boleh dimuat (Export CO+AC,
    // Repo Out CO/WA + AC/DN), principal & tipe sama dengan kitir, dan tidak sedang dipakai truk lain. Mengembalikan data container atau array respon error.
    private function _validate_container($queue, $container_no) {
        $container_no = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $container_no));
        if (!preg_match('/^[A-Z]{4}\d{7}$/', $container_no)) {
            return array('status' => 'error', 'message' => 'Nomor container harus 4 huruf + 7 angka (contoh: ONEU1077596).');
        }

        $this->load->model('kitir_model');
        $container = $this->kitir_model->find_container($container_no);
        if (!$container) {
            return array('status' => 'error', 'message' => "Container {$container_no} tidak terdaftar di database depo.");
        }
        if (!$this->kitir_model->same_principal($container->principal, $queue->principal)) {
            return array('status' => 'error', 'message' => "Principal tidak sesuai!\nContainer {$container_no} milik " . ($container->principal ? $container->principal : '(kosong)') . ", kitir untuk {$queue->principal}.");
        }
        if ($queue->container_size !== NULL && $queue->container_size !== '' && $container->size !== '' && $container->size !== $queue->container_size) {
            return array('status' => 'error', 'message' => "Tipe container tidak sesuai!\nContainer {$container_no} {$container->size}, kitir meminta {$queue->container_size}.");
        }
        $problem = $this->kitir_model->load_problem($container, $queue->activity);
        if ($problem !== NULL) {
            return array('status' => 'error', 'message' => "Container {$container_no} belum bisa dimuat:\n{$problem}.");
        }

        $used = $this->db->where('container_no', $container_no)
                         ->where('id !=', $queue->id)
                         ->where_in('status', $this->in_depo_statuses)
                         ->get('queue_transactions', 1)->row();
        if ($used) {
            return array('status' => 'warning', 'message' => "Container {$container_no} sedang dipakai antrean #{$used->queue_number} ({$used->license_plate}).");
        }
        return $container;
    }

    // Antrean dari QR supir atau barcode kitir (Yardman boleh input nomor kitir)
    private function _find_queue($input) {
        $this->load->model('kitir_model');
        if ($this->kitir_model->parse_barcode($input) !== FALSE) {
            $kitir = $this->kitir_model->find_by_barcode($input);
            $queue = $kitir ? $this->db->where('cp_id', $kitir->cp_id)
                                       ->where_in('activity', $this->_activity_group($kitir->activity))
                                       ->where_in('status', $this->in_depo_statuses)
                                       ->order_by('id', 'DESC')
                                       ->get('queue_transactions', 1)->row() : NULL;
            return $queue ? $queue : 'kitir ' . strtoupper(trim((string) $input)) . ' (belum Gate In)';
        }

        $queue_number = $this->_extract_queue_number($input);
        $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();
        return $queue ? $queue : $queue_number;
    }

    // Langkah Yardman untuk antrean ini (step 1 / 2), atau respon peringatan jika tidak bisa diproses
    private function _check_step($queue) {
        if (!is_object($queue)) {
            return array('status' => 'error', 'message' => 'Nomor Antrean ' . $queue . ' tidak ditemukan!');
        }
        if ($queue->status == 'WAITING_GATE') {
            return array('status' => 'warning', 'message' => "Truk {$queue->license_plate} BELUM Gate In.\nTruk wajib discan Security di Gate In terlebih dahulu.");
        }
        if ($queue->status == 'IN_YARD') {
            return array('step' => $this->_is_muat($queue->activity) ? 3 : 1);
        }
        if ($queue->status == 'IN_SURVEY') {
            return array('step' => 2);
        }
        if ($queue->status == 'AT_YARD') {
            return array('status' => 'warning', 'message' => ($this->_is_muat($queue->activity) ? 'Muat container' : 'Survey') . " antrean {$queue->queue_number} sudah selesai pada {$queue->yard_scan_at}.\nSelanjutnya scan di Gate Out.");
        }
        return array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah berstatus {$queue->status}.");
    }
}
