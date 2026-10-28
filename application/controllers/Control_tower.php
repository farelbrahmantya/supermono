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
        $this->_demo_tick();
        $data = $this->_get_dashboard_data();
        $data['demo_active']   = $this->demo_model->active();
        $data['demo_has_data'] = $this->demo_model->has_data();
        $data['demo_ready']    = $this->demo_model->ready();
        $data['admin_name'] = $this->session->userdata('name');
        $data['tv_url']     = base_url('display/tv/' . $this->_display_key());

        $this->load->view('control_tower/dashboard_view', $data);
    }

    // Riwayat truk yang sudah selesai (Gate Out) dalam rentang tanggal
    public function history() {
        list($date_from, $date_to, $error) = $this->_history_range();
        // Selama ada data demo, riwayat ikut menampilkannya (ditandai DEMO)
        $this->load->model('demo_model');
        $include_demo = $this->demo_model->has_data();
        $rows = $error ? array() : $this->_get_history($date_from, $date_to, $include_demo);

        $data = array(
            'date_from'  => $date_from,
            'date_to'    => $date_to,
            'error'      => $error,
            'rows'       => $rows,
            'summary'    => $this->_history_summary($rows),
            'include_demo' => $include_demo,
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
        $this->load->model('demo_model');
        $rows = $this->_get_history($date_from, $date_to, $this->demo_model->has_data());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="riwayat_lasmono_' . $date_from . '_sd_' . $date_to . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        fputcsv($out, array(
            'No. Antrean', 'Aktivitas', 'Nopol', 'No. HP Supir', 'No. Container', 'Principal', 'Size', 'Customer', 'Depo', 'Blok',
            'Daftar', 'Gate In', 'Masuk Survey', 'Selesai Survey', 'Lift Off', 'Gate Out',
            'Antre (mnt)', 'Tahap Gate In (mnt)', 'Tahap Survey (mnt)', 'Tahap Lift Off (mnt)', 'Tahap Muat Container (mnt)', 'Tahap Gate Out (mnt)', 'Total Proses (mnt)', 'Capai KPI Semua Tahap',
            'Petugas Gate In', 'Yardman Survey', 'Yardman Selesai', 'Yardman Lift Off', 'Petugas Gate Out'
        ), ';');
        foreach ($rows as $r) {
            fputcsv($out, array(
                $r['queue_number'], $r['activity_label'], $r['license_plate'], $r['driver_phone'], $r['container_no'], $r['principal'], $r['container_size'], $r['customer'], $r['depo'], $r['zone_code'],
                $r['created_at'], $r['gate_in_at'], $r['survey_start_at'], $r['yard_scan_at'], $r['lift_off_at'], $r['gate_out_at'],
                $this->_csv_number($r['wait_minutes']), $this->_csv_number($r['stage_gate_in']), $this->_csv_number($r['stage_survey']), $this->_csv_number($r['stage_lift_off']),
                $this->_csv_number($r['stage_muat']), $this->_csv_number($r['stage_gate_out']), $this->_csv_number($r['total_minutes']), $r['all_achieved'] ? 'YA' : 'TIDAK',
                $r['gate_in_name'], $r['survey_start_name'], $r['yard_scan_name'], $r['lift_off_name'], $r['gate_out_name']
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

    private function _get_history($date_from, $date_to, $include_demo = FALSE) {
        $rows = $this->db->query("
            SELECT q.*, z.zone_code, z.depo,
                   ugi.name AS gate_in_name, uss.name AS survey_start_name, uys.name AS yard_scan_name, ulo.name AS lift_off_name, ugo.name AS gate_out_name
            FROM queue_transactions q
            LEFT JOIN yard_zones z ON z.id = q.current_zone_id
            LEFT JOIN users ugi ON ugi.id = q.gate_in_by
            LEFT JOIN users uss ON uss.id = q.survey_start_by
            LEFT JOIN users uys ON uys.id = q.yard_scan_by
            LEFT JOIN users ulo ON ulo.id = q.lift_off_by
            LEFT JOIN users ugo ON ugo.id = q.gate_out_by
            WHERE q.status = 'COMPLETED' AND q.gate_out_at >= ? AND q.gate_out_at <= ?" . ($include_demo ? '' : ' AND q.is_demo = 0') . "
            ORDER BY q.gate_out_at DESC
        ", array($date_from . ' 00:00:00', $date_to . ' 23:59:59'))->result_array();

        foreach ($rows as &$r) {
            $plan = $this->_stage_plan($r['activity'], $this->_is_legacy($r));

            $r['activity_label'] = $this->_activity_label($r['activity']);
            $r['wait_minutes']   = $this->_minutes_between($r['created_at'], $r['gate_in_at']);
            $r['total_minutes']  = $this->_minutes_between($r['gate_in_at'], $r['gate_out_at']);
            $r['total_target']   = $this->_total_target($r['activity']);
            $r['stage_gate_in']  = $r['stage_survey'] = $r['stage_lift_off'] = $r['stage_muat'] = $r['stage_gate_out'] = NULL;
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
            $r['stage_lift_off'] = isset($r['stages']['lift_off']) ? $r['stages']['lift_off'] : NULL;
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
        $this->_demo_tick();
        $data = $this->_get_dashboard_data();
        $data['demo_active'] = $this->demo_model->active();
        $this->_json($data);
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

    // KPI per tahap untuk truk yang Gate Out hari ini: rata-rata menit & jumlah yang mencapai target
    private function _get_stage_kpi() {
        $rows = $this->db->query("
            SELECT activity, gate_in_at, survey_start_at, yard_scan_at, lift_off_at, gate_out_at
            FROM queue_transactions
            WHERE status = 'COMPLETED' AND gate_out_at >= ?
        ", array(date('Y-m-d 00:00:00')))->result_array();

        $stats = array();
        foreach ($rows as $r) {
            foreach ($this->_stage_plan($r['activity'], $this->_is_legacy($r)) as $key => $stage) {
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

    // Dampak LASMONO: bandingkan dua periode (sebelum vs sesudah perbaikan)
    public function impact() {
        $this->load->model('demo_model');
        $today = date('Y-m-d');
        $b_to   = $this->_date_param('b_to', $today);
        $b_from = $this->_date_param('b_from', date('Y-m-d', strtotime('-6 days')));
        $a_to   = $this->_date_param('a_to', date('Y-m-d', strtotime($b_from . ' -1 day')));
        $a_from = $this->_date_param('a_from', date('Y-m-d', strtotime($a_to . ' -6 days')));
        $include_demo = $this->input->get('demo') !== NULL ? $this->input->get('demo') === '1' : $this->demo_model->has_data();

        $error = NULL;
        foreach (array(array($a_from, $a_to), array($b_from, $b_to)) as $range) {
            if ($range[0] > $range[1]) {
                $error = 'Tanggal awal periode tidak boleh melebihi tanggal akhir.';
            } elseif ((strtotime($range[1]) - strtotime($range[0])) / 86400 > 93) {
                $error = 'Setiap periode maksimal 93 hari.';
            }
        }

        $periods = array();
        if (!$error) {
            foreach (array('a' => array($a_from, $a_to), 'b' => array($b_from, $b_to)) as $key => $range) {
                $periods[$key] = $this->_impact_period($range[0], $range[1], $include_demo);
            }
        }

        $this->load->view('control_tower/impact_view', array(
            'a_from' => $a_from, 'a_to' => $a_to, 'b_from' => $b_from, 'b_to' => $b_to,
            'include_demo' => $include_demo,
            'demo_has_data' => $this->demo_model->has_data(),
            'error' => $error,
            'periods' => $periods,
            'admin_name' => $this->session->userdata('name')
        ));
    }

    private function _date_param($name, $default) {
        $value = (string) $this->input->get($name);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
    }

    // Ringkasan satu periode untuk halaman Dampak
    private function _impact_period($date_from, $date_to, $include_demo) {
        $rows = $this->_get_history($date_from, $date_to, $include_demo);
        $days = (int) round((strtotime($date_to) - strtotime($date_from)) / 86400) + 1;

        $tat = $wait = $service = array();
        $daily = array();
        for ($t = strtotime($date_from); $t <= strtotime($date_to); $t += 86400) {
            $daily[date('Y-m-d', $t)] = array();
        }
        foreach ($rows as $r) {
            if ($r['wait_minutes'] !== NULL) $wait[] = $r['wait_minutes'];
            if ($r['total_minutes'] !== NULL) $service[] = $r['total_minutes'];
            // Data dengan jam tidak wajar (durasi negatif) tidak dihitung
            if ($r['wait_minutes'] !== NULL && $r['total_minutes'] !== NULL && $r['wait_minutes'] >= 0 && $r['total_minutes'] >= 0) {
                $tat[] = $r['wait_minutes'] + $r['total_minutes'];
                $day = substr($r['gate_out_at'], 0, 10);
                if (isset($daily[$day])) $daily[$day][] = $r['wait_minutes'] + $r['total_minutes'];
            }
        }
        $avg = function ($values) {
            return count($values) ? round(array_sum($values) / count($values), 1) : NULL;
        };
        foreach ($daily as $day => $values) {
            $daily[$day] = array('avg' => $avg($values), 'count' => count($values));
        }

        $sos = $this->db->query("
            SELECT COUNT(*) AS total FROM sos_reports s JOIN queue_transactions q ON q.id = s.queue_id
            WHERE s.created_at >= ? AND s.created_at <= ?" . ($include_demo ? '' : ' AND q.is_demo = 0'),
            array($date_from . ' 00:00:00', $date_to . ' 23:59:59'))->row();

        $summary = $this->_history_summary($rows);
        return array(
            'from'         => $date_from,
            'to'           => $date_to,
            'days'         => $days,
            'trucks'       => count($rows),
            'per_day'      => $days ? round(count($rows) / $days, 1) : 0,
            'avg_tat'      => $avg($tat),
            'avg_wait'     => $avg($wait),
            'avg_service'  => $avg($service),
            'kpi_percent'  => count($rows) ? (int) round($summary['all_achieved'] / count($rows) * 100) : NULL,
            'stages'       => $summary['stages'],
            'sos'          => (int) $sos->total,
            'daily'        => $daily
        );
    }

    // Mode Demo untuk presentasi
    public function demo_start() {
        $this->load->model('demo_model');
        if ($this->demo_model->ready()) {
            $this->demo_model->start();
        }
        redirect('control_tower');
    }

    public function demo_stop() {
        $this->load->model('demo_model');
        if ($this->demo_model->ready()) {
            $this->demo_model->stop();
        }
        redirect('control_tower');
    }

    public function demo_clear() {
        $this->load->model('demo_model');
        if ($this->demo_model->ready()) {
            $this->demo_model->clear();
        }
        redirect('control_tower');
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
        // Traffic trucking di jalan akses tiap blok: truk yang sudah diarahkan ke blok (survey selesai,
        // sedang lift off atau menunggu Gate Out) dan belum Gate Out. Bukan isi tumpukan container di blok.
        $rows = $this->db->query("
            SELECT z.zone_code, z.depo, z.principal, z.principal_color, COUNT(q.id) AS total
            FROM yard_zones z
            LEFT JOIN queue_transactions q ON q.current_zone_id = z.id AND q.status IN ('LIFT_OFF', 'AT_YARD')
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
