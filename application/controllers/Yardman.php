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
        $data['zones'] = $this->db->order_by('zone_code', 'ASC')->get('yard_zones')->result();
        $this->load->view('yardman/scanner_view', $data);
    }

    // Daftar truk yang sudah Gate In tapi belum diverifikasi Yardman
    public function pending_json() {
        $this->_json(array('yard' => $this->_pending_queues('IN_YARD')));
    }

    // API Pemrosesan Scan Barcode Yardman (IN_YARD -> AT_YARD) + Anti Double Scan
    // Gate In & Gate Out hanya boleh dilakukan Security di halaman Gate.
    public function process_scan() {
        $queue_number = strtoupper(trim((string) $this->input->post('queue_number')));
        $zone_code    = strtoupper(trim((string) $this->input->post('zone_code')));
        $user_id      = $this->session->userdata('user_id');

        $queue = $this->db->get_where('queue_transactions', array('queue_number' => $queue_number))->row();

        if (!$queue) {
            $this->_json(array('status' => 'error', 'message' => 'Nomor Antrean ' . $queue_number . ' tidak ditemukan!'));
            return;
        }

        if ($queue->status == 'WAITING_GATE') {
            $this->_json(array(
                'status'  => 'warning',
                'message' => "Truk {$queue->license_plate} BELUM Gate In.\nTruk wajib discan Security di Gate In terlebih dahulu."
            ));
            return;
        }

        if ($queue->status == 'AT_YARD') {
            $this->_json(array(
                'status'  => 'warning',
                'message' => "Antrean {$queue->queue_number} sudah diverifikasi Yardman pada {$queue->yard_scan_at}.\nSelanjutnya scan di Gate Out."
            ));
            return;
        }

        if ($queue->status != 'IN_YARD') {
            $this->_json(array(
                'status'  => 'warning',
                'message' => "Antrean {$queue->queue_number} sudah berstatus {$queue->status}."
            ));
            return;
        }

        // Blok yard wajib dipilih (jika master yard_zones terisi) agar heatmap kepadatan akurat
        $zone = NULL;
        if ($zone_code !== '') {
            $zone = $this->db->get_where('yard_zones', array('zone_code' => $zone_code))->row();
            if (!$zone) {
                $this->_json(array('status' => 'error', 'message' => "Blok yard {$zone_code} tidak terdaftar!"));
                return;
            }
        } elseif ($this->db->count_all('yard_zones') > 0) {
            $this->_json(array('status' => 'error', 'message' => 'Pilih Blok Yard terlebih dahulu sebelum scan!'));
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

        if (!$this->_transition($queue->id, 'IN_YARD', $update)) {
            $this->_json(array('status' => 'warning', 'message' => "Antrean {$queue->queue_number} sudah diproses petugas lain."));
            return;
        }

        $zone_text = $zone ? " di Blok {$zone->zone_code}" : '';
        $this->_json(array(
            'status'  => 'success',
            'message' => "VERIFIKASI YARD BERHASIL!\nTruk {$queue->license_plate} terkonfirmasi melayani{$zone_text}."
        ));
    }
}
