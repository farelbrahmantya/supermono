<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
    }

    // Halaman Utama Login
    public function index() {
        // Hanya Role DRIVER yang bisa langsung redirect otomatis
        if ($this->session->userdata('role') === 'DRIVER' && $this->session->userdata('logged_in')) {
            redirect('driver');
        }

        // Untuk Petugas: Hapus session lama agar wajib re-login saat buka tab/portal baru
        if (in_array($this->session->userdata('role'), array('YARDMAN', 'CONTROL_TOWER', 'GATE_IN', 'GATE_OUT'))) {
            $this->session->unset_userdata(array('user_id', 'name', 'username', 'role', 'logged_in'));
        }

        $this->load->view('auth/login_view');
    }

    // Proses Login Petugas (Gate, Yardman & Control Tower)
    public function process_login_staff() {
        $username = trim($this->input->post('username'));
        $password = (string) $this->input->post('password');

        $user = $this->db->get_where('users', array('username' => $username))->row();

        if ($user && password_verify($password, $user->password)) {
            // Cegah session fixation
            $this->session->sess_regenerate(TRUE);

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
        $phone = $this->_normalize_phone($this->input->post('driver_phone'));
        $plate = $this->_normalize_plate($this->input->post('license_plate'));

        if ($phone === FALSE || $plate === FALSE) {
            $this->session->set_flashdata('error_driver', 'Format Nomor HP (contoh: 08123456789) atau Nopol (contoh: B 9123 UQK) tidak valid!');
            redirect('auth');
            return;
        }

        // 1. CEK ANTREAN AKTIF (berdasarkan Nomor HP ATAU Nopol, tanpa batas tanggal)
        $compact_plate = str_replace(' ', '', $plate);
        $this->db->group_start();
            $this->db->where('driver_phone', $phone);
            $this->db->or_where("REPLACE(license_plate, ' ', '') = " . $this->db->escape($compact_plate), NULL, FALSE);
        $this->db->group_end();
        $this->db->group_start();
            $this->db->where_in('status', $this->in_depo_statuses);
            $this->db->or_group_start();
                $this->db->where('status', 'WAITING_GATE');
                $this->db->where('created_at >=', $this->_expiry_cutoff());
            $this->db->group_end();
        $this->db->group_end();
        $this->db->order_by('id', 'DESC');
        $active_queues = $this->db->get('queue_transactions')->result();

        foreach ($active_queues as $active_queue) {
            if ($active_queue->driver_phone === $phone && str_replace(' ', '', $active_queue->license_plate) === $compact_plate) {
                // Kembalikan supir ke antrean aktif miliknya sendiri
                $this->_set_driver_session($active_queue->driver_phone, $active_queue->license_plate);
                $this->session->set_flashdata('info_driver', 'Anda dikembalikan ke antrean aktif #' . $active_queue->queue_number);
                redirect('driver');
                return;
            }
        }

        if (!empty($active_queues)) {
            // Nopol atau Nomor HP sudah dipakai antrean aktif dengan pasangan data lain:
            // tolak agar antrean orang lain tidak bisa diambil alih dan tidak ada antrean ganda
            $this->session->set_flashdata('error_driver', 'Nomor HP atau Nopol ini sudah terdaftar di antrean aktif #' . $active_queues[0]->queue_number . '. Gunakan pasangan Nomor HP & Nopol yang sama seperti saat mendaftar.');
            redirect('auth');
            return;
        }

        // 2. JIKA BELUM ADA ANTREAN AKTIF: Buatkan Nomor Antrean Baru
        $queue_num = $this->_create_queue($phone, $plate);
        if ($queue_num === FALSE) {
            $this->session->set_flashdata('error_driver', 'Gagal membuat antrean, silakan coba lagi.');
            redirect('auth');
            return;
        }

        $this->_set_driver_session($phone, $plate);
        redirect('driver');
    }

    // Token CSRF terbaru untuk form/AJAX: token yang tertanam di halaman bisa basi
    // jika cookie CSRF berubah setelah halaman dimuat (misalnya oleh proxy/CDN)
    public function csrf_token() {
        $this->_json(array(
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash()
        ));
    }

    // Logout Semuanya
    public function logout() {
        $this->session->sess_destroy();
        redirect('auth');
    }

    private function _set_driver_session($phone, $plate) {
        $this->session->sess_regenerate(TRUE);
        $this->session->set_userdata(array(
            'driver_phone'  => $phone,
            'license_plate' => $plate,
            'role'          => 'DRIVER',
            'logged_in'     => TRUE
        ));
    }

    // Insert antrean baru; dicoba ulang jika insert gagal
    private function _create_queue($phone, $plate) {
        $prefix = 'A-' . date('Ymd') . '-';
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $last_queue = $this->db->select('queue_number')
                                   ->like('queue_number', $prefix, 'after')
                                   ->order_by('id', 'DESC')
                                   ->limit(1)
                                   ->get('queue_transactions')->row();

            $next_num  = $last_queue ? (int) substr($last_queue->queue_number, -3) + 1 : 1;
            $queue_num = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);

            $inserted = $this->db->insert('queue_transactions', array(
                'queue_number'       => $queue_num,
                'driver_phone'       => $phone,
                'license_plate'      => $plate,
                'status'             => 'WAITING_GATE',
                'sla_target_minutes' => 15,
                'created_at'         => $this->_now()
            ));

            if ($inserted) {
                $this->db->db_debug = $db_debug;
                return $queue_num;
            }
        }

        $this->db->db_debug = $db_debug;
        return FALSE;
    }

    // 0812-3456-789 / +62812... / 62812... -> 08123456789
    private function _normalize_phone($phone) {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strpos($digits, '62') === 0) {
            $digits = '0' . substr($digits, 2);
        }
        return preg_match('/^08\d{7,11}$/', $digits) ? $digits : FALSE;
    }

    // b9123uqk / B  9123 UQK -> B 9123 UQK
    private function _normalize_plate($plate) {
        $compact = strtoupper(preg_replace('/\s+/', '', (string) $plate));
        if (!preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', $compact, $m)) {
            return FALSE;
        }
        return trim($m[1] . ' ' . $m[2] . ' ' . $m[3]);
    }
}
