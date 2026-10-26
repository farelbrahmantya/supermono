<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Login petugas memakai user aplikasi utama perusahaan (database new_general,
| tabel tbluser + tblstaff). LASMONO hanya membaca (SELECT), tidak pernah menulis.
*/

// Aktif/nonaktifkan login dengan user perusahaan (akun lokal LASMONO tetap bisa dipakai)
$config['sso_enabled'] = TRUE;

// Cara aplikasi utama (COINS, class/class_login.php LoginUser) menyimpan tbluser.password:
//   password = AES_ENCRYPT(password, $HashKey)  -> mode 'aes'
// Mode lain yang didukung: 'auto', 'md5', 'sha1', 'mysql_password', 'bcrypt', 'encode'.
$config['sso_password_mode'] = getenv('LASMONO_SSO_PASSWORD_MODE') ?: 'aes';

// Nilai $HashKey dari aplikasi utama (lihat config.php COINS). RAHASIA: isi langsung di server,
// atau lewat environment LASMONO_SSO_PASSWORD_KEY. Jangan di-commit ke GitHub.
$config['sso_password_key']  = getenv('LASMONO_SSO_PASSWORD_KEY') ?: '';

// Cabang yang boleh masuk (tblstaff.ktrasl), 003 = Surabaya
$config['sso_branch'] = '003';

// Pemetaan user perusahaan -> role LASMONO, dicek berurutan (yang pertama cocok dipakai).
// Kunci yang bisa dipakai: user (tbluser.user), kddept, posisi (daftar, tanpa beda huruf besar/kecil), staff_type.
// Role GATE = Security: setelah login bisa berpindah Gate In / Gate Out.
$config['sso_rules'] = array(
    // Akses penuh (Control Tower dapat membuka Control Tower, Gate In/Out, Yardman, Riwayat, Layar TV)
    array('role' => 'CONTROL_TOWER', 'user' => 'farel.sby', 'kddept' => '008', 'posisi' => array('SECTION HEAD'), 'staff_type' => 'Internal'),
    array('role' => 'GATE',          'user' => 'security.sby'),
    array('role' => 'GATE',          'kddept' => '999', 'posisi' => array('SECURITY')),
    array('role' => 'CONTROL_TOWER', 'kddept' => '007', 'posisi' => array('SECTION HEAD', 'DEPARTMENT HEAD', 'DEPT HEAD', 'DEPT. HEAD'), 'staff_type' => 'Internal'),
    array('role' => 'YARDMAN',       'kddept' => '007', 'posisi' => array('STAFF'), 'staff_type' => 'Internal')
);
