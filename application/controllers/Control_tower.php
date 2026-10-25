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
            END AS elapsed_minutes
            FROM queue_transactions q
            LEFT JOIN yard_zones z ON z.id = q.current_zone_id
            WHERE q.status IN ('IN_YARD', 'AT_YARD')
               OR (q.status = 'WAITING_GATE' AND q.created_at >= ?)
            ORDER BY q.id ASC
        ", array($now, $now, $this->_expiry_cutoff()))->result();

        $rate     = $this->_minutes_per_gate_in();
        $position = 0;
        $queues   = array();

        foreach ($rows as $q) {
            $elapsed  = max(0, (int) $q->elapsed_minutes);
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
                $label = 'Gate In / Menuju Yard';
                $badge = 'badge-yard';
            } else {
                $label = $q->zone_code ? 'Di Yard (Blok ' . $q->zone_code . ')' : 'Di Yard';
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
                'estimated_call'  => $estimated_call
            );
        }

        return $queues;
    }

    private function _get_sos_reports() {
        return $this->db->query("
            SELECT s.created_at, s.report_type, s.description, s.media_path, q.queue_number, q.license_plate
            FROM sos_reports s
            JOIN queue_transactions q ON s.queue_id = q.id
            ORDER BY s.id DESC
            LIMIT 100
        ")->result();
    }

    private function _get_yard_density() {
        // Jumlah truk yang sedang dilayani (AT_YARD) per blok, satu query untuk semua blok
        $rows = $this->db->query("
            SELECT z.zone_code, COUNT(q.id) AS total
            FROM yard_zones z
            LEFT JOIN queue_transactions q ON q.current_zone_id = z.id AND q.status = 'AT_YARD'
            GROUP BY z.zone_code
        ")->result();

        $counts = array();
        foreach ($rows as $row) {
            $counts[$row->zone_code] = (int) $row->total;
        }

        // Grid Matriks Yard A1 - F5
        $zones = array('A', 'B', 'C', 'D', 'E', 'F');
        $grid = array();

        foreach ($zones as $z) {
            for ($i = 1; $i <= 5; $i++) {
                $code  = $z . $i;
                $count = isset($counts[$code]) ? $counts[$code] : 0;

                $level = 'low';
                if ($count > 10) $level = 'high';
                elseif ($count > 5) $level = 'medium';

                $grid[$code] = array('count' => $count, 'level' => $level);
            }
        }
        return $grid;
    }
}
