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

    // Toleransi tahap pemilihan container Export (menit)
    const EXPORT_PICK_KPI_MINUTES = 10;

    // Status antrean yang sedang berada di dalam depo
    protected $in_depo_statuses = array('IN_YARD', 'IN_SURVEY', 'AT_YARD');

    // Jenis aktivitas = huruf awal barcode kitir
    protected $activities = array('Z' => 'Repo In', 'A' => 'Import', 'D' => 'Export', 'E' => 'Repo Out');

    // Aktivitas muat (container keluar depo): tanpa survey, Yardman langsung memuat container
    protected function _is_muat($activity) {
        return in_array($activity, array('D', 'E'), TRUE);
    }

    protected function _activity_label($activity) {
        return isset($this->activities[$activity]) ? $this->activities[$activity] : $this->activities['Z'];
    }

    // Tahap KPI di dalam depo per aktivitas:
    // kunci => [nama tahap, status saat tahap berjalan, kolom mulai, kolom selesai, target menit]
    protected function _stage_plan($activity) {
        $gate_out = array('Gate Out', 'AT_YARD', 'yard_scan_at', 'gate_out_at', self::STAGE_KPI_MINUTES);
        if ($activity === 'D') {
            return array(
                'pick'     => array('Pilih & Muat Container', 'IN_YARD', 'gate_in_at', 'yard_scan_at', self::EXPORT_PICK_KPI_MINUTES),
                'gate_out' => $gate_out
            );
        }
        if ($activity === 'E') {
            return array(
                'load'     => array('Muat Container', 'IN_YARD', 'gate_in_at', 'yard_scan_at', self::STAGE_KPI_MINUTES),
                'gate_out' => $gate_out
            );
        }
        return array(
            'gate_in'  => array('Gate In', 'IN_YARD', 'gate_in_at', 'survey_start_at', self::STAGE_KPI_MINUTES),
            'survey'   => array('At Yard (Survey)', 'IN_SURVEY', 'survey_start_at', 'yard_scan_at', self::STAGE_KPI_MINUTES),
            'gate_out' => $gate_out
        );
    }

    // Tahap yang sedang berjalan untuk antrean: [kunci, nama, kolom mulai, target menit], NULL jika di luar depo
    protected function _current_stage($queue) {
        foreach ($this->_stage_plan(isset($queue->activity) ? $queue->activity : 'Z') as $key => $stage) {
            if ($stage[1] === $queue->status) {
                return array($key, $stage[0], $stage[2], $stage[4]);
            }
        }
        return NULL;
    }

    // Target total proses (menit) = jumlah target semua tahap aktivitas
    protected function _total_target($activity) {
        $total = 0;
        foreach ($this->_stage_plan($activity) as $stage) {
            $total += $stage[4];
        }
        return $total;
    }

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
        $stage = $this->_current_stage($queue);
        if (empty($queue->gate_in_at) || $stage === NULL) {
            return NULL;
        }
        $started = $queue->{$stage[2]};

        return array(
            'stage'                   => $stage[0],
            'stage_label'             => $stage[1],
            'stage_target_minutes'    => $stage[3],
            'stage_remaining_seconds' => $stage[3] * 60 - (time() - strtotime($started)),
            'total_target_minutes'    => (int) $queue->sla_target_minutes,
            'total_remaining_seconds' => (int) $queue->sla_target_minutes * 60 - (time() - strtotime($queue->gate_in_at))
        );
    }

    // Durasi (menit) setiap tahap yang sudah dilalui, untuk ringkasan & KPI per tahap
    protected function _stage_durations($queue) {
        $result = array();
        foreach ($this->_stage_plan(isset($queue->activity) ? $queue->activity : 'Z') as $stage) {
            $from = $queue->{$stage[2]};
            $to   = $queue->{$stage[3]};
            if (empty($from) || empty($to)) {
                continue;
            }
            $minutes  = round((strtotime($to) - strtotime($from)) / 60, 1);
            $result[] = array('label' => $stage[0], 'minutes' => $minutes, 'achieved' => $minutes <= $stage[4]);
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

        $sql = "SELECT q.queue_number, q.license_plate, q.container_no, q.status, q.activity, z.zone_code, z.depo, TIMESTAMPDIFF(MINUTE, {$column}, ?) AS waiting_minutes
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
            $stage   = $this->_current_stage($row);
            $pending[] = array(
                'activity'        => $row->activity,
                'activity_label'  => $this->_activity_label($row->activity),
                'queue_number'    => $row->queue_number,
                'license_plate'   => $row->license_plate,
                'container_no'    => $row->container_no,
                'zone_code'       => $row->zone_code,
                'depo'            => $row->depo,
                'waiting_minutes' => $minutes,
                // Antre di luar belum dihitung KPI; tahap di dalam depo sesuai target tahap aktivitasnya
                'overdue'         => $stage !== NULL && $minutes > $stage[3]
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
