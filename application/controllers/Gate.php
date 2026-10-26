<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gate extends MY_Controller {

    // Khusus Gate In, Gate Out, dan Control Tower
    protected $allowed_roles = array('GATE_IN', 'GATE_OUT', 'CONTROL_TOWER');

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index() {
        $data['role'] = $this->session->userdata('role');
        $this->load->view('gate/terminal_view', $data);
    }

    // Daftar truk yang belum di-scan sesuai wewenang petugas gate
    public function pending_json() {
        $role = $this->session->userdata('role');
        $data = array();
        if (in_array($role, array('GATE_IN', 'CONTROL_TOWER'), TRUE)) {
            $data['gate_in'] = $this->_pending_queues('WAITING_GATE');
        }
        if (in_array($role, array('GATE_OUT', 'CONTROL_TOWER'), TRUE)) {
            $data['gate_out'] = $this->_pending_queues('AT_YARD');
        }
        $this->_json($data);
    }

    // GATE IN tahap 1: scan barcode kitir -> tampilkan data container + pilihan truk yang antre
    public function lookup_kitir() {
        if (!$this->_can_gate_in()) {
            $this->_json(array('status' => 'error', 'message' => 'Hanya petugas Gate In yang dapat memproses kitir.'));
            return;
        }

        $kitir = $this->_find_kitir($this->input->post('barcode'));
        if (!is_object($kitir)) {
            $this->_json($kitir);
            return;
        }

        $candidates = $this->_pending_queues('WAITING_GATE');
        if (empty($candidates)) {
            $this->_json(array('status' => 'warning', 'message' => "Kitir {$kitir->barcode} ditemukan, tetapi tidak ada truk yang sedang antre Gate In."));
            return;
        }

        // Saran truk: nopol di kitir (jika ada) yang cocok, selain itu antrean paling awal (FIFO)
        $suggested = $candidates[0]['queue_number'];
        $kitir_plate = str_replace(' ', '', strtoupper((string) $kitir->nopol));
        foreach ($candidates as $c) {
            if ($kitir_plate !== '' && str_replace(' ', '', $c['license_plate']) === $kitir_plate) {
                $suggested = $c['queue_number'];
                break;
            }
        }

        $kitir->activity_label = $this->_activity_label($kitir->activity);
        $kitir->muat           = $this->_is_muat($kitir->activity);

        $this->_json(array(
            'status'     => 'success',
            'kitir'      => $kitir,
            'candidates' => $candidates,
            'suggested'  => $suggested
        ));
    }

    // GATE IN tahap 2: konfirmasi kitir + truk -> status IN_YARD, data container disimpan ke antrean
    public function process_gate_in() {
        if (!$this->_can_gate_in()) {
            $this->_json(array('status' => 'error', 'message' => 'Hanya petugas Gate In yang dapat memproses Gate In.'));
            return;
        }

        $kitir = $this->_find_kitir($this->input->post('barcode'));
        if (!is_object($kitir)) {
            $this->_json($kitir);
            return;
        }

        $queue_number = $this->_extract_queue_number($this->input->post('queue_number'));
        $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();

        if (!$queue || $queue->status !== 'WAITING_GATE') {
            $this->_json(array('status' => 'warning', 'message' => "Antrean {$queue_number} tidak sedang menunggu Gate In."));
            return;
        }
        if ($this->_is_expired($queue)) {
            $this->_json(array(
                'status'  => 'error',
                'message' => "Antrean {$queue->queue_number} sudah kedaluwarsa (lebih dari " . self::QUEUE_EXPIRY_HOURS . " jam).\nSupir wajib daftar ulang."
            ));
            return;
        }

        $updated = $this->_transition($queue->id, 'WAITING_GATE', array(
            'status'         => 'IN_YARD',
            'gate_in_at'     => $this->_now(),
            'gate_in_by'     => $this->session->userdata('user_id'),
            'cp_id'              => $kitir->cp_id,
            'activity'           => $kitir->activity,
            'container_no'       => $kitir->container_no !== '' ? $kitir->container_no : NULL,
            'principal'          => $kitir->principal,
            'container_size'     => $kitir->size,
            'customer'           => $kitir->customer,
            'sla_target_minutes' => $this->_total_target($kitir->activity)
        ));

        $label = $this->_activity_label($kitir->activity);
        if ($kitir->activity === 'D') {
            $text = "Kitir Export {$kitir->barcode} ({$kitir->principal}).\nSilakan menuju Yardman untuk pemilihan container.";
        } elseif ($kitir->activity === 'E') {
            $text = "{$label} - Container {$kitir->container_no}.\nSilakan menuju Yardman untuk muat container.";
        } else {
            $text = "{$label} - Container {$kitir->container_no}.\nSilakan menuju area survey.";
        }
        $this->_json($updated
            ? array('status' => 'success', 'message' => "GATE IN SUCCESS!\nTruk {$queue->license_plate} - {$text}")
            : array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
    }

    // Gate Out: scan barcode kitir atau QR supir. Gate In wajib lewat barcode kitir.
    public function process_scan() {
        $input     = $this->input->post('queue_number');
        $user_role = $this->session->userdata('role');

        $this->load->model('kitir_model');
        if ($this->kitir_model->parse_barcode($input) !== FALSE) {
            $queue = $this->_queue_by_kitir($input);
            if (!is_object($queue)) {
                $this->_json($queue);
                return;
            }
        } else {
            $queue_number = $this->_extract_queue_number($input);
            $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();
            if (!$queue) {
                $this->_json(array('status' => 'error', 'message' => 'Nomor Antrean ' . $queue_number . ' tidak ditemukan!'));
                return;
            }
        }

        $can_gate_out = in_array($user_role, array('GATE_OUT', 'CONTROL_TOWER'), TRUE);

        if ($queue->status == 'WAITING_GATE') {
            $this->_json(array(
                'status'  => 'warning',
                'message' => "Truk {$queue->license_plate} belum Gate In.\nGate In dilakukan dengan scan BARCODE KITIR."
            ));
            return;
        }

        // JIKA PETUGAS GATE OUT: hanya truk yang sudah selesai survey Yardman (AT_YARD)
        if ($can_gate_out && $queue->status == 'AT_YARD') {
            $updated = $this->_transition($queue->id, 'AT_YARD', array(
                'status'      => 'COMPLETED',
                'gate_out_at' => $this->_now(),
                'gate_out_by' => $this->session->userdata('user_id')
            ));
            $this->_json($updated
                ? array('status' => 'success', 'message' => "GATE OUT SUCCESS!\nTruk {$queue->license_plate}" . ($queue->container_no ? " - Container {$queue->container_no}" : '') . ".\n" . $this->_activity_label($queue->activity) . ' selesai.')
                : array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        if ($can_gate_out && in_array($queue->status, array('IN_YARD', 'IN_SURVEY'), TRUE)) {
            $this->_json(array(
                'status'  => 'warning',
                'message' => $this->_is_muat($queue->activity)
                    ? "Truk {$queue->license_plate} BELUM muat container.\nArahkan truk ke Yardman sebelum Gate Out."
                    : "Truk {$queue->license_plate} BELUM selesai survey Yardman.\nArahkan truk ke Yardman sebelum Gate Out."
            ));
            return;
        }

        $this->_json(array(
            'status'  => 'warning',
            'message' => "Antrean {$queue->queue_number} berstatus: {$queue->status}. Tidak sesuai dengan wewenang Gate."
        ));
    }

    private function _can_gate_in() {
        return in_array($this->session->userdata('role'), array('GATE_IN', 'CONTROL_TOWER'), TRUE);
    }

    // Data kitir yang valid & belum dipakai antrean aktif, atau array respon error
    private function _find_kitir($barcode) {
        $this->load->model('kitir_model');

        if ($this->kitir_model->parse_barcode($barcode) === FALSE) {
            return array('status' => 'error', 'message' => 'Barcode ' . strtoupper(trim((string) $barcode)) . ' bukan barcode kitir (contoh: Z02155544, A02155830, D1578461, E1577775).');
        }

        $kitir = $this->kitir_model->find_by_barcode($barcode);
        if (!$kitir) {
            return array('status' => 'error', 'message' => 'Data kitir ' . strtoupper(trim((string) $barcode)) . ' tidak ditemukan di database depo.');
        }

        $used = $this->db->where('cp_id', $kitir->cp_id)
                     ->where_in('activity', $this->_activity_group($kitir->activity))
                         ->where_not_in('status', array('COMPLETED', 'CANCELLED'))
                         ->get('queue_transactions')->row();
        if ($used) {
            return array('status' => 'warning', 'message' => "Kitir {$kitir->barcode} sudah dipakai antrean #{$used->queue_number} ({$used->license_plate}).");
        }

        return $kitir;
    }

    // Antrean aktif yang memakai kitir ini (untuk Gate Out scan kitir), atau array respon error
    private function _queue_by_kitir($barcode) {
        $kitir = $this->kitir_model->find_by_barcode($barcode);
        if (!$kitir) {
            return array('status' => 'error', 'message' => 'Data kitir ' . strtoupper(trim((string) $barcode)) . ' tidak ditemukan di database depo.');
        }
        $queue = $this->db->where('cp_id', $kitir->cp_id)
                      ->where_in('activity', $this->_activity_group($kitir->activity))
                          ->where_in('status', $this->in_depo_statuses)
                          ->order_by('id', 'DESC')
                          ->get('queue_transactions', 1)->row();
        if (!$queue) {
            return array('status' => 'warning', 'message' => "Kitir {$kitir->barcode} tidak sedang dipakai truk di dalam depo.");
        }
        return $queue;
    }
}
