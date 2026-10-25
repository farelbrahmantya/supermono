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

    // Riwayat truk yang sudah selesai (Gate Out) dalam rentang tanggal
    public function history() {
        list($date_from, $date_to, $error) = $this->_history_range();
        $rows = $error ? array() : $this->_get_history($date_from, $date_to);

        $data = array(
            'date_from'  => $date_from,
            'date_to'    => $date_to,
            'error'      => $error,
            'rows'       => $rows,
            'summary'    => $this->_history_summary($rows),
            'admin_name' => $this->session->userdata('name')
        );
        $this->load->view('control_tower/history_view', $data);
    }

    // Export riwayat ke CSV (dibuka di Excel)
    public function history_csv() {
        list($date_from, $date_to, $error) = $this->_history_range();
        if ($error) {
            show_error($error, 400);
            return;
        }
        $rows = $this->_get_history($date_from, $date_to);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="riwayat_lasmono_' . $date_from . '_sd_' . $date_to . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        fputcsv($out, array(
            'No. Antrean', 'Nopol', 'No. HP Supir', 'No. Container', 'Principal', 'Size', 'Customer', 'Depo', 'Blok',
            'Daftar', 'Gate In', 'Masuk Survey', 'Selesai Survey', 'Gate Out',
            'Antre (mnt)', 'Tahap Gate In (mnt)', 'Tahap Survey (mnt)', 'Tahap Gate Out (mnt)', 'Total Proses (mnt)', 'Capai KPI Semua Tahap',
            'Petugas Gate In', 'Yardman Survey', 'Yardman Selesai', 'Petugas Gate Out'
        ), ';');
        foreach ($rows as $r) {
            fputcsv($out, array(
                $r['queue_number'], $r['license_plate'], $r['driver_phone'], $r['container_no'], $r['principal'], $r['container_size'], $r['customer'], $r['depo'], $r['zone_code'],
                $r['created_at'], $r['gate_in_at'], $r['survey_start_at'], $r['yard_scan_at'], $r['gate_out_at'],
                $this->_csv_number($r['wait_minutes']), $this->_csv_number($r['stage_gate_in']), $this->_csv_number($r['stage_survey']),
                $this->_csv_number($r['stage_gate_out']), $this->_csv_number($r['total_minutes']), $r['all_achieved'] ? 'YA' : 'TIDAK',
                $r['gate_in_name'], $r['survey_start_name'], $r['yard_scan_name'], $r['gate_out_name']
            ), ';');
        }
        fclose($out);
        exit;
    }

    // Rentang tanggal dari query string (default hari ini), maksimal 93 hari
    private function _history_range() {
        $today     = date('Y-m-d');
        $date_from = (string) $this->input->get('from');
        $date_to   = (string) $this->input->get('to');
        $date_from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : $today;
        $date_to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) ? $date_to : $today;

        $error = NULL;
        if ($date_from > $date_to) {
            $error = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
        } elseif ((strtotime($date_to) - strtotime($date_from)) / 86400 > 93) {
            $error = 'Rentang tanggal maksimal 93 hari (3 bulan).';
        }
        return array($date_from, $date_to, $error);
    }

    private function _get_history($date_from, $date_to) {
        $rows = $this->db->query("
            SELECT q.*, z.zone_code, z.depo,
                   ugi.name AS gate_in_name, uss.name AS survey_start_name, uys.name AS yard_scan_name, ugo.name AS gate_out_name
            FROM queue_transactions q
            LEFT JOIN yard_zones z ON z.id = q.current_zone_id
            LEFT JOIN users ugi ON ugi.id = q.gate_in_by
            LEFT JOIN users uss ON uss.id = q.survey_start_by
            LEFT JOIN users uys ON uys.id = q.yard_scan_by
            LEFT JOIN users ugo ON ugo.id = q.gate_out_by
            WHERE q.status = 'COMPLETED' AND q.gate_out_at >= ? AND q.gate_out_at <= ?
            ORDER BY q.gate_out_at DESC
        ", array($date_from . ' 00:00:00', $date_to . ' 23:59:59'))->result_array();

        foreach ($rows as &$r) {
            $r['wait_minutes']   = $this->_minutes_between($r['created_at'], $r['gate_in_at']);
            $r['stage_gate_in']  = $this->_minutes_between($r['gate_in_at'], $r['survey_start_at']);
            $r['stage_survey']   = $this->_minutes_between($r['survey_start_at'], $r['yard_scan_at']);
            $r['stage_gate_out'] = $this->_minutes_between($r['yard_scan_at'], $r['gate_out_at']);
            $r['total_minutes']  = $this->_minutes_between($r['gate_in_at'], $r['gate_out_at']);

            $r['all_achieved'] = TRUE;
            foreach (array('stage_gate_in', 'stage_survey', 'stage_gate_out') as $stage) {
                if ($r[$stage] === NULL || $r[$stage] > self::STAGE_KPI_MINUTES) {
                    $r['all_achieved'] = FALSE;
                }
            }
        }
        unset($r);
        return $rows;
    }

    // Ringkasan riwayat: jumlah truk, rata-rata & persentase capai target per tahap
    private function _history_summary($rows) {
        $summary = array('total' => count($rows), 'all_achieved' => 0, 'stages' => array());
        $stages  = array('stage_gate_in' => 'Gate In', 'stage_survey' => 'At Yard (Survey)', 'stage_gate_out' => 'Gate Out', 'total_minutes' => 'Total Proses');

        foreach ($stages as $key => $label) {
            $values = array();
            foreach ($rows as $r) {
                if ($r[$key] !== NULL) {
                    $values[] = $r[$key];
                }
            }
            $target   = $key === 'total_minutes' ? self::STAGE_KPI_MINUTES * 3 : self::STAGE_KPI_MINUTES;
            $achieved = 0;
            foreach ($values as $v) {
                if ($v <= $target) $achieved++;
            }
            $summary['stages'][] = array(
                'label'    => $label,
                'target'   => $target,
                'count'    => count($values),
                'avg'      => count($values) ? round(array_sum($values) / count($values), 1) : NULL,
                'percent'  => count($values) ? (int) round($achieved / count($values) * 100) : NULL
            );
        }
        foreach ($rows as $r) {
            if ($r['all_achieved']) $summary['all_achieved']++;
        }
        return $summary;
    }

    // Desimal pakai koma agar terbaca sebagai angka di Excel berformat Indonesia
    private function _csv_number($value) {
        return $value === NULL ? '' : str_replace('.', ',', (string) $value);
    }

    private function _minutes_between($from, $to) {
        if (empty($from) || empty($to)) {
            return NULL;
        }
        return round((strtotime($to) - strtotime($from)) / 60, 1);
    }

    // Endpoint API untuk Auto-Refresh Data
    public function get_live_data() {
        $this->_json($this->_get_dashboard_data());
    }

    private function _get_dashboard_data() {
        return array(
            'kpi'         => $this->_get_kpi_metrics(),
            'queues'      => $this->_get_active_queues(),
            'stage_kpi'   => $this->_get_stage_kpi(),
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
        $truck_in_yard = $this->db->where_in('status', $this->in_depo_statuses)
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
            SELECT q.id, q.queue_number, q.license_plate, q.container_no, q.status, q.sla_target_minutes, z.zone_code, z.depo,
            CASE WHEN q.status = 'WAITING_GATE'
                 THEN TIMESTAMPDIFF(MINUTE, q.created_at, ?)
                 ELSE TIMESTAMPDIFF(MINUTE, q.gate_in_at, ?)
            END AS elapsed_minutes,
            CASE q.status
                 WHEN 'WAITING_GATE' THEN TIMESTAMPDIFF(MINUTE, q.created_at, ?)
                 WHEN 'IN_YARD'      THEN TIMESTAMPDIFF(MINUTE, q.gate_in_at, ?)
                 WHEN 'IN_SURVEY'    THEN TIMESTAMPDIFF(MINUTE, q.survey_start_at, ?)
                 ELSE TIMESTAMPDIFF(MINUTE, q.yard_scan_at, ?)
            END AS stage_minutes
            FROM queue_transactions q
            LEFT JOIN yard_zones z ON z.id = q.current_zone_id
            WHERE q.status IN ('IN_YARD', 'IN_SURVEY', 'AT_YARD')
               OR (q.status = 'WAITING_GATE' AND q.created_at >= ?)
            ORDER BY q.id ASC
        ", array($now, $now, $now, $now, $now, $now, $this->_expiry_cutoff()))->result();

        $rate     = $this->_minutes_per_gate_in();
        $position = 0;
        $queues   = array();

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
                $label = 'Gate In - Menuju Survey';
                $badge = 'badge-yard';
            } elseif ($q->status === 'IN_SURVEY') {
                $label = 'Sedang Survey';
                $badge = 'badge-yard';
            } else {
                $label = 'Menunggu Gate Out' . ($q->zone_code ? ' (' . $q->depo . ' Blok ' . $q->zone_code . ')' : '');
                $badge = 'badge-yard';
            }

            if ($over_sla) {
                $label = 'Over SLA - ' . $label;
                $badge = 'badge-over-sla';
            }

            $queues[] = array(
                'queue_number'    => $q->queue_number,
                'license_plate'   => $q->license_plate,
                'container_no'    => $q->container_no,
                'status_label'    => $label,
                'badge_class'     => $badge,
                'elapsed_minutes' => $elapsed,
                'estimated_call'  => $estimated_call,
                // Tahap yang sedang ditunggu & lama menunggu di tahap itu (KPI per tahap di dalam depo)
                'stage'           => isset($this->stages[$q->status]) ? $this->stages[$q->status][0] : 'antre',
                'stage_minutes'   => $stage_minutes,
                'stage_overdue'   => $q->status !== 'WAITING_GATE' && $stage_minutes > self::STAGE_KPI_MINUTES
            );
        }

        return $queues;
    }

    // KPI per tahap untuk truk yang Gate Out hari ini: rata-rata menit & jumlah yang mencapai target
    private function _get_stage_kpi() {
        $target = self::STAGE_KPI_MINUTES * 60;
        $row = $this->db->query("
            SELECT COUNT(*) AS total,
                   AVG(TIMESTAMPDIFF(SECOND, gate_in_at, survey_start_at))   AS avg_gate_in,
                   AVG(TIMESTAMPDIFF(SECOND, survey_start_at, yard_scan_at)) AS avg_survey,
                   AVG(TIMESTAMPDIFF(SECOND, yard_scan_at, gate_out_at))     AS avg_gate_out,
                   SUM(TIMESTAMPDIFF(SECOND, gate_in_at, survey_start_at) <= ?)   AS ok_gate_in,
                   SUM(TIMESTAMPDIFF(SECOND, survey_start_at, yard_scan_at) <= ?) AS ok_survey,
                   SUM(TIMESTAMPDIFF(SECOND, yard_scan_at, gate_out_at) <= ?)     AS ok_gate_out
            FROM queue_transactions
            WHERE status = 'COMPLETED' AND gate_out_at >= ? AND survey_start_at IS NOT NULL
        ", array($target, $target, $target, date('Y-m-d 00:00:00')))->row();

        $total  = (int) $row->total;
        $result = array();
        foreach (array('gate_in' => 'Gate In', 'survey' => 'At Yard (Survey)', 'gate_out' => 'Gate Out') as $key => $label) {
            $ok = (int) $row->{'ok_' . $key};
            $result[] = array(
                'stage'       => $key,
                'label'       => $label,
                'avg_minutes' => $total ? round((float) $row->{'avg_' . $key} / 60, 1) : NULL,
                'achieved'    => $ok,
                'total'       => $total,
                'percent'     => $total ? (int) round($ok / $total * 100) : NULL
            );
        }
        return $result;
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
            SELECT z.zone_code, z.depo, z.capacity, COUNT(q.id) AS total
            FROM yard_zones z
            LEFT JOIN queue_transactions q ON q.current_zone_id = z.id AND q.status = 'AT_YARD'
            GROUP BY z.id, z.zone_code, z.depo, z.capacity
            ORDER BY z.depo ASC, z.zone_code ASC
        ")->result();

        $grid = array();
        foreach ($rows as $row) {
            $count    = (int) $row->total;
            $capacity = max(1, (int) $row->capacity);
            $percent  = $count / $capacity * 100;

            $level = 'low';
            if ($percent >= 80) $level = 'high';
            elseif ($percent >= 50) $level = 'medium';

            $grid[$row->zone_code] = array('depo' => $row->depo, 'count' => $count, 'capacity' => (int) $row->capacity, 'level' => $level);
        }
        return $grid;
    }
}
