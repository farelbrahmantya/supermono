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

    // Process Scan khusus Barcode Scanner Gerbang Security
    public function process_scan() {
        $queue_number = strtoupper(trim((string) $this->input->post('queue_number')));
        $user_id      = $this->session->userdata('user_id');
        $user_role    = $this->session->userdata('role');

        $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();

        if (!$queue) {
            $this->_json(array('status' => 'error', 'message' => 'Nomor Antrean ' . $queue_number . ' tidak ditemukan!'));
            return;
        }

        $now = $this->_now();
        $can_gate_in  = in_array($user_role, array('GATE_IN', 'CONTROL_TOWER'), TRUE);
        $can_gate_out = in_array($user_role, array('GATE_OUT', 'CONTROL_TOWER'), TRUE);

        // JIKA PETUGAS GATE IN
        if ($can_gate_in && $queue->status == 'WAITING_GATE') {
            if ($this->_is_expired($queue)) {
                $this->_json(array(
                    'status'  => 'error',
                    'message' => "Antrean {$queue->queue_number} sudah kedaluwarsa (lebih dari " . self::QUEUE_EXPIRY_HOURS . " jam).\nSupir wajib daftar ulang."
                ));
                return;
            }

            $updated = $this->_transition($queue->id, 'WAITING_GATE', array(
                'status'     => 'IN_YARD',
                'gate_in_at' => $now,
                'gate_in_by' => $user_id
            ));
            $this->_json($updated
                ? array('status' => 'success', 'message' => "GATE IN SUCCESS!\nTruk {$queue->license_plate} Silakan Masuk Yard.")
                : array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        // JIKA PETUGAS GATE OUT: hanya truk yang sudah diverifikasi Yardman (AT_YARD)
        if ($can_gate_out && $queue->status == 'AT_YARD') {
            $updated = $this->_transition($queue->id, 'AT_YARD', array(
                'status'      => 'COMPLETED',
                'gate_out_at' => $now,
                'gate_out_by' => $user_id
            ));
            $this->_json($updated
                ? array('status' => 'success', 'message' => "GATE OUT SUCCESS!\nTruk {$queue->license_plate} Servis Selesai.")
                : array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        if ($can_gate_out && $queue->status == 'IN_YARD') {
            $this->_json(array(
                'status'  => 'warning',
                'message' => "Truk {$queue->license_plate} BELUM diverifikasi Yardman.\nArahkan truk ke Yardman sebelum Gate Out."
            ));
            return;
        }

        $this->_json(array(
            'status'  => 'warning',
            'message' => "Antrean {$queue->queue_number} berstatus: {$queue->status}. Tidak sesuai dengan wewenang Gate."
        ));
    }
}
