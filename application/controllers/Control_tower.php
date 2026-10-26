<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Control_tower extends MY_Controller {

    protected $allowed_roles = array('CONTROL_TOWER');

    // Batas traffic truk di jalan akses satu blok: >= MEDIUM = sedang, >= HIGH = padat
    const TRAFFIC_MEDIUM = 3;
    const TRAFFIC_HIGH = 5;

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
            'No. Antrean', 'Aktivitas', 'Nopol', 'No. HP Supir', 'No. Container', 'Principal', 'Size', 'Customer', 'Depo', 'Blok',
            'Daftar', 'Gate In', 'Masuk Survey', 'Selesai Survey', 'Gate Out',
            'Antre (mnt)', 'Tahap Gate In (mnt)', 'Tahap Survey (mnt)', 'Tahap Muat Container (mnt)', 'Tahap Gate Out (mnt)', 'Total Proses (mnt)', 'Capai KPI Semua Tahap',
            'Petugas Gate In', 'Yardman Survey', 'Yardman Selesai', 'Petugas Gate Out'
        ), ';');
        foreach ($rows as $r) {
            fputcsv($out, array(
                $r['queue_number'], $r['activity_label'], $r['license_plate'], $r['driver_phone'], $r['container_no'], $r['principal'], $r['container_size'], $r['customer'], $r['depo'], $r['zone_code'],
                $r['created_at'], $r['gate_in_at'], $r['survey_start_at'], $r['yard_scan_at'], $r['gate_out_at'],
                $this->_csv_number($r['wait_minutes']), $this->_csv_number($r['stage_gate_in']), $this->_csv_number($r['stage_survey']),
                $this->_csv_number($r['stage_muat']), $this->_csv_number($r['stage_gate_out']), $this->_csv_number($r['total_minutes']), $r['all_achieved'] ? 'YA' : 'TIDAK',
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
            $plan = $this->_stage_plan($r['activity']);

            $r['activity_label'] = $this->_activity_label($r['activity']);
            $r['wait_minutes']   = $this->_minutes_between($r['created_at'], $r['gate_in_at']);
            $r['total_minutes']  = $this->_minutes_between($r['gate_in_at'], $r['gate_out_at']);
            $r['total_target']   = $this->_total_target($r['activity']);
            $r['stage_gate_in']  = $r['stage_survey'] = $r['stage_muat'] = $r['stage_gate_out'] = NULL;
            $r['muat_target']    = NULL;
            // Durasi per tahap: kunci tahap => menit
            $r['stages']         = array();

            $r['all_achieved'] = TRUE;
            foreach ($plan as $key => $stage) {
                $minutes = $this->_minutes_between($r[$stage[2]], $r[$stage[3]]);
                $r['stages'][$key] = $minutes;
                if ($minutes === NULL || $minutes > $stage[4]) {
                    $r['all_achieved'] = FALSE;
                }
            }
            $r['stage_gate_in']  = isset($r['stages']['gate_in']) ? $r['stages']['gate_in'] : NULL;
            $r['stage_survey']   = isset($r['stages']['survey']) ? $r['stages']['survey'] : NULL;
            $r['stage_gate_out'] = $r['stages']['gate_out'];
            foreach (array('pick', 'load') as $key) {
                if (array_key_exists($key, $plan)) {
                    $r['stage_muat']  = $r['stages'][$key];
                    $r['muat_target'] = $plan[$key][4];
                }
            }
        }
        unset($r);
        return $rows;
    }

    // Semua tahap KPI dari seluruh aktivitas: kunci => [nama, target menit]
    private function _all_stages() {
        $all = array();
        foreach (array('Z', 'D', 'E') as $activity) {
            foreach ($this->_stage_plan($activity) as $key => $stage) {
                $all[$key] = array($stage[0] . ($key === 'pick' ? ' (Export)' : ($key === 'load' ? ' (Repo Out)' : '')), $stage[4]);
            }
        }
        // Urutan tampil: tahap Gate Out paling akhir
        $gate_out = $all['gate_out'];
        unset($all['gate_out']);
        $all['gate_out'] = $gate_out;
        return $all;
    }

    // Ringkasan riwayat: jumlah truk, rata-rata & persentase capai target per tahap
    private function _history_summary($rows) {
        $summary = array('total' => count($rows), 'all_achieved' => 0, 'stages' => array());
        $stages  = $this->_all_stages();
        $stages['total'] = array('Total Proses', NULL);

        foreach ($stages as $key => $stage) {
            $values   = array();
            $achieved = 0;
            foreach ($rows as $r) {
                $value  = $key === 'total' ? $r['total_minutes'] : (isset($r['stages'][$key]) ? $r['stages'][$key] : NULL);
                $target = $key === 'total' ? $r['total_target'] : $stage[1];
                if ($value !== NULL) {
                    $values[] = $value;
                    if ($value <= $target) $achieved++;
                }
            }
            // Tahap muat hanya ditampilkan jika ada truk aktivitas tersebut
            if (in_array($key, array('pick', 'load'), TRUE) && empty($values)) {
                continue;
            }
            $summary['stages'][] = array(
                'label'    => $stage[0],
                'target'   => $key === 'total' ? NULL : $stage[1],
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
            SELECT q.id, q.queue_number, q.license_plate, q.container_no, q.status, q.activity, q.sla_target_minutes, z.zone_code, z.depo,
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
            } elseif ($q->status === 'IN_YARD' && $q->activity === 'D') {
                $label = 'Gate In - Pilih Container (Yardman)';
                $badge = 'badge-yard';
            } elseif ($q->status === 'IN_YARD' && $q->activity === 'E') {
                $label = 'Gate In - Menuju Muat (Yardman)';
                $badge = 'badge-yard';
            } elseif ($q->status === 'IN_YARD') {
                $label = 'Gate In - Menuju Survey';
                $badge = 'badge-yard';
            } elseif ($q->status === 'IN_SURVEY') {
                $label = 'Sedang Survey';
                $badge = 'badge-yard';
            } else {
                $label = ($this->_is_muat($q->activity) ? 'Sudah Muat - ' : '') . 'Menunggu Gate Out' . ($q->zone_code ? ' (' . $q->depo . ' Blok ' . $q->zone_code . ')' : '');
                $badge = 'badge-yard';
            }

            if ($over_sla) {
                $label = 'Over SLA - ' . $label;
                $badge = 'badge-over-sla';
            }

            $stage = $this->_current_stage($q);
            $queues[] = array(
                'queue_number'    => $q->queue_number,
                'activity'        => $q->activity,
                'activity_label'  => $this->_activity_label($q->activity),
                'license_plate'   => $q->license_plate,
                'container_no'    => $q->container_no,
                'status_label'    => $label,
                'badge_class'     => $badge,
                'elapsed_minutes' => $elapsed,
                'estimated_call'  => $estimated_call,
                // Tahap yang sedang ditunggu & lama menunggu di tahap itu (KPI per tahap di dalam depo)
                'stage'           => $stage ? $stage[0] : 'antre',
                'stage_target'    => $stage ? $stage[3] : NULL,
                'stage_minutes'   => $stage_minutes,
                'stage_overdue'   => $stage !== NULL && $stage_minutes > $stage[3]
            );
        }

        return $queues;
    }

    // KPI per tahap untuk truk yang Gate Out hari ini: rata-rata menit & jumlah yang mencapai target
    private function _get_stage_kpi() {
        $rows = $this->db->query("
            SELECT activity, gate_in_at, survey_start_at, yard_scan_at, gate_out_at
            FROM queue_transactions
            WHERE status = 'COMPLETED' AND gate_out_at >= ?
        ", array(date('Y-m-d 00:00:00')))->result_array();

        $stats = array();
        foreach ($rows as $r) {
            foreach ($this->_stage_plan($r['activity']) as $key => $stage) {
                $minutes = $this->_minutes_between($r[$stage[2]], $r[$stage[3]]);
                if ($minutes === NULL) {
                    continue;
                }
                if (!isset($stats[$key])) {
                    $stats[$key] = array('sum' => 0, 'ok' => 0, 'total' => 0);
                }
                $stats[$key]['sum'] += $minutes;
                $stats[$key]['total']++;
                if ($minutes <= $stage[4]) $stats[$key]['ok']++;
            }
        }

        $result = array();
        foreach ($this->_all_stages() as $key => $stage) {
            $s = isset($stats[$key]) ? $stats[$key] : array('sum' => 0, 'ok' => 0, 'total' => 0);
            // Tahap muat (Export / Repo Out) hanya tampil jika hari ini ada truknya
            if (in_array($key, array('pick', 'load'), TRUE) && $s['total'] === 0) {
                continue;
            }
            $result[] = array(
                'stage'       => $key,
                'label'       => $stage[0],
                'target'      => $stage[1],
                'avg_minutes' => $s['total'] ? round($s['sum'] / $s['total'], 1) : NULL,
                'achieved'    => $s['ok'],
                'total'       => $s['total'],
                'percent'     => $s['total'] ? (int) round($s['ok'] / $s['total'] * 100) : NULL
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
        // Traffic trucking di jalan akses tiap blok: truk yang sudah diarahkan ke blok (survey selesai)
        // dan belum Gate Out. Bukan isi tumpukan container di blok.
        $rows = $this->db->query("
            SELECT z.zone_code, z.depo, z.principal, z.principal_color, COUNT(q.id) AS total
            FROM yard_zones z
            LEFT JOIN queue_transactions q ON q.current_zone_id = z.id AND q.status = 'AT_YARD'
            GROUP BY z.id, z.zone_code, z.depo, z.principal, z.principal_color
            ORDER BY z.depo ASC, z.zone_code ASC
        ")->result();

        $grid = array();
        foreach ($rows as $row) {
            $count = (int) $row->total;

            $level = 'low';
            if ($count >= self::TRAFFIC_HIGH) $level = 'high';
            elseif ($count >= self::TRAFFIC_MEDIUM) $level = 'medium';

            $grid[$row->zone_code] = array(
                'depo'            => $row->depo,
                'principal'       => $row->principal,
                'principal_color' => $row->principal_color,
                'count'           => $count,
                'level'           => $level
            );
        }
        return $grid;
    }
}
