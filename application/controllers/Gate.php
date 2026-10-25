<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gate extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        // Khusus Gate In, Gate Out, dan Control Tower
        if (!in_array($this->session->userdata('role'), array('GATE_IN', 'GATE_OUT', 'CONTROL_TOWER'))) {
            redirect('auth');
        }
    }

    public function index() {
        $data['role'] = $this->session->userdata('role');
        $this->load->view('gate/terminal_view', $data);
    }

    // Process Scan khusus Barcode Scanner Gerbang Security
    public function process_scan() {
        $queue_number = trim($this->input->post('queue_number'));
        $user_id      = $this->session->userdata('user_id');
        $user_role    = $this->session->userdata('role');

        $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();

        if (!$queue) {
            echo json_encode(array('status' => 'error', 'message' => 'Nomor Antrean ' . $queue_number . ' tidak ditemukan!'));
            return;
        }

        $now = date('Y-m-d H:i:s');

        // JIKA PETUGAS GATE IN
        if ($user_role == 'GATE_IN' || $user_role == 'CONTROL_TOWER') {
            if ($queue->status == 'WAITING_GATE') {
                $update = array(
                    'status'     => 'IN_YARD',
                    'gate_in_at' => $now,
                    'gate_in_by' => $user_id
                );
                $this->db->where('id', $queue->id)->update('queue_transactions', $update);
                echo json_encode(array(
                    'status'  => 'success', 
                    'message' => "GATE IN SUCCESS!\nTruk {$queue->license_plate} Silakan Masuk Yard."
                ));
                return;
            }
        }

        // JIKA PETUGAS GATE OUT
        if ($user_role == 'GATE_OUT' || $user_role == 'CONTROL_TOWER') {
            if (in_array($queue->status, array('IN_YARD', 'AT_YARD'))) {
                $update = array(
                    'status'      => 'COMPLETED',
                    'gate_out_at' => $now,
                    'gate_out_by' => $user_id
                );
                $this->db->where('id', $queue->id)->update('queue_transactions', $update);
                echo json_encode(array(
                    'status'  => 'success', 
                    'message' => "GATE OUT SUCCESS!\nTruk {$queue->license_plate} Servis Selesai."
                ));
                return;
            }
        }

        echo json_encode(array(
            'status'  => 'warning', 
            'message' => "Antrean {$queue->queue_number} berstatus: {$queue->status}. Tidak sesuai dengan wewenang Gate."
        ));
    }
}