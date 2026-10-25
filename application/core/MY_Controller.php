<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller LASMONO: guard role, header no-cache, dan helper antrean bersama.
 */
class MY_Controller extends CI_Controller {

    // Antrean WAITING_GATE yang tidak di-Gate In dalam rentang ini dianggap kedaluwarsa
    const QUEUE_EXPIRY_HOURS = 12;

    // Role yang boleh mengakses controller turunan (kosong = publik)
    protected $allowed_roles = array();

    public function __construct() {
        parent::__construct();

        // Mencegah simpanan cache halaman oleh browser
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        if (!empty($this->allowed_roles) && !in_array($this->session->userdata('role'), $this->allowed_roles, TRUE)) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(401);
                header('Content-Type: application/json');
                echo json_encode(array('status' => 'unauthorized', 'message' => 'Sesi berakhir, silakan login ulang.'));
                exit;
            }
            redirect('auth');
        }
    }

    protected function _json($data) {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    protected function _now() {
        return date('Y-m-d H:i:s');
    }

    // Batas created_at untuk antrean WAITING_GATE yang masih berlaku
    protected function _expiry_cutoff() {
        return date('Y-m-d H:i:s', time() - self::QUEUE_EXPIRY_HOURS * 3600);
    }

    protected function _is_expired($queue) {
        return $queue->status === 'WAITING_GATE' && $queue->created_at < $this->_expiry_cutoff();
    }

    // Update status secara atomik: hanya berhasil jika status saat ini masih $from (anti double scan / race)
    protected function _transition($queue_id, $from, $data) {
        $this->db->where('id', $queue_id)
                 ->where_in('status', (array) $from)
                 ->update('queue_transactions', $data);
        return $this->db->affected_rows() === 1;
    }

    // Rata-rata menit per truk di Gate In selama 2 jam terakhir (NULL jika belum ada data)
    protected function _minutes_per_gate_in() {
        $window = 120;
        $count = $this->db->where('gate_in_at >=', date('Y-m-d H:i:s', time() - $window * 60))
                          ->count_all_results('queue_transactions');
        return $count > 0 ? $window / $count : NULL;
    }

    // Posisi antrean FIFO di antara antrean WAITING_GATE yang masih berlaku
    protected function _queue_position($queue) {
        return $this->db->where('status', 'WAITING_GATE')
                        ->where('created_at >=', $this->_expiry_cutoff())
                        ->where('id <=', $queue->id)
                        ->count_all_results('queue_transactions');
    }

    // Sisa detik SLA sejak Gate In (negatif = over SLA, NULL = belum Gate In)
    protected function _sla_remaining_seconds($queue) {
        if (empty($queue->gate_in_at) || !in_array($queue->status, array('IN_YARD', 'AT_YARD'), TRUE)) {
            return NULL;
        }
        return (int) $queue->sla_target_minutes * 60 - (time() - strtotime($queue->gate_in_at));
    }

    // Estimasi menit sampai dipanggil Gate In berdasarkan throughput 2 jam terakhir
    protected function _estimated_wait($position) {
        if ($position === NULL) {
            return NULL;
        }
        $rate = $this->_minutes_per_gate_in();
        return $rate === NULL ? NULL : (int) round(($position - 1) * $rate);
    }
}
