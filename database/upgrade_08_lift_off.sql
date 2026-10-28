-- =====================================================================
-- LASMONO - Upgrade 08: tahap Lift Off (Repo In / Import)
-- Alur Repo In & Import menjadi 4 tahap: Gate In (5) - Survey (5) - Lift Off (10) - Gate Out (5),
-- target total 25 menit. Yardman / surveyor / operator scan barcode supir ketiga kalinya
-- setelah container diturunkan di blok.
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_07_dampak_demo.sql. Backup (Ekspor) terlebih dahulu.
-- WAJIB dijalankan sebelum file aplikasi update ini di-upload.
-- =====================================================================

-- Status baru LIFT_OFF: survey selesai, truk menuju blok & container diturunkan
ALTER TABLE `queue_transactions`
  MODIFY `status` enum('WAITING_GATE','IN_YARD','IN_SURVEY','LIFT_OFF','AT_YARD','COMPLETED','CANCELLED') NOT NULL DEFAULT 'WAITING_GATE',
  ADD `lift_off_by` int(11) DEFAULT NULL AFTER `yard_scan_by`,
  ADD `lift_off_at` datetime DEFAULT NULL AFTER `yard_scan_at`;

-- Transaksi lama tetap dihitung 3 tahap (lift_off_at kosong), tidak perlu diubah.
