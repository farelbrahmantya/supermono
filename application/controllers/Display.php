<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Layar TV live antrean (role DISPLAY): hanya-baca, tanpa login.
 * Akses lewat link berkunci dari tombol "Layar TV" di Control Tower: display/tv/<kunci>.
 * Data yang tampil tidak memuat nomor HP supir maupun laporan SOS.
 */
class Display extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
    }

    public function tv($key = '') {
        $this->_check_key($key);
        $this->load->view('display/tv_view', array(
            'data_url' => base_url('display/data/' . $key),
            'targets'  => array('stage' => self::STAGE_KPI_MINUTES, 'export' => self::EXPORT_PICK_KPI_MINUTES)
        ));
    }

    public function data($key = '') {
        $this->_check_key($key);
        $this->_demo_tick();

        $kpi    = $this->_get_kpi_metrics();
        $queues = array();
        $over   = 0;
        foreach ($this->_get_active_queues() as $q) {
            if ($q['stage_level'] === 'over' || $q['sla_level'] === 'over') {
                $over++;
            }
            $queues[] = array(
                'queue_number'   => $q['queue_number'],
                'license_plate'  => $q['license_plate'],
                'container_no'   => $q['container_no'],
                'activity'       => $q['activity'],
                'activity_label' => $q['activity_label'],
                'stage'          => $q['stage'],
                'stage_minutes'  => $q['stage_minutes'],
                'stage_target'   => $q['stage_target'],
                'stage_level'    => $q['stage_level'],
                'sla_level'      => $q['sla_level'],
                'zone'           => $q['zone'],
                'estimated_call' => $q['estimated_call']
            );
        }

        $this->_json(array(
            'time'   => date('H:i:s'),
            'demo'   => $this->demo_model->active(),
            'kpi'    => array(
                'waiting'   => $kpi['truck_waiting'],
                'in_yard'   => $kpi['truck_in_yard'],
                'completed' => $kpi['completed_today'],
                'avg_tat'   => $kpi['avg_tat'],
                'over_sla'  => $over
            ),
            'queues' => $queues
        ));
    }

    private function _check_key($key) {
        if (!is_string($key) || strlen($key) !== 24 || $key !== $this->_display_key()) {
            show_404();
        }
    }
}
