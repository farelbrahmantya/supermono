<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Control_tower extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');

        if ($this->session->userdata('role') !== 'CONTROL_TOWER') {
            redirect('auth');
        }
    }

    public function index() {
        $data['kpi']         = $this->_get_kpi_metrics();
        $data['queues']      = $this->_get_active_queues();
        $data['sos_reports'] = $this->_get_sos_reports();
        $data['heatmap']     = $this->_get_yard_density();

        $this->load->view('control_tower/dashboard_view', $data);
    }

    // Endpoint API untuk Auto-Refresh Data
    public function get_live_data() {
        $data = array(
            'kpi'         => $this->_get_kpi_metrics(),
            'queues'      => $this->_get_active_queues(),
            'sos_reports' => $this->_get_sos_reports(),
            'heatmap'     => $this->_get_yard_density()
        );
        echo json_encode($data);
    }

    private function _get_kpi_metrics() {
        // Truk Antre di Luar
        $truck_waiting = $this->db->where('status', 'WAITING_GATE')->count_all_results('queue_transactions');
        
        // Truk di Yard
        $truck_in_yard = $this->db->where('status', 'IN_YARD')->count_all_results('queue_transactions');

        // Hitung Rata-rata Turn Around Time (TAT) dalam Menit untuk yang COMPLETED hari ini
        $query_tat = $this->db->query("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, gate_in_at, gate_out_at)) AS avg_tat 
            FROM queue_transactions 
            WHERE status = 'COMPLETED' AND DATE(created_at) = CURDATE()
        ")->row();

        $avg_tat = round($query_tat->avg_tat ? $query_tat->avg_tat : 0);

        return array(
            'avg_tat'       => $avg_tat,
            'truck_waiting' => $truck_waiting,
            'truck_in_yard' => $truck_in_yard,
            'gate_active'   => '2/2'
        );
    }

    private function _get_active_queues() {
        return $this->db->query("
            SELECT *, 
            TIMESTAMPDIFF(MINUTE, gate_in_at, NOW()) AS elapsed_minutes 
            FROM queue_transactions 
            WHERE status IN ('WAITING_GATE', 'IN_YARD') 
            ORDER BY id ASC
        ")->result();
    }

    private function _get_sos_reports() {
        return $this->db->query("
            SELECT s.*, q.queue_number, q.license_plate 
            FROM sos_reports s
            JOIN queue_transactions q ON s.queue_id = q.id
            ORDER BY s.id DESC
        ")->result();
    }

    private function _get_yard_density() {
        // Simulasi Grid Matriks Yard A1 - F5
        $zones = array('A', 'B', 'C', 'D', 'E', 'F');
        $grid = array();

        foreach ($zones as $z) {
            for ($i = 1; $i <= 5; $i++) {
                $code = $z . $i;
                $count = $this->db->query("
                    SELECT COUNT(*) as total FROM queue_transactions 
                    WHERE status = 'IN_YARD' AND current_zone_id = (SELECT id FROM yard_zones WHERE zone_code = '$code')
                ")->row()->total;

                $level = 'low';
                if ($count > 10) $level = 'high';
                elseif ($count > 5) $level = 'medium';

                $grid[$code] = array('count' => $count, 'level' => $level);
            }
        }
        return $grid;
    }
}