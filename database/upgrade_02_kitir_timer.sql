-- =====================================================================
-- LASMONO - Upgrade 02: data kitir (new_sby), survey Yardman 2x, depo per blok
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada database lasmono_db
-- setelah upgrade_lasmono_db.sql. Backup (Ekspor) terlebih dahulu.
-- =====================================================================

-- 1. Status IN_SURVEY (Yardman scan 1 = masuk area survey, scan 2 = selesai survey)
--    + data container dari kitir disimpan saat Gate In
ALTER TABLE `queue_transactions`
  MODIFY `status` enum('WAITING_GATE','IN_YARD','IN_SURVEY','AT_YARD','COMPLETED','CANCELLED') NOT NULL DEFAULT 'WAITING_GATE',
  ADD `cp_id` bigint(20) DEFAULT NULL AFTER `current_zone_id`,
  ADD `container_no` varchar(15) DEFAULT NULL AFTER `cp_id`,
  ADD `principal` varchar(20) DEFAULT NULL AFTER `container_no`,
  ADD `container_size` varchar(30) DEFAULT NULL AFTER `principal`,
  ADD `customer` varchar(100) DEFAULT NULL AFTER `container_size`,
  ADD `survey_start_by` int(11) DEFAULT NULL AFTER `gate_in_by`,
  ADD `survey_start_at` datetime DEFAULT NULL AFTER `gate_in_at`,
  ADD KEY `idx_cp_id` (`cp_id`);

-- 2. Pembagian blok per depo (sesuaikan dengan kondisi lapangan)
ALTER TABLE `yard_zones`
  ADD `depo` varchar(20) NOT NULL DEFAULT 'Depo 1' AFTER `zone_code`;

UPDATE `yard_zones` SET `depo` = 'Depo 1' WHERE LEFT(`zone_code`, 1) IN ('A', 'B', 'C');
UPDATE `yard_zones` SET `depo` = 'Depo 2' WHERE LEFT(`zone_code`, 1) IN ('D', 'E', 'F');
