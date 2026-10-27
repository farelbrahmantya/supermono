-- =====================================================================
-- LASMONO - Struktur database lengkap (instalasi baru / laptop demo)
-- Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5.
-- Untuk database yang sudah berjalan, gunakan upgrade_lasmono_db.sql, upgrade_02_kitir_timer.sql, lalu upgrade_03_principal_blok.sql.
-- Login petugas: yardman1 / admin_ct / gate_in / gate_out, password: password123
-- =====================================================================

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('GATE_IN','GATE_OUT','YARDMAN','CONTROL_TOWER') NOT NULL DEFAULT 'YARDMAN',
  `source` enum('LOCAL','NEW_GENERAL') NOT NULL DEFAULT 'LOCAL',
  `ext_user_id` int(11) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_source_ext` (`source`, `ext_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `yard_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_code` varchar(10) NOT NULL,
  `depo` varchar(20) NOT NULL DEFAULT 'Depo 1',
  `principal` varchar(30) DEFAULT NULL,
  `principal_color` varchar(7) DEFAULT NULL,
  `zone_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT '20',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_zone_code` (`zone_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `queue_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `queue_number` varchar(20) NOT NULL,
  `driver_phone` varchar(20) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `status` enum('WAITING_GATE','IN_YARD','IN_SURVEY','AT_YARD','COMPLETED','CANCELLED') NOT NULL DEFAULT 'WAITING_GATE',
  `current_zone_id` int(11) DEFAULT NULL,
  `cp_id` bigint(20) DEFAULT NULL,
  `activity` char(1) NOT NULL DEFAULT 'Z',
  `driver_activity` char(1) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT '0',
  `container_no` varchar(15) DEFAULT NULL,
  `principal` varchar(20) DEFAULT NULL,
  `container_size` varchar(30) DEFAULT NULL,
  `customer` varchar(100) DEFAULT NULL,
  `sla_target_minutes` int(11) NOT NULL DEFAULT '15',
  `gate_in_by` int(11) DEFAULT NULL,
  `survey_start_by` int(11) DEFAULT NULL,
  `yard_scan_by` int(11) DEFAULT NULL,
  `gate_out_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `gate_in_at` datetime DEFAULT NULL,
  `survey_start_at` datetime DEFAULT NULL,
  `yard_scan_at` datetime DEFAULT NULL,
  `gate_out_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_queue_number` (`queue_number`),
  KEY `fk_zone` (`current_zone_id`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_gate_in_at` (`gate_in_at`),
  KEY `idx_gate_out_at` (`gate_out_at`),
  KEY `idx_driver_phone` (`driver_phone`),
  KEY `idx_license_plate` (`license_plate`),
  KEY `idx_cp_id` (`cp_id`),
  KEY `idx_container_no` (`container_no`),
  KEY `idx_is_demo` (`is_demo`),
  CONSTRAINT `fk_queue_zone` FOREIGN KEY (`current_zone_id`) REFERENCES `yard_zones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `lasmono_settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sos_reports` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `queue_id` bigint(20) NOT NULL,
  `report_type` enum('PUNGLI','PENYEROBOTAN','LAINNYA') NOT NULL DEFAULT 'PUNGLI',
  `description` text,
  `media_path` varchar(255) DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_queue` (`queue_id`),
  KEY `idx_resolved` (`is_resolved`),
  CONSTRAINT `fk_sos_queue` FOREIGN KEY (`queue_id`) REFERENCES `queue_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Yardman', 'yardman1', '$2a$10$s.JxmO8sGJMbMWaXZGUaEOQLDBE4v9/ixnUpB9NkHBzcqu.6qRHQy', 'YARDMAN', NOW()),
(2, 'Supervisor Control Tower', 'admin_ct', '$2a$10$YV9AonYqCje045qqtoU1KeNmW85yn.ZrEtb2G42NH52czGcAHkmTe', 'CONTROL_TOWER', NOW()),
(3, 'Security Gate In', 'gate_in', '$2a$10$xMMGFJunUxU7qpo9Lkq52OZtIHMu/2FqaoUBSRXkSiDgfzUs9GxBG', 'GATE_IN', NOW()),
(4, 'Security Gate Out', 'gate_out', '$2a$10$/LdGNFVlBfGiLWxZhrwqFemdja41nZ8ONzI.ct8QkYmOF1HXK2MP2', 'GATE_OUT', NOW());

INSERT INTO `yard_zones` (`zone_code`, `depo`, `principal`, `principal_color`, `zone_name`, `capacity`, `created_at`) VALUES
('A1', 'Depo 1', 'ONE', '#e4007f', 'Blok A1', 20, NOW()),
('A2', 'Depo 1', 'ONE', '#e4007f', 'Blok A2', 20, NOW()),
('A3', 'Depo 1', 'ONE', '#e4007f', 'Blok A3', 20, NOW()),
('A4', 'Depo 1', 'WAN HAI', '#0b3d91', 'Blok A4', 20, NOW()),
('A5', 'Depo 1', 'WAN HAI', '#0b3d91', 'Blok A5', 20, NOW()),
('B1', 'Depo 1', 'ONE', '#e4007f', 'Blok B1', 20, NOW()),
('B2', 'Depo 1', 'ONE', '#e4007f', 'Blok B2', 20, NOW()),
('B3', 'Depo 1', 'ONE', '#e4007f', 'Blok B3', 20, NOW()),
('B4', 'Depo 1', 'WAN HAI', '#0b3d91', 'Blok B4', 20, NOW()),
('B5', 'Depo 1', 'WAN HAI', '#0b3d91', 'Blok B5', 20, NOW()),
('C1', 'Depo 1', 'ONE', '#e4007f', 'Blok C1', 20, NOW()),
('C2', 'Depo 1', 'ONE', '#e4007f', 'Blok C2', 20, NOW()),
('C3', 'Depo 1', 'ONE', '#e4007f', 'Blok C3', 20, NOW()),
('C4', 'Depo 1', 'WAN HAI', '#0b3d91', 'Blok C4', 20, NOW()),
('C5', 'Depo 1', 'WAN HAI', '#0b3d91', 'Blok C5', 20, NOW()),
('D1', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok D1', 20, NOW()),
('D2', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok D2', 20, NOW()),
('D3', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok D3', 20, NOW()),
('D4', 'Depo 2', 'CMA CGM', '#041e42', 'Blok D4', 20, NOW()),
('D5', 'Depo 2', 'CMA CGM', '#041e42', 'Blok D5', 20, NOW()),
('E1', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok E1', 20, NOW()),
('E2', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok E2', 20, NOW()),
('E3', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok E3', 20, NOW()),
('E4', 'Depo 2', 'CMA CGM', '#041e42', 'Blok E4', 20, NOW()),
('E5', 'Depo 2', 'CMA CGM', '#041e42', 'Blok E5', 20, NOW()),
('F1', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok F1', 20, NOW()),
('F2', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok F2', 20, NOW()),
('F3', 'Depo 2', 'KMTC', '#1f3f8f', 'Blok F3', 20, NOW()),
('F4', 'Depo 2', 'CMA CGM', '#041e42', 'Blok F4', 20, NOW()),
('F5', 'Depo 2', 'CMA CGM', '#041e42', 'Blok F5', 20, NOW());
