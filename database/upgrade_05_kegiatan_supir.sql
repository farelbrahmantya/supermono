-- =====================================================================
-- LASMONO - Upgrade 05: kegiatan yang dipilih supir saat daftar antrean
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_04_aktivitas_kitir.sql. Backup (Ekspor) terlebih dahulu.
-- Kolom activity tetap mengikuti kitir saat Gate In; driver_activity menyimpan pilihan supir.
-- =====================================================================

ALTER TABLE `queue_transactions`
  ADD `driver_activity` char(1) DEFAULT NULL AFTER `activity`;
