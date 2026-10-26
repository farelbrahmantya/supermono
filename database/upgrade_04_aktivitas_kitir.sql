-- =====================================================================
-- LASMONO - Upgrade 04: jenis aktivitas kitir (Z Repo In, A Import, D Export, E Repo Out)
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_03_principal_blok.sql. Backup (Ekspor) terlebih dahulu.
-- Antrean lama otomatis bernilai 'Z' (Repo In).
-- =====================================================================

ALTER TABLE `queue_transactions`
  ADD `activity` char(1) NOT NULL DEFAULT 'Z' AFTER `cp_id`,
  ADD KEY `idx_container_no` (`container_no`);
