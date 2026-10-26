-- =====================================================================
-- LASMONO - Upgrade 06: login petugas dengan user aplikasi utama (new_general)
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_05_kegiatan_supir.sql. Backup (Ekspor) terlebih dahulu.
-- Akun lokal lama (yardman1, admin_ct, gate_in, gate_out) tetap bisa dipakai.
-- =====================================================================

ALTER TABLE `users`
  ADD `source` enum('LOCAL','NEW_GENERAL') NOT NULL DEFAULT 'LOCAL' AFTER `role`,
  ADD `ext_user_id` int(11) DEFAULT NULL AFTER `source`,
  ADD `nik` varchar(20) DEFAULT NULL AFTER `ext_user_id`,
  ADD `department` varchar(100) DEFAULT NULL AFTER `nik`,
  ADD `position` varchar(50) DEFAULT NULL AFTER `department`,
  ADD `last_login_at` datetime DEFAULT NULL AFTER `created_at`,
  ADD UNIQUE KEY `uk_source_ext` (`source`, `ext_user_id`);

-- ---------------------------------------------------------------------
-- Hak akses baca ke new_general: jalankan sebagai root / admin MySQL di server 10.28.222.211.
-- Ganti 'USER_LASMONO' dengan user database yang dipakai LASMONO (lihat config/database.php),
-- dan host '10.28.252.86' dengan IP server aplikasi LASMONO.
-- ---------------------------------------------------------------------
-- GRANT SELECT ON `new_general`.`tbluser`  TO 'USER_LASMONO'@'10.28.252.86';
-- GRANT SELECT ON `new_general`.`tblstaff` TO 'USER_LASMONO'@'10.28.252.86';
-- GRANT SELECT ON `new_general`.`tbldept`  TO 'USER_LASMONO'@'10.28.252.86';
-- FLUSH PRIVILEGES;
