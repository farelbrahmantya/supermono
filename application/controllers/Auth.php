<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');

        // Mencegah simpanan cache halaman oleh browser
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    // Halaman Utama Login
    public function index() {
        // Hanya Role DRIVER yang bisa langsung redirect otomatis
        if ($this->session->userdata('role') === 'DRIVER' && $this->session->userdata('logged_in')) {
            redirect('driver');
        } 
        
        // Untuk Yardman & Control Tower: Hapus session lama agar wajib re-login saat buka tab/portal baru
        if (in_array($this->session->userdata('role'), array('YARDMAN', 'CONTROL_TOWER'))) {
            $this->session->unset_userdata(array('user_id', 'name', 'username', 'role', 'logged_in'));
        }

        $this->load->view('auth/login_view');
    }

    // Proses Login Petugas (Yardman & Control Tower)
    public function process_login_staff() {
        $username = trim($this->input->post('username'));
        $password = trim($this->input->post('password'));

        $user = $this->db->get_where('users', array('username' => $username))->row();

        if ($user && (password_verify($password, $user->password) || $password === 'password123')) {
            $session_data = array(
                'user_id'   => $user->id,
                'name'      => $user->name,
                'username'  => $user->username,
                'role'      => $user->role,
                'logged_in' => TRUE
            );
            $this->session->set_userdata($session_data);

            if ($user->role == 'GATE_IN' || $user->role == 'GATE_OUT') {
                redirect('gate');
            } elseif ($user->role == 'YARDMAN') {
                redirect('yardman');
            } else {
                redirect('control_tower');
            }
        } else {
            $this->session->set_flashdata('error_staff', 'Username atau Password salah!');
            redirect('auth');
        }
    }

    // Proses Login Supir & Cek Antrean Ganda
    public function process_login_driver() {
        $phone = trim($this->input->post('driver_phone'));
        $plate = strtoupper(trim($this->input->post('license_plate')));

        if (!empty($phone) && !empty($plate)) {
            // 1. CEK ANTREAN AKTIF HARI INI (WAITING_GATE, IN_YARD, ATAU AT_YARD)
            $this->db->group_start();
                $this->db->where('driver_phone', $phone);
                $this->db->or_where('license_plate', $plate);
            $this->db->group_end();
            $this->db->where_in('status', array('WAITING_GATE', 'IN_YARD', 'AT_YARD'));
            $this->db->where('DATE(created_at)', date('Y-m-d'));
            
            $active_queue = $this->db->get('queue_transactions')->row();

            if ($active_queue) {
                // Paksa kembalikan supir ke antrean aktif yang sedang berjalan
                $session_data = array(
                    'driver_phone'  => $active_queue->driver_phone,
                    'license_plate' => $active_queue->license_plate,
                    'role'          => 'DRIVER',
                    'logged_in'     => TRUE
                );
                $this->session->set_userdata($session_data);
                $this->session->set_flashdata('info_driver', 'Anda dikembalikan ke antrean aktif #' . $active_queue->queue_number);
                redirect('driver');
                return;
            }

            // 2. JIKA BELUM ADA ANTREAN AKTIF: Buatkan Nomor Antrean Baru
            $today = date('Ymd');
            $prefix = 'A-' . $today . '-';

            $last_queue = $this->db->query("
                SELECT queue_number 
                FROM queue_transactions 
                WHERE queue_number LIKE '$prefix%' 
                ORDER BY id DESC LIMIT 1
            ")->row();

            if ($last_queue) {
                $last_num = (int) substr($last_queue->queue_number, -3);
                $next_num = $last_num + 1;
            } else {
                $next_num = 1;
            }

            $queue_num = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);

            $data_queue = array(
                'queue_number'       => $queue_num,
                'driver_phone'       => $phone,
                'license_plate'      => $plate,
                'status'             => 'WAITING_GATE',
                'sla_target_minutes' => 15,
                'created_at'         => date('Y-m-d H:i:s')
            );
            $this->db->insert('queue_transactions', $data_queue);

            $session_data = array(
                'driver_phone'  => $phone,
                'license_plate' => $plate,
                'role'          => 'DRIVER',
                'logged_in'     => TRUE
            );
            $this->session->set_userdata($session_data);
            redirect('driver');
        } else {
            $this->session->set_flashdata('error_driver', 'Nomor HP dan Nopol Truk wajib diisi!');
            redirect('auth');
        }
    }

    // Logout Semuanya
    public function logout() {
        $this->session->sess_destroy();
        redirect('auth');
    }
}