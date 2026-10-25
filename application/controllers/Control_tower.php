<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Control_tower extends MY_Controller {

    protected $allowed_roles = array('CONTROL_TOWER');

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index() {
        $data = $this->_get_dashboard_data();
        $data['admin_name'] = $this->session->userdata('name');

        $this->load->view('control_tower/dashboard_view', $data);
    }

    // Endpoint API untuk Auto-Refresh Data
    public function get_live_data() {
        $this->_json($this->_get_dashboard_data());
    }

    private function _get_dashboard_data() {
        return array(
            'kpi'         => $this->_get_kpi_metrics(),
            'queues'      => $this->_get_active_queues(),
            'sos_reports' => $this->_get_sos_reports(),
            'heatmap'     => $this->_get_yard_density()
        );
    }

    private function _get_kpi_metrics() {
        $cutoff = $this->_expiry_cutoff();

        // Truk Antre di Luar (yang belum kedaluwarsa)
        $truck_waiting = $this->db->where('status', 'WAITING_GATE')
                                  ->where('created_at >=', $cutoff)
                                  ->count_all_results('queue_transactions');

        // Antrean kedaluwarsa (tidak pernah Gate In)
        $truck_expired = $this->db->where('status', 'WAITING_GATE')
                                  ->where('created_at <', $cutoff)
                                  ->count_all_results('queue_transactions');

        // Truk di dalam depo (sudah Gate In, belum Gate Out)
        $truck_in_yard = $this->db->where_in('status', array('IN_YARD', 'AT_YARD'))
                                  ->count_all_results('queue_transactions');

        // Rata-rata TAT (Menit) truk yang Gate Out hari ini:
        // total = daftar antrean -> gate out, wait = daftar -> gate in, service = gate in -> gate out
        $query_tat = $this->db->query("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, gate_out_at)) AS avg_total,
                   AVG(TIMESTAMPDIFF(MINUTE, created_at, gate_in_at))  AS avg_wait,
                   AVG(TIMESTAMPDIFF(MINUTE, gate_in_at, gate_out_at)) AS avg_service,
                   COUNT(*) AS completed
            FROM queue_transactions
            WHERE status = 'COMPLETED' AND gate_out_at >= ?
        ", array(date('Y-m-d 00:00:00')))->row();

        // Antrean per tahap yang belum di-scan
        $pending_yard     = $this->db->where('status', 'IN_YARD')->count_all_results('queue_transactions');
        $pending_gate_out = $this->db->where('status', 'AT_YARD')->count_all_results('queue_transactions');

        // Throughput Gate In 1 jam terakhir
        $gate_in_last_hour = $this->db->where('gate_in_at >=', date('Y-m-d H:i:s', time() - 3600))
                                      ->count_all_results('queue_transactions');

        return array(
            'avg_tat'           => (int) round((float) $query_tat->avg_total),
            'avg_wait'          => (int) round((float) $query_tat->avg_wait),
            'avg_service'       => (int) round((float) $query_tat->avg_service),
            'completed_today'   => (int) $query_tat->completed,
            'truck_waiting'     => $truck_waiting,
            'truck_expired'     => $truck_expired,
            'truck_in_yard'     => $truck_in_yard,
            'pending_yard'      => $pending_yard,
            'pending_gate_out'  => $pending_gate_out,
            'gate_in_last_hour' => $gate_in_last_hour
        );
    }

    private function _get_active_queues() {
        $now  = $this->_now();
        $rows = $this->db->query("
            SELECT q.id, q.queue_number, q.license_plate, q.status, q.sla_target_minutes, z.zone_code,
            CASE WHEN q.status = 'WAITING_GATE'
                 THEN TIMESTAMPDIFF(MINUTE, q.created_at, ?)
                 ELSE TIMESTAMPDIFF(MINUTE, q.gate_in_at, ?)
            END AS elapsed_minutes,
            CASE q.status
                 WHEN 'WAITING_GATE' THEN TIMESTAMPDIFF(MINUTE, q.created_at, ?)
                 WHEN 'IN_YARD'      THEN TIMESTAMPDIFF(MINUTE, q.gate_in_at, ?)
                 ELSE TIMESTAMPDIFF(MINUTE, q.yard_scan_at, ?)
            END AS stage_minutes
            FROM queue_transactions q
            LEFT JOIN yard_zones z ON z.id = q.current_zone_id
            WHERE q.status IN ('IN_YARD', 'AT_YARD')
               OR (q.status = 'WAITING_GATE' AND q.created_at >= ?)
            ORDER BY q.id ASC
        ", array($now, $now, $now, $now, $now, $this->_expiry_cutoff()))->result();

        $rate     = $this->_minutes_per_gate_in();
        $position = 0;
        $queues   = array();

        $stages = array('WAITING_GATE' => 'gate_in', 'IN_YARD' => 'yard', 'AT_YARD' => 'gate_out');

        foreach ($rows as $q) {
            $elapsed       = max(0, (int) $q->elapsed_minutes);
            $stage_minutes = max(0, (int) $q->stage_minutes);
            $over_sla = $q->status !== 'WAITING_GATE' && $elapsed > (int) $q->sla_target_minutes;
            $estimated_call = NULL;

            if ($q->status === 'WAITING_GATE') {
                $position++;
                $label = 'Menunggu Gate';
                $badge = 'badge-waiting';
                if ($rate !== NULL) {
                    $estimated_call = (int) round(($position - 1) * $rate);
                }
            } elseif ($q->status === 'IN_YARD') {
                $label = 'Menunggu Yardman';
                $badge = 'badge-yard';
            } else {
                $label = 'Menunggu Gate Out' . ($q->zone_code ? ' (Blok ' . $q->zone_code . ')' : '');
                $badge = 'badge-yard';
            }

            if ($over_sla) {
                $label = 'Over SLA - ' . $label;
                $badge = 'badge-over-sla';
            }

            $queues[] = array(
                'queue_number'    => $q->queue_number,
                'license_plate'   => $q->license_plate,
                'status_label'    => $label,
                'badge_class'     => $badge,
                'elapsed_minutes' => $elapsed,
                'estimated_call'  => $estimated_call,
                // Tahap yang sedang ditunggu & lama menunggu di tahap itu (KPI per tahap di dalam depo)
                'stage'           => $stages[$q->status],
                'stage_minutes'   => $stage_minutes,
                'stage_overdue'   => $q->status !== 'WAITING_GATE' && $stage_minutes > self::STAGE_KPI_MINUTES
            );
        }

        return $queues;
    }

    // Tandai laporan SOS sudah ditindaklanjuti
    public function resolve_sos() {
        $this->db->where('id', (int) $this->input->post('sos_id'))
                 ->where('is_resolved', 0)
                 ->update('sos_reports', array('is_resolved' => 1));
        $this->_json(array('status' => $this->db->affected_rows() === 1 ? 'success' : 'warning'));
    }

    private function _get_sos_reports() {
        return $this->db->query("
            SELECT s.id, s.created_at, s.report_type, s.description, s.media_path, s.is_resolved, q.queue_number, q.license_plate 
            FROM sos_reports s
            JOIN queue_transactions q ON s.queue_id = q.id
            ORDER BY s.is_resolved ASC, s.id DESC
            LIMIT 100
        ")->result();
    }

    private function _get_yard_density() {
        // Jumlah truk yang sedang dilayani (AT_YARD) per blok dibanding kapasitas blok
        $rows = $this->db->query("
            SELECT z.zone_code, z.capacity, COUNT(q.id) AS total
            FROM yard_zones z
            LEFT JOIN queue_transactions q ON q.current_zone_id = z.id AND q.status = 'AT_YARD'
            GROUP BY z.id, z.zone_code, z.capacity
            ORDER BY z.zone_code ASC
        ")->result();

        $grid = array();
        foreach ($rows as $row) {
            $count    = (int) $row->total;
            $capacity = max(1, (int) $row->capacity);
            $percent  = $count / $capacity * 100;

            $level = 'low';
            if ($percent >= 80) $level = 'high';
            elseif ($percent >= 50) $level = 'medium';

            $grid[$row->zone_code] = array('count' => $count, 'capacity' => (int) $row->capacity, 'level' => $level);
        }
        return $grid;
    }
}
