<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User aplikasi utama perusahaan (database new_general: tbluser + tblstaff).
 * Hanya SELECT. Password dicek di dalam query sehingga hash password tidak dibaca ke aplikasi,
 * kecuali mode bcrypt yang memang harus diverifikasi di PHP.
 */
class Company_user_model extends CI_Model {

    private $general_db = NULL;

    public function __construct() {
        parent::__construct();
        $this->config->load('lasmono_sso', TRUE);
    }

    public function enabled() {
        return (bool) $this->_cfg('sso_enabled');
    }

    private function _cfg($key) {
        return $this->config->item($key, 'lasmono_sso');
    }

    private function _db() {
        if ($this->general_db === NULL) {
            $this->general_db = $this->load->database('new_general', TRUE);
        }
        return $this->general_db;
    }

    // Data staff jika username & password benar dan user aktif, selain itu NULL
    public function authenticate($username, $password) {
        $username = trim((string) $username);
        if ($username === '' || (string) $password === '') {
            return NULL;
        }

        list($check_sql, $check_params) = $this->_password_check($password);
        $params = array_merge(array($username, $this->_cfg('sso_branch')), $check_params);

        $row = $this->_db()->query("
            SELECT u.user_id, u.user, u.password AS password_hash, s.nik, s.nama, s.ktrasl, s.kddept, s.posisi, s.staff_type, d.deptnam
            FROM tbluser u
            JOIN tblstaff s ON s.user_id = u.user_id
            LEFT JOIN tbldept d ON d.kddept = s.kddept
            WHERE u.user = ? AND u.active = 1 AND s.ktrasl = ? AND ({$check_sql})
            LIMIT 1
        ", $params)->row();

        if (!$row) {
            return NULL;
        }
        // bcrypt hanya bisa diverifikasi di PHP
        if ($check_sql === '1 = 1' || ($this->_mode() === 'auto' && strpos((string) $row->password_hash, '$2') === 0 && !$this->_sql_matched($row->user_id, $password))) {
            if (!password_verify($password, (string) $row->password_hash)) {
                return NULL;
            }
        }
        unset($row->password_hash);
        return $row;
    }

    // Role LASMONO untuk staff sesuai sso_rules, NULL jika tidak punya akses
    public function role_for($staff) {
        foreach ($this->_cfg('sso_rules') as $rule) {
            if (isset($rule['user']) && strcasecmp($rule['user'], $staff->user) !== 0) continue;
            if (isset($rule['kddept']) && trim($staff->kddept) !== $rule['kddept']) continue;
            if (isset($rule['staff_type']) && strcasecmp(trim($staff->staff_type), $rule['staff_type']) !== 0) continue;
            if (isset($rule['posisi']) && !in_array(strtoupper(trim($staff->posisi)), $rule['posisi'], TRUE)) continue;
            return $rule['role'];
        }
        return NULL;
    }

    private function _mode() {
        return strtolower((string) $this->_cfg('sso_password_mode'));
    }

    // Potongan WHERE untuk mencocokkan password sesuai mode
    private function _password_check($password) {
        $key = (string) $this->_cfg('sso_password_key');
        $hex = "LOWER(CONVERT(u.password USING latin1))";
        $checks = array(
            'md5'            => array("({$hex} = MD5(?) OR u.password = UNHEX(MD5(?)))", array($password, $password)),
            'sha1'           => array("({$hex} = SHA1(?) OR u.password = UNHEX(SHA1(?)))", array($password, $password)),
            'mysql_password' => array("CONVERT(u.password USING latin1) = PASSWORD(?)", array($password)),
            // Sama seperti LoginUser() di aplikasi utama: password = AES_ENCRYPT(password, $HashKey)
            'aes'            => array("u.password = AES_ENCRYPT(?, ?)", array($password, $key)),
            'encode'         => array("DECODE(u.password, ?) = ?", array($key, $password))
        );

        $mode = $this->_mode();
        if ($mode === 'bcrypt') {
            return array('1 = 1', array());
        }
        if (isset($checks[$mode])) {
            return $checks[$mode];
        }

        // auto: gabungan semua cara (aes/encode hanya jika kunci diisi), atau hash bcrypt
        $sql = array("LEFT(CONVERT(u.password USING latin1), 2) = '$2'");
        $params = array();
        foreach ($checks as $name => $check) {
            if (($name === 'aes' || $name === 'encode') && $key === '') continue;
            $sql[] = $check[0];
            $params = array_merge($params, $check[1]);
        }
        return array(implode(' OR ', $sql), $params);
    }

    // Mode auto: apakah password cocok lewat cara selain bcrypt
    private function _sql_matched($user_id, $password) {
        list($sql, $params) = $this->_password_check($password);
        $sql = str_replace("LEFT(CONVERT(u.password USING latin1), 2) = '$2' OR ", '', $sql);
        return (bool) $this->_db()->query("SELECT 1 FROM tbluser u WHERE u.user_id = ? AND ({$sql})", array_merge(array($user_id), $params))->row();
    }
}
