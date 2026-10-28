-- =====================================================================
-- LASMONO - Upgrade 09: jenis laporan SOS
-- Pilihan laporan driver: Kondisi Tidak Aman (Unsafe Condition, aspek HSE),
-- Pemungutan Liar, dan Penyerobotan Antrean. Nilai LAINNYA tetap disimpan
-- agar laporan lama masih terbaca.
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_08_lift_off.sql. Backup (Ekspor) terlebih dahulu.
-- WAJIB dijalankan sebelum file aplikasi update ini di-upload.
-- =====================================================================

ALTER TABLE `sos_reports`
  MODIFY `report_type` enum('UNSAFE','PUNGLI','PENYEROBOTAN','LAINNYA') NOT NULL DEFAULT 'UNSAFE';
