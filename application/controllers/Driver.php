<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Driver extends MY_Controller {

    protected $allowed_roles = array('DRIVER');

    // Jenis laporan SOS: kondisi tidak aman (HSE), pemungutan liar, penyerobotan antrean
    private $sos_types = array('UNSAFE', 'PUNGLI', 'PENYEROBOTAN');
    const SOS_LIMIT_PER_QUEUE = 3;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index() {
        $queue = $this->_get_active_queue();

        // Jika tidak ada antrean aktif (sudah COMPLETED / kedaluwarsa), kembalikan ke portal login
        if (!$queue) {
            $this->session->unset_userdata(array('driver_phone', 'license_plate', 'role', 'logged_in'));
            $this->session->set_flashdata('error_driver', 'Tidak ada antrean aktif (sudah selesai atau kedaluwarsa). Silakan daftar ulang.');
            redirect('auth');
            return;
        }

        $data['queue']                 = $queue;
        $data['queue_position']        = $queue->status === 'WAITING_GATE' ? $this->_queue_position($queue) : NULL;
        $data['estimated_wait']        = $this->_estimated_wait($data['queue_position']);
        $data['timer']                 = $this->_timer_info($queue);
        $data['stage_durations']       = $this->_stage_durations($queue);
        // Barcode baru muncul setelah Gate In (berisi data kitir)
        $data['qr_payload']            = empty($queue->gate_in_at) ? NULL : $this->_qr_payload($queue);

        $this->load->view('driver/dashboard_view', $data);
    }

    // Submit Laporan SOS dengan Upload Foto/Video Bukti (Maks 10MB)
    public function submit_sos() {
        // queue_id diambil dari session, bukan dari form, agar tidak bisa melapor atas nama antrean lain
        $queue = $this->_get_active_queue();
        if (!$queue) {
            redirect('driver');
            return;
        }

        $report_type = $this->input->post('report_type');
        $description = trim((string) $this->input->post('description'));

        if (!in_array($report_type, $this->sos_types, TRUE) || $description === '' || mb_strlen($description) > 1000) {
            $this->session->set_flashdata('error_sos', 'Jenis pelanggaran dan deskripsi (maks. 1000 karakter) wajib diisi dengan benar.');
            redirect('driver');
            return;
        }

        $sos_count = $this->db->where('queue_id', $queue->id)->count_all_results('sos_reports');
        if ($sos_count >= self::SOS_LIMIT_PER_QUEUE) {
            $this->session->set_flashdata('error_sos', 'Batas laporan SOS untuk antrean ini sudah tercapai. Hubungi Control Tower secara langsung.');
            redirect('driver');
            return;
        }

        $media_path = NULL;

        // Konfigurasi Upload File CI3
        $config['upload_path']   = './upload/';
        $config['allowed_types'] = 'jpg|jpeg|png|mp4|mov';
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
            'queue_id'    => $queue->id,
            'report_type' => $report_type,
            'description' => $description,
            'media_path'  => $media_path,
            'created_at'  => $this->_now()
        );

        $this->db->insert('sos_reports', $data_sos);
        $this->session->set_flashdata('success_sos', 'Laporan SOS dan bukti berhasil dikirim ke Control Tower!');
        redirect('driver');
    }

    // API Endpoint untuk Auto-Polling Status Real-Time di Driver View
    public function get_status_json() {
        $phone = $this->session->userdata('driver_phone');
        $plate = $this->session->userdata('license_plate');

        $this->db->select('q.*, z.zone_code, z.depo');
        $this->db->from('queue_transactions q');
        $this->db->join('yard_zones z', 'z.id = q.current_zone_id', 'left');
        $this->db->where('q.driver_phone', $phone);
        $this->db->where('q.license_plate', $plate);
        $this->db->order_by('q.id', 'DESC');
        $this->db->limit(1);
        $queue = $this->db->get()->row();

        if ($queue) {
            $position = $queue->status === 'WAITING_GATE' ? $this->_queue_position($queue) : NULL;
            $this->_json(array(
                'status'          => 'success',
                'queue_status'    => $this->_is_expired($queue) ? 'EXPIRED' : $queue->status,
                'queue_position'  => $position,
                'estimated_wait'  => $this->_estimated_wait($position),
                'timer'           => $this->_timer_info($queue),
                'stage_durations' => $queue->status === 'COMPLETED' ? $this->_stage_durations($queue) : array()
            ));
        } else {
            $this->_json(array('status' => 'not_found'));
        }
    }

    // Antrean aktif supir (WAITING_GATE yang belum kedaluwarsa, atau sedang di dalam depo)
    private function _get_active_queue() {
        $this->db->select('q.*, z.zone_code, z.depo');
        $this->db->from('queue_transactions q');
        $this->db->join('yard_zones z', 'z.id = q.current_zone_id', 'left');
        $this->db->where('q.driver_phone', $this->session->userdata('driver_phone'));
        $this->db->where('q.license_plate', $this->session->userdata('license_plate'));
        $this->db->where_in('q.status', array_merge(array('WAITING_GATE'), $this->in_depo_statuses));
        $this->db->order_by('q.id', 'DESC');
        $this->db->limit(1);
        $queue = $this->db->get()->row();

        return ($queue && !$this->_is_expired($queue)) ? $queue : NULL;
    }
}
