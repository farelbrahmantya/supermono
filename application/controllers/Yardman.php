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

    // Scan QR supir oleh Yardman (2x):
    //   scan 1: IN_YARD   -> IN_SURVEY (truk masuk area survey)
    //   scan 2: IN_SURVEY -> AT_YARD   (survey selesai, blok penempatan wajib dipilih)
    // Gate In & Gate Out hanya boleh dilakukan Security di halaman Gate.

    // Baca data truk dari QR tanpa mengubah status, untuk layar konfirmasi Yardman
    public function lookup() {
        $queue = $this->_find_queue($this->input->post('queue_number'));
        $check = $this->_check_step($queue);
        if (isset($check['status'])) {
            $this->_json($check);
            return;
        }

        $this->_json(array(
            'status' => 'success',
            'step'   => $check['step'],
            'queue'  => array(
                'queue_number'   => $queue->queue_number,
                'license_plate'  => $queue->license_plate,
                'container_no'   => $queue->container_no,
                'principal'      => $queue->principal,
                'container_size' => $queue->container_size,
                'customer'       => $queue->customer,
                'stage_minutes'  => (int) floor((time() - strtotime($check['step'] === 1 ? $queue->gate_in_at : $queue->survey_start_at)) / 60)
            )
        ));
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

    private function _find_queue($input) {
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
            return array('step' => 1);
        }
        if ($queue->status == 'IN_SURVEY') {
            return array('step' => 2);
        }
        if ($queue->status == 'AT_YARD') {
            return array('status' => 'warning', 'message' => "Survey antrean {$queue->queue_number} sudah selesai pada {$queue->yard_scan_at}.\nSelanjutnya scan di Gate Out.");
        }
        return array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah berstatus {$queue->status}.");
    }
}
