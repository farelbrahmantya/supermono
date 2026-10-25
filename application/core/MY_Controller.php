<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller LASMONO: guard role, header no-cache, dan helper antrean bersama.
 */
class MY_Controller extends CI_Controller {

    // Antrean WAITING_GATE yang tidak di-Gate In dalam rentang ini dianggap kedaluwarsa
    const QUEUE_EXPIRY_HOURS = 12;

    // Target KPI per tahap proses di dalam depo (menit)
    const STAGE_KPI_MINUTES = 5;

    // Status antrean yang sedang berada di dalam depo
    protected $in_depo_statuses = array('IN_YARD', 'IN_SURVEY', 'AT_YARD');

    // Tahap KPI di dalam depo: status => [kunci tahap, nama tahap, kolom waktu mulai tahap]
    protected $stages = array(
        'IN_YARD'   => array('gate_in',  'Gate In',          'gate_in_at'),
        'IN_SURVEY' => array('survey',   'At Yard (Survey)', 'survey_start_at'),
        'AT_YARD'   => array('gate_out', 'Gate Out',         'yard_scan_at')
    );

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

    // Ambil nomor antrean dari hasil scan QR supir ("A-20261025-002 | ONEU1077596 | ...")
    protected function _extract_queue_number($input) {
        $input = strtoupper(trim((string) $input));
        return preg_match('/A-\d{8}-\d{3}/', $input, $m) ? $m[0] : $input;
    }

    // Isi QR supir setelah Gate In: nomor antrean + data kitir
    protected function _qr_payload($queue) {
        return implode(' | ', array($queue->queue_number, $queue->container_no, $queue->principal, $queue->container_size, $queue->customer));
    }

    // Timer KPI: tahap yang sedang berjalan (timer besar) dan total proses sejak Gate In (timer kecil)
    protected function _timer_info($queue) {
        if (empty($queue->gate_in_at) || !isset($this->stages[$queue->status])) {
            return NULL;
        }
        $stage   = $this->stages[$queue->status];
        $started = $queue->{$stage[2]};

        return array(
            'stage'                   => $stage[0],
            'stage_label'             => $stage[1],
            'stage_target_minutes'    => self::STAGE_KPI_MINUTES,
            'stage_remaining_seconds' => self::STAGE_KPI_MINUTES * 60 - (time() - strtotime($started)),
            'total_target_minutes'    => (int) $queue->sla_target_minutes,
            'total_remaining_seconds' => (int) $queue->sla_target_minutes * 60 - (time() - strtotime($queue->gate_in_at))
        );
    }

    // Durasi (menit) setiap tahap yang sudah dilalui, untuk ringkasan & KPI per tahap
    protected function _stage_durations($queue) {
        $points = array(
            array('Gate In', $queue->gate_in_at, $queue->survey_start_at),
            array('At Yard (Survey)', $queue->survey_start_at, $queue->yard_scan_at),
            array('Gate Out', $queue->yard_scan_at, $queue->gate_out_at)
        );
        $result = array();
        foreach ($points as $p) {
            if (empty($p[1]) || empty($p[2])) {
                continue;
            }
            $minutes  = round((strtotime($p[2]) - strtotime($p[1])) / 60, 1);
            $result[] = array('label' => $p[0], 'minutes' => $minutes, 'achieved' => $minutes <= self::STAGE_KPI_MINUTES);
        }
        return $result;
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

    // Daftar antrean yang menunggu diproses pada tahap tertentu (belum di-scan)
    // WAITING_GATE = menunggu Gate In, IN_YARD = menunggu masuk survey, IN_SURVEY = sedang survey, AT_YARD = menunggu Gate Out
    protected function _pending_queues($status) {
        $since_column = array('WAITING_GATE' => 'q.created_at', 'IN_YARD' => 'q.gate_in_at', 'IN_SURVEY' => 'q.survey_start_at', 'AT_YARD' => 'q.yard_scan_at');
        $column = $since_column[$status];

        $sql = "SELECT q.queue_number, q.license_plate, q.container_no, z.zone_code, z.depo, TIMESTAMPDIFF(MINUTE, {$column}, ?) AS waiting_minutes
                FROM queue_transactions q
                LEFT JOIN yard_zones z ON z.id = q.current_zone_id
                WHERE q.status = ?";
        $params = array($this->_now(), $status);
        if ($status === 'WAITING_GATE') {
            $sql .= " AND q.created_at >= ?";
            $params[] = $this->_expiry_cutoff();
        }
        $rows = $this->db->query($sql . " ORDER BY q.id ASC", $params)->result();

        $pending = array();
        foreach ($rows as $row) {
            $minutes = max(0, (int) $row->waiting_minutes);
            $pending[] = array(
                'queue_number'    => $row->queue_number,
                'license_plate'   => $row->license_plate,
                'container_no'    => $row->container_no,
                'zone_code'       => $row->zone_code,
                'depo'            => $row->depo,
                'waiting_minutes' => $minutes,
                // Antre di luar belum dihitung KPI; tahap di dalam depo target STAGE_KPI_MINUTES
                'overdue'         => $status !== 'WAITING_GATE' && $minutes > self::STAGE_KPI_MINUTES
            );
        }
        return $pending;
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
