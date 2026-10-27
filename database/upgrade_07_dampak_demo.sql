-- =====================================================================
-- LASMONO - Upgrade 07: halaman Dampak & Mode Demo
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_06_user_perusahaan.sql. Backup (Ekspor) terlebih dahulu.
-- WAJIB dijalankan sebelum file aplikasi update ini di-upload.
-- =====================================================================

-- Penanda truk simulasi Mode Demo (0 = data asli)
ALTER TABLE `queue_transactions`
  ADD `is_demo` tinyint(1) NOT NULL DEFAULT '0' AFTER `driver_activity`,
  ADD KEY `idx_is_demo` (`is_demo`);

-- Pengaturan aplikasi (status Mode Demo)
CREATE TABLE IF NOT EXISTS `lasmono_settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
