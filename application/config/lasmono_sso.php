<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Login petugas memakai user aplikasi utama perusahaan (database new_general,
| tabel tbluser + tblstaff). LASMONO hanya membaca (SELECT), tidak pernah menulis.
*/

// Aktif/nonaktifkan login dengan user perusahaan (akun lokal LASMONO tetap bisa dipakai)
$config['sso_enabled'] = TRUE;

// Cara aplikasi utama menyimpan tbluser.password:
//   'auto'     : coba md5, sha1, PASSWORD() MySQL, bcrypt (dan aes/encode jika kunci diisi)
//   'md5' | 'sha1' | 'mysql_password' | 'bcrypt'
//   'aes'      : AES_ENCRYPT(password, kunci)  -> isi sso_password_key
//   'encode'   : ENCODE(password, kunci)       -> isi sso_password_key
// Setelah cara yang dipakai diketahui, ganti 'auto' dengan cara tersebut.
$config['sso_password_mode'] = getenv('LASMONO_SSO_PASSWORD_MODE') ?: 'auto';
$config['sso_password_key']  = getenv('LASMONO_SSO_PASSWORD_KEY') ?: '';

// Cabang yang boleh masuk (tblstaff.ktrasl), 003 = Surabaya
$config['sso_branch'] = '003';

// Pemetaan user perusahaan -> role LASMONO, dicek berurutan (yang pertama cocok dipakai).
// Kunci yang bisa dipakai: user (tbluser.user), kddept, posisi (daftar, tanpa beda huruf besar/kecil), staff_type.
// Role GATE = Security: setelah login bisa berpindah Gate In / Gate Out.
$config['sso_rules'] = array(
    array('role' => 'GATE',          'user' => 'security.sby'),
    array('role' => 'GATE',          'kddept' => '999', 'posisi' => array('SECURITY')),
    array('role' => 'CONTROL_TOWER', 'kddept' => '007', 'posisi' => array('SECTION HEAD', 'DEPARTMENT HEAD', 'DEPT HEAD', 'DEPT. HEAD'), 'staff_type' => 'Internal'),
    array('role' => 'YARDMAN',       'kddept' => '007', 'posisi' => array('STAFF'), 'staff_type' => 'Internal')
);
