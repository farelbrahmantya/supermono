<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Driver extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');

        // Mencegah simpanan cache halaman oleh browser
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        // Validasi Session Driver
        if ($this->session->userdata('role') !== 'DRIVER') {
            redirect('auth');
        }
    }

    public function index() {
        $phone = $this->session->userdata('driver_phone');
        $plate = $this->session->userdata('license_plate');

        // Mengambil antrean aktif supir (WAITING_GATE, IN_YARD, atau AT_YARD)
        $this->db->where('driver_phone', $phone);
        $this->db->where('license_plate', $plate);
        $this->db->where_in('status', array('WAITING_GATE', 'IN_YARD', 'AT_YARD'));
        $this->db->order_by('id', 'DESC');
        $queue = $this->db->get('queue_transactions')->row();

        // Jika tidak ada antrean aktif (misal sudah COMPLETED), kembalikan ke portal login
        if (!$queue) {
            $this->session->unset_userdata(array('driver_phone', 'license_plate', 'role', 'logged_in'));
            redirect('auth');
            return;
        }

        // Hitung Posisi Antrean FIFO Hari Ini
        $this->db->where_in('status', array('WAITING_GATE', 'IN_YARD', 'AT_YARD'));
        $this->db->where('DATE(created_at)', date('Y-m-d'));
        $this->db->where('id <=', $queue->id);
        $queue_position = $this->db->count_all_results('queue_transactions');

        $data['queue']          = $queue;
        $data['queue_position'] = $queue_position;

        $this->load->view('driver/dashboard_view', $data);
    }

    // Submit Laporan SOS dengan Upload Foto/Video Bukti (Maks 10MB)
    public function submit_sos() {
        $queue_id    = $this->input->post('queue_id');
        $report_type = $this->input->post('report_type');
        $description = $this->input->post('description');
        $media_path  = NULL;

        // Konfigurasi Upload File CI3
        $config['upload_path']   = './upload/';
        $config['allowed_types'] = 'jpg|jpeg|png|gif|mp4|avi|mov|mkv';
        $config['max_size']      = 10240; // Batas 10MB
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!empty($_FILES['sos_media']['name'])) {
            if ($this->upload->do_upload('sos_media')) {
                $upload_data = $this->upload->data();
                $media_path  = 'upload/' . $upload_data['file_name'];
            } else {
                $this->session->set_flashdata('error_sos', $this->upload->display_errors('', ''));
                redirect('driver');
                return;
            }
        }

        $data_sos = array(
            'queue_id'    => $queue_id,
            'report_type' => $report_type,
            'description' => $description,
            'media_path'  => $media_path,
            'created_at'  => date('Y-m-d H:i:s')
        );

        $this->db->insert('sos_reports', $data_sos);
        $this->session->set_flashdata('success_sos', 'Laporan SOS dan bukti berhasil dikirim ke Control Tower!');
        redirect('driver');
    }

    // API Endpoint untuk Auto-Polling Status Real-Time di Driver View
    public function get_status_json() {
        $phone = $this->session->userdata('driver_phone');
        $plate = $this->session->userdata('license_plate');

        $this->db->where('driver_phone', $phone);
        $this->db->where('license_plate', $plate);
        $this->db->order_by('id', 'DESC');
        $queue = $this->db->get('queue_transactions')->row();

        if ($queue) {
            echo json_encode(array(
                'status'             => 'success',
                'queue_status'       => $queue->status,
                'gate_in_at'         => $queue->gate_in_at,
                'yard_scan_at'       => $queue->yard_scan_at,
                'gate_out_at'        => $queue->gate_out_at,
                'sla_target_minutes' => $queue->sla_target_minutes
            ));
        } else {
            echo json_encode(array('status' => 'not_found'));
        }
    }
}