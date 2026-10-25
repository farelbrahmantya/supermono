-- =====================================================================
-- LASMONO - Upgrade database existing (lasmono_db)
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI lewat tab SQL / Import.
-- Backup database (Ekspor) terlebih dahulu sebelum menjalankan script ini.
-- =====================================================================

-- 1. Password petugas
-- Hash lama berformat $2y$ yang tidak bisa diverifikasi PHP 5.3.3, sehingga login
-- selama ini hanya berhasil lewat password cadangan 'password123' yang sudah dihapus
-- dari kode. Hash baru berformat $2a$ (didukung PHP 5.3.3) untuk password: password123
-- Ganti password ini sebelum dipakai di luar demo.
UPDATE `users` SET `password` = '$2a$10$s.JxmO8sGJMbMWaXZGUaEOQLDBE4v9/ixnUpB9NkHBzcqu.6qRHQy' WHERE `username` = 'yardman1';
UPDATE `users` SET `password` = '$2a$10$YV9AonYqCje045qqtoU1KeNmW85yn.ZrEtb2G42NH52czGcAHkmTe' WHERE `username` = 'admin_ct';
UPDATE `users` SET `password` = '$2a$10$xMMGFJunUxU7qpo9Lkq52OZtIHMu/2FqaoUBSRXkSiDgfzUs9GxBG' WHERE `username` = 'gate_in';
UPDATE `users` SET `password` = '$2a$10$/LdGNFVlBfGiLWxZhrwqFemdja41nZ8ONzI.ct8QkYmOF1HXK2MP2' WHERE `username` = 'gate_out';

-- 2. Master blok yard A1 - F5 (dipakai Yardman & heatmap Kepadatan Yard)
INSERT IGNORE INTO `yard_zones` (`zone_code`, `zone_name`, `capacity`, `created_at`) VALUES
('A1', 'Blok A1', 20, NOW()),
('A2', 'Blok A2', 20, NOW()),
('A3', 'Blok A3', 20, NOW()),
('A4', 'Blok A4', 20, NOW()),
('A5', 'Blok A5', 20, NOW()),
('B1', 'Blok B1', 20, NOW()),
('B2', 'Blok B2', 20, NOW()),
('B3', 'Blok B3', 20, NOW()),
('B4', 'Blok B4', 20, NOW()),
('B5', 'Blok B5', 20, NOW()),
('C1', 'Blok C1', 20, NOW()),
('C2', 'Blok C2', 20, NOW()),
('C3', 'Blok C3', 20, NOW()),
('C4', 'Blok C4', 20, NOW()),
('C5', 'Blok C5', 20, NOW()),
('D1', 'Blok D1', 20, NOW()),
('D2', 'Blok D2', 20, NOW()),
('D3', 'Blok D3', 20, NOW()),
('D4', 'Blok D4', 20, NOW()),
('D5', 'Blok D5', 20, NOW()),
('E1', 'Blok E1', 20, NOW()),
('E2', 'Blok E2', 20, NOW()),
('E3', 'Blok E3', 20, NOW()),
('E4', 'Blok E4', 20, NOW()),
('E5', 'Blok E5', 20, NOW()),
('F1', 'Blok F1', 20, NOW()),
('F2', 'Blok F2', 20, NOW()),
('F3', 'Blok F3', 20, NOW()),
('F4', 'Blok F4', 20, NOW()),
('F5', 'Blok F5', 20, NOW());

-- 3. Index untuk query KPI, antrean FIFO, dan login supir
ALTER TABLE `queue_transactions`
  ADD KEY `idx_status_created` (`status`, `created_at`),
  ADD KEY `idx_gate_in_at` (`gate_in_at`),
  ADD KEY `idx_gate_out_at` (`gate_out_at`),
  ADD KEY `idx_driver_phone` (`driver_phone`),
  ADD KEY `idx_license_plate` (`license_plate`);

ALTER TABLE `sos_reports`
  ADD KEY `idx_resolved` (`is_resolved`);

-- 4. Relasi antar tabel (index fk_zone & fk_queue sudah ada, sekarang dijadikan foreign key)
ALTER TABLE `queue_transactions`
  ADD CONSTRAINT `fk_queue_zone` FOREIGN KEY (`current_zone_id`) REFERENCES `yard_zones` (`id`);

ALTER TABLE `sos_reports`
  ADD CONSTRAINT `fk_sos_queue` FOREIGN KEY (`queue_id`) REFERENCES `queue_transactions` (`id`);

-- 5. Rapikan data lama
-- Nopol disamakan dengan format baru aplikasi (contoh: L 9999 UX)
UPDATE `queue_transactions` SET `license_plate` = 'L 9999 UX' WHERE `license_plate` = 'L9999UX';

-- Antrean WAITING_GATE lebih dari 12 jam tanpa Gate In ditandai CANCELLED
-- (aplikasi juga sudah menganggapnya kedaluwarsa, ini hanya merapikan data)
UPDATE `queue_transactions`
SET `status` = 'CANCELLED'
WHERE `status` = 'WAITING_GATE' AND `created_at` < NOW() - INTERVAL 12 HOUR;

-- (Opsional) Truk yang tertahan berstatus IN_YARD / AT_YARD lebih dari 1 hari
-- akan selalu tampil "Over SLA". Hapus tanda komentar di bawah jika ingin ditutup.
-- UPDATE `queue_transactions`
-- SET `status` = 'CANCELLED'
-- WHERE `status` IN ('IN_YARD', 'AT_YARD') AND `gate_in_at` < NOW() - INTERVAL 1 DAY;
