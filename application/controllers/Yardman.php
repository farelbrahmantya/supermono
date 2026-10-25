<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Yardman extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');

        if (!in_array($this->session->userdata('role'), array('YARDMAN', 'CONTROL_TOWER'))) {
            redirect('auth');
        }
    }

    public function index() {
        $this->load->view('yardman/scanner_view');
    }

    // API Pemrosesan Scan Barcode 3 Tahapan + Anti Double Scan
    public function process_scan() {
        $queue_number = trim($this->input->post('queue_number'));
        $zone_code    = trim($this->input->post('zone_code')); // Opsional untuk lokasi blok
        $user_id      = $this->session->userdata('user_id');

        $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();

        if (!$queue) {
            echo json_encode(array('status' => 'error', 'message' => 'Nomor Antrean ' . $queue_number . ' tidak ditemukan!'));
            return;
        }

        $now = date('Y-m-d H:i:s');

        // TAHAP 1: GATE IN SCAN (Memulai SLA)
        if ($queue->status == 'WAITING_GATE') {
            $update = array(
                'status'     => 'IN_YARD',
                'gate_in_at' => $now,
                'gate_in_by' => $user_id
            );
            $this->db->where('id', $queue->id)->update('queue_transactions', $update);
            echo json_encode(array(
                'status'  => 'success', 
                'message' => "TAHAP 1 (GATE IN) BERHASIL!\nTruk {$queue->license_plate} resmi masuk area depo. SLA Dimulai."
            ));

        // TAHAP 2: YARD AREA SCAN (Aktivitas Bongkar/Muat di Blok Yard)
        } elseif ($queue->status == 'IN_YARD') {
            $update = array(
                'status'       => 'AT_YARD',
                'yard_scan_at' => $now,
                'yard_scan_by' => $user_id
            );
            $this->db->where('id', $queue->id)->update('queue_transactions', $update);
            echo json_encode(array(
                'status'  => 'success', 
                'message' => "TAHAP 2 (SERVIS YARD) BERHASIL!\nTruk {$queue->license_plate} terkonfirmasi melayani di Blok Yard."
            ));

        // TAHAP 3: GATE OUT SCAN (Penyelesaian Servis & Stop TAT)
        } elseif ($queue->status == 'AT_YARD') {
            $update = array(
                'status'      => 'COMPLETED',
                'gate_out_at' => $now,
                'gate_out_by' => $user_id
            );
            $this->db->where('id', $queue->id)->update('queue_transactions', $update);
            echo json_encode(array(
                'status'  => 'success', 
                'message' => "TAHAP 3 (GATE OUT) BERHASIL!\nServis Truk {$queue->license_plate} Selesai & Siap Keluar Depo."
            ));

        } else {
            echo json_encode(array(
                'status'  => 'warning', 
                'message' => "Antrean {$queue->queue_number} sudah berstatus COMPLETED."
            ));
        }
    }
}