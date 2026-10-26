-- =====================================================================
-- LASMONO - Upgrade 03: pembagian blok yard per principal (pelayaran)
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5. Jalankan SEKALI pada lasmono_db
-- setelah upgrade_02_kitir_timer.sql. Backup (Ekspor) terlebih dahulu.
-- =====================================================================

ALTER TABLE `yard_zones`
  ADD `principal` varchar(30) DEFAULT NULL AFTER `depo`,
  ADD `principal_color` varchar(7) DEFAULT NULL AFTER `principal`;

-- Depo 1: A1-C3 = ONE (magenta), A4-C5 = Wan Hai Lines (biru tua)
UPDATE `yard_zones` SET `principal` = 'ONE', `principal_color` = '#e4007f'
WHERE LEFT(`zone_code`, 1) IN ('A', 'B', 'C') AND SUBSTRING(`zone_code`, 2) IN ('1', '2', '3');
UPDATE `yard_zones` SET `principal` = 'WAN HAI', `principal_color` = '#0b3d91'
WHERE LEFT(`zone_code`, 1) IN ('A', 'B', 'C') AND SUBSTRING(`zone_code`, 2) IN ('4', '5');

-- Depo 2: D1-F3 = KMTC Lines (biru tua), D4-F5 = CMA CGM (biru tua)
UPDATE `yard_zones` SET `principal` = 'KMTC', `principal_color` = '#1f3f8f'
WHERE LEFT(`zone_code`, 1) IN ('D', 'E', 'F') AND SUBSTRING(`zone_code`, 2) IN ('1', '2', '3');
UPDATE `yard_zones` SET `principal` = 'CMA CGM', `principal_color` = '#041e42'
WHERE LEFT(`zone_code`, 1) IN ('D', 'E', 'F') AND SUBSTRING(`zone_code`, 2) IN ('4', '5');
