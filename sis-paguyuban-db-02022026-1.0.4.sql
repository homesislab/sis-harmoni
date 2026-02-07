/*
 Navicat Premium Data Transfer

 Source Server         : Localhost
 Source Server Type    : MySQL
 Source Server Version : 80019 (8.0.19)
 Source Host           : localhost:3307
 Source Schema         : sis_harmoni

 Target Server Type    : MySQL
 Target Server Version : 80019 (8.0.19)
 File Encoding         : 65001

 Date: 02/02/2026 15:03:12
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for audit_logs
-- ----------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `action` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user` (`user_id`),
  KEY `idx_audit_logs_action` (`action`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of audit_logs
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for charge_components
-- ----------------------------
DROP TABLE IF EXISTS `charge_components`;
CREATE TABLE `charge_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `charge_type_id` bigint unsigned NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sort_order` int NOT NULL DEFAULT '1',
  `ledger_account_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_charge_components_charge_type` (`charge_type_id`),
  KEY `idx_charge_components_ledger` (`ledger_account_id`),
  KEY `idx_charge_components_sort` (`charge_type_id`,`sort_order`),
  KEY `idx_charge_components_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_charge_components_charge_type` FOREIGN KEY (`charge_type_id`) REFERENCES `charge_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_charge_components_ledger` FOREIGN KEY (`ledger_account_id`) REFERENCES `ledger_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of charge_components
-- ----------------------------
BEGIN;
INSERT INTO `charge_components` (`id`, `charge_type_id`, `name`, `amount`, `sort_order`, `ledger_account_id`, `created_at`, `deleted_at`) VALUES (1, 1, 'Keamanan', 70000.00, 1, 1, '2026-02-02 12:27:11', NULL);
INSERT INTO `charge_components` (`id`, `charge_type_id`, `name`, `amount`, `sort_order`, `ledger_account_id`, `created_at`, `deleted_at`) VALUES (2, 1, 'Kebersihan', 20000.00, 2, 1, '2026-02-02 12:27:40', NULL);
COMMIT;

-- ----------------------------
-- Table structure for charge_types
-- ----------------------------
DROP TABLE IF EXISTS `charge_types`;
CREATE TABLE `charge_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('paguyuban','dkm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_periodic` tinyint(1) NOT NULL DEFAULT '1',
  `period_unit` enum('monthly','weekly','once') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_charge_types_category` (`category`),
  KEY `idx_charge_types_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of charge_types
-- ----------------------------
BEGIN;
INSERT INTO `charge_types` (`id`, `name`, `category`, `is_periodic`, `period_unit`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'IPL (Iuran Pengelolaan Lingkungan)', 'paguyuban', 1, 'monthly', 1, '2026-02-02 12:26:58', '2026-02-02 12:26:58');
COMMIT;

-- ----------------------------
-- Table structure for emergency_reports
-- ----------------------------
DROP TABLE IF EXISTS `emergency_reports`;
CREATE TABLE `emergency_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reporter_person_id` bigint unsigned DEFAULT NULL,
  `house_id` bigint unsigned DEFAULT NULL,
  `type` enum('medical','fire','crime','accident','lost_child','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('open','acknowledged','resolved','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emergency_reports_house_id` (`house_id`),
  KEY `idx_emergency_reports_reporter_person_id` (`reporter_person_id`),
  KEY `idx_emergency_reports_type` (`type`),
  KEY `idx_emergency_reports_status` (`status`),
  KEY `idx_emergency_reports_created_at` (`created_at`),
  KEY `fk_emergency_reports_ack_by` (`acknowledged_by`),
  KEY `fk_emergency_reports_resolved_by` (`resolved_by`),
  KEY `fk_emergency_reports_created_by` (`created_by`),
  CONSTRAINT `fk_emergency_reports_ack_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_emergency_reports_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_emergency_reports_house_id` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_emergency_reports_reporter_person_id` FOREIGN KEY (`reporter_person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_emergency_reports_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of emergency_reports
-- ----------------------------
BEGIN;
INSERT INTO `emergency_reports` (`id`, `reporter_person_id`, `house_id`, `type`, `description`, `location_text`, `contact_phone`, `status`, `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`, `resolution_note`, `created_by`, `created_at`, `updated_at`) VALUES (1, 3, 140, 'crime', 'Butuh bantuan keamanan.', NULL, NULL, 'open', 1, '2026-01-21 18:31:15', NULL, NULL, NULL, 2, '2026-01-21 18:27:31', '2026-01-22 01:31:27');
INSERT INTO `emergency_reports` (`id`, `reporter_person_id`, `house_id`, `type`, `description`, `location_text`, `contact_phone`, `status`, `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`, `resolution_note`, `created_by`, `created_at`, `updated_at`) VALUES (2, 3, 140, 'medical', 'Butuh bantuan kesehatan.', NULL, NULL, 'resolved', 1, '2026-01-22 03:05:29', 1, '2026-01-22 03:06:34', 'Laporan telah ditindaklanjuti oleh tim keamanan dan warga sekitar. Pelapor sudah mendapatkan bantuan awal dan kondisi saat ini terpantau stabil. Keluarga juga sudah mendampingi. Disarankan untuk segera melanjutkan pemeriksaan ke fasilitas kesehatan terdekat jika keluhan berlanjut. Laporan dinyatakan selesai.', 2, '2026-01-22 02:59:58', '2026-01-22 03:06:34');
INSERT INTO `emergency_reports` (`id`, `reporter_person_id`, `house_id`, `type`, `description`, `location_text`, `contact_phone`, `status`, `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`, `resolution_note`, `created_by`, `created_at`, `updated_at`) VALUES (3, 3, 140, 'fire', 'Ada risiko kebakaran.', NULL, NULL, 'acknowledged', 1, '2026-01-22 03:05:23', NULL, NULL, NULL, 2, '2026-01-22 03:00:04', '2026-01-22 03:05:23');
INSERT INTO `emergency_reports` (`id`, `reporter_person_id`, `house_id`, `type`, `description`, `location_text`, `contact_phone`, `status`, `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`, `resolution_note`, `created_by`, `created_at`, `updated_at`) VALUES (4, 3, 140, 'other', 'Butuh bantuan segera.', NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL, NULL, 2, '2026-01-22 03:00:06', '2026-01-22 03:00:25');
COMMIT;

-- ----------------------------
-- Table structure for events
-- ----------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_at` datetime NOT NULL,
  `org` enum('paguyuban','dkm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_event_at` (`event_at`),
  KEY `idx_events_org` (`org`),
  KEY `idx_events_created_by` (`created_by`),
  CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of events
-- ----------------------------
BEGIN;
INSERT INTO `events` (`id`, `title`, `event_at`, `org`, `image_url`, `description`, `location`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'Pengajian Akbar Isra Mi’raj', '2026-02-01 19:30:00', 'dkm', 'http://sis-harmoni.test/uploads/images/20260126_120158_f613d5c6c1_aboodi-vesakaran-37l1mIhiX-Y-unsplash.webp', 'Pengajian akbar dalam rangka peringatan Isra Mi’raj Nabi Muhammad SAW. Acara akan diisi dengan tausiyah dan doa bersama.', 'Masjid Al-Nufais', 1, '2026-01-26 19:01:58', '2026-01-26 19:01:58');
INSERT INTO `events` (`id`, `title`, `event_at`, `org`, `image_url`, `description`, `location`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'Kerja Bakti Bersih Masjid', '2026-01-28 07:00:00', 'dkm', 'http://sis-harmoni.test/uploads/images/20260126_120330_227a7f46bf_masjid-maba-D7cFwSum3qo-unsplash.webp', 'Kegiatan gotong royong membersihkan area masjid dan sekitarnya. Diharapkan seluruh jamaah dapat berpartisipasi.', 'Masjid Al-Nufais', 1, '2026-01-26 19:03:31', '2026-01-26 19:03:31');
INSERT INTO `events` (`id`, `title`, `event_at`, `org`, `image_url`, `description`, `location`, `created_by`, `created_at`, `updated_at`) VALUES (3, 'Lomba Anak & Keluarga Akhir Pekan', '2026-02-02 10:00:00', 'paguyuban', 'http://sis-harmoni.test/uploads/images/20260126_120507_ad975cf403_hoi-an-and-da-nang-photographer-m9Psn4t89xc-unsplash.webp', 'Kegiatan lomba santai untuk anak dan keluarga guna mempererat kebersamaan antarwarga.', 'Area Taman Anak', 1, '2026-01-26 19:05:07', '2026-01-26 19:05:07');
COMMIT;

-- ----------------------------
-- Table structure for feedback_categories
-- ----------------------------
DROP TABLE IF EXISTS `feedback_categories`;
CREATE TABLE `feedback_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_feedback_categories_code` (`code`),
  KEY `idx_feedback_categories_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of feedback_categories
-- ----------------------------
BEGIN;
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1, 'umum', 'Umum', 1, 1, '2026-01-22 00:46:30', '2026-01-22 00:46:30');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (2, 'keamanan', 'Keamanan', 1, 2, '2026-01-22 00:46:30', '2026-01-22 00:46:41');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (3, 'kebersihan', 'Kebersihan', 1, 3, '2026-01-22 00:46:30', '2026-01-22 00:46:42');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (4, 'fasilitas', 'Fasilitas', 1, 4, '2026-01-22 00:46:30', '2026-01-22 00:46:43');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (5, 'keuangan', 'Keuangan', 1, 5, '2026-01-22 00:46:30', '2026-01-22 00:46:43');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (6, 'layanan', 'Layanan', 1, 6, '2026-01-22 00:46:30', '2026-01-22 00:46:44');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (7, 'kegiatan', 'Kegiatan', 1, 7, '2026-01-22 00:46:30', '2026-01-22 00:46:44');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (8, 'keagamaan', 'Keagamaan', 1, 8, '2026-01-22 00:46:30', '2026-01-22 00:46:45');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (9, 'sosial', 'Sosial', 1, 9, '2026-01-22 00:46:30', '2026-01-22 00:46:45');
INSERT INTO `feedback_categories` (`id`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (10, 'darurat', 'Darurat', 1, 10, '2026-01-22 00:46:30', '2026-01-22 00:46:46');
COMMIT;

-- ----------------------------
-- Table structure for feedback_responses
-- ----------------------------
DROP TABLE IF EXISTS `feedback_responses`;
CREATE TABLE `feedback_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feedback_id` bigint unsigned NOT NULL,
  `responder_id` bigint unsigned DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_responses_feedback_id` (`feedback_id`),
  KEY `idx_feedback_responses_responder_id` (`responder_id`),
  CONSTRAINT `fk_feedback_responses_feedback_id` FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_responses_responder_id` FOREIGN KEY (`responder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of feedback_responses
-- ----------------------------
BEGIN;
INSERT INTO `feedback_responses` (`id`, `feedback_id`, `responder_id`, `message`, `is_public`, `created_at`, `updated_at`) VALUES (1, 1, 1, 'Terima kasih atas masukannya. Kami sudah mencatat laporan terkait penerangan taman yang kurang terang. Saat ini tim fasilitas akan melakukan pengecekan kondisi lampu dan jalur listrik di area tersebut. Jika diperlukan, akan dilakukan penambahan atau penggantian lampu agar taman lebih aman dan nyaman digunakan pada malam hari. Mohon kesabarannya, dan terima kasih atas perhatiannya.', 1, '2026-01-21 18:06:35', '2026-01-21 18:06:35');
COMMIT;

-- ----------------------------
-- Table structure for feedbacks
-- ----------------------------
DROP TABLE IF EXISTS `feedbacks`;
CREATE TABLE `feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `person_id` bigint unsigned DEFAULT NULL,
  `house_id` bigint unsigned DEFAULT NULL,
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','in_review','responded','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_by` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedbacks_category_id` (`category_id`),
  KEY `idx_feedbacks_person_id` (`person_id`),
  KEY `idx_feedbacks_house_id` (`house_id`),
  KEY `idx_feedbacks_status` (`status`),
  KEY `idx_feedbacks_created_at` (`created_at`),
  KEY `fk_feedbacks_created_by` (`created_by`),
  KEY `fk_feedbacks_assigned_to` (`assigned_to`),
  KEY `fk_feedbacks_closed_by` (`closed_by`),
  CONSTRAINT `fk_feedbacks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedbacks_category_id` FOREIGN KEY (`category_id`) REFERENCES `feedback_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedbacks_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedbacks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedbacks_house_id` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedbacks_person_id` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of feedbacks
-- ----------------------------
BEGIN;
INSERT INTO `feedbacks` (`id`, `category_id`, `person_id`, `house_id`, `title`, `message`, `status`, `created_by`, `assigned_to`, `closed_by`, `closed_at`, `created_at`, `updated_at`) VALUES (1, 4, 3, 140, 'Penerangan Taman Kurang Terang', 'Lampu di area taman utama terlihat kurang terang pada malam hari. Mohon dapat ditambahkan penerangan atau dilakukan pengecekan agar area lebih aman dan nyaman digunakan warga, terutama anak-anak.', 'responded', 2, NULL, NULL, NULL, '2026-01-21 17:47:55', '2026-01-22 01:06:35');
INSERT INTO `feedbacks` (`id`, `category_id`, `person_id`, `house_id`, `title`, `message`, `status`, `created_by`, `assigned_to`, `closed_by`, `closed_at`, `created_at`, `updated_at`) VALUES (2, 6, 3, 140, 'Respon Pengajuan Cukup Lama', 'Saya sudah mengajukan permohonan melalui aplikasi beberapa waktu lalu, namun belum mendapat respon. Mungkin bisa ditambahkan informasi estimasi waktu respon agar warga mengetahui proses yang sedang berjalan.', 'open', 2, NULL, 1, '2026-01-21 18:04:52', '2026-01-21 17:48:19', '2026-01-22 01:05:41');
COMMIT;

-- ----------------------------
-- Table structure for fundraiser_donations
-- ----------------------------
DROP TABLE IF EXISTS `fundraiser_donations`;
CREATE TABLE `fundraiser_donations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fundraiser_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  `ledger_entry_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `paid_at` datetime NOT NULL,
  `proof_file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fundraiser_donations_fundraiser` (`fundraiser_id`),
  KEY `idx_fundraiser_donations_person` (`person_id`),
  KEY `idx_fundraiser_donations_ledger_entry` (`ledger_entry_id`),
  KEY `idx_fundraiser_donations_status` (`status`),
  KEY `idx_fundraiser_donations_is_anonymous` (`is_anonymous`),
  KEY `idx_fundraiser_donations_paid_at` (`paid_at`),
  KEY `fk_fundraiser_donations_verified_by` (`verified_by`),
  CONSTRAINT `fk_fundraiser_donations_fundraiser` FOREIGN KEY (`fundraiser_id`) REFERENCES `fundraisers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fundraiser_donations_ledger_entry` FOREIGN KEY (`ledger_entry_id`) REFERENCES `ledger_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fundraiser_donations_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fundraiser_donations_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of fundraiser_donations
-- ----------------------------
BEGIN;
INSERT INTO `fundraiser_donations` (`id`, `fundraiser_id`, `person_id`, `ledger_entry_id`, `amount`, `paid_at`, `proof_file_url`, `note`, `is_anonymous`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES (1, 1, 3, NULL, 1000000.00, '2026-01-28 09:36:00', 'http://sis-harmoni.test/uploads/images/20260128_023736_cb79385dc0_bukti_transfer_trf__1577263390_4882166a.jpg', NULL, 0, 'approved', 1, '2026-01-28 03:21:59', '2026-01-28 09:37:36');
INSERT INTO `fundraiser_donations` (`id`, `fundraiser_id`, `person_id`, `ledger_entry_id`, `amount`, `paid_at`, `proof_file_url`, `note`, `is_anonymous`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES (2, 1, 3, NULL, 500000.00, '2026-01-29 14:11:00', NULL, NULL, 1, 'pending', NULL, NULL, '2026-01-29 14:11:42');
COMMIT;

-- ----------------------------
-- Table structure for fundraiser_updates
-- ----------------------------
DROP TABLE IF EXISTS `fundraiser_updates`;
CREATE TABLE `fundraiser_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fundraiser_id` bigint unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments_json` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fundraiser_updates_fundraiser` (`fundraiser_id`),
  KEY `idx_fundraiser_updates_created_by` (`created_by`),
  CONSTRAINT `fk_fundraiser_updates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fundraiser_updates_fundraiser` FOREIGN KEY (`fundraiser_id`) REFERENCES `fundraisers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of fundraiser_updates
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for fundraisers
-- ----------------------------
DROP TABLE IF EXISTS `fundraisers`;
CREATE TABLE `fundraisers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `target_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `collected_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `category` enum('paguyuban','dkm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fundraisers_status` (`status`),
  KEY `idx_fundraisers_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of fundraisers
-- ----------------------------
BEGIN;
INSERT INTO `fundraisers` (`id`, `title`, `description`, `target_amount`, `collected_amount`, `status`, `category`, `created_at`, `updated_at`) VALUES (1, 'Donasi Perbaikan Penerangan Jalan Lingkungan', 'Penggalangan dana untuk perbaikan dan penambahan lampu penerangan jalan di beberapa titik yang saat ini masih kurang terang, demi meningkatkan keamanan dan kenyamanan warga.', 25000000.00, 1000000.00, 'active', 'paguyuban', '2026-01-26 19:29:12', '2026-01-28 10:21:59');
COMMIT;

-- ----------------------------
-- Table structure for guest_visits
-- ----------------------------
DROP TABLE IF EXISTS `guest_visits`;
CREATE TABLE `guest_visits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `house_id` bigint unsigned DEFAULT NULL,
  `host_person_id` bigint unsigned DEFAULT NULL,
  `destination_type` enum('unit','non_unit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `destination_label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visitor_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_count` int unsigned NOT NULL DEFAULT '1',
  `vehicle_plate` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_at` datetime NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('checked_in','checked_out') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'checked_in',
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_guest_visits_house_id` (`house_id`),
  KEY `idx_guest_visits_host_person_id` (`host_person_id`),
  KEY `idx_guest_visits_status` (`status`),
  KEY `idx_guest_visits_visit_at` (`visit_at`),
  KEY `idx_guest_visits_created_by` (`created_by`),
  KEY `idx_guest_visits_approved_by` (`approved_by`),
  CONSTRAINT `fk_guest_visits_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_guest_visits_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_guest_visits_host_person_id` FOREIGN KEY (`host_person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_guest_visits_house_id` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of guest_visits
-- ----------------------------
BEGIN;
INSERT INTO `guest_visits` (`id`, `house_id`, `host_person_id`, `destination_type`, `destination_label`, `visitor_name`, `visitor_phone`, `purpose`, `visitor_count`, `vehicle_plate`, `visit_at`, `note`, `status`, `created_by`, `approved_by`, `approved_at`, `checked_in_at`, `checked_out_at`, `created_at`, `updated_at`) VALUES (13, 140, 3, 'unit', NULL, 'Bpk. Ahmad Fauzi', '081234567890', 'Bertamu / silaturahmi', 2, 'D 1234 ABC', '2026-01-26 16:00:00', NULL, 'checked_out', 1, NULL, NULL, NULL, '2026-01-28 13:49:15', '2026-01-26 10:15:00', '2026-01-29 15:15:31');
INSERT INTO `guest_visits` (`id`, `house_id`, `host_person_id`, `destination_type`, `destination_label`, `visitor_name`, `visitor_phone`, `purpose`, `visitor_count`, `vehicle_plate`, `visit_at`, `note`, `status`, `created_by`, `approved_by`, `approved_at`, `checked_in_at`, `checked_out_at`, `created_at`, `updated_at`) VALUES (15, 140, 3, 'unit', NULL, 'Bpk. Budi Santoso', '085677889900', 'Bertamu / silaturahmi', 3, 'B 9876 XYZ', '2026-01-26 18:00:00', NULL, 'checked_out', 1, 1, '2026-01-26 17:30:00', '2026-01-26 18:05:00', '2026-01-28 04:15:52', '2026-01-26 17:20:00', '2026-01-29 15:15:28');
INSERT INTO `guest_visits` (`id`, `house_id`, `host_person_id`, `destination_type`, `destination_label`, `visitor_name`, `visitor_phone`, `purpose`, `visitor_count`, `vehicle_plate`, `visit_at`, `note`, `status`, `created_by`, `approved_by`, `approved_at`, `checked_in_at`, `checked_out_at`, `created_at`, `updated_at`) VALUES (17, 140, NULL, 'unit', NULL, 'Bpk. Wawan Gunawan', NULL, 'Bertamu / silaturahmi', 2, 'D 4584 YBU', '2026-01-29 05:48:01', 'Keluarga', 'checked_in', 1, NULL, NULL, NULL, NULL, '2026-01-29 05:48:01', '2026-01-29 15:04:01');
INSERT INTO `guest_visits` (`id`, `house_id`, `host_person_id`, `destination_type`, `destination_label`, `visitor_name`, `visitor_phone`, `purpose`, `visitor_count`, `vehicle_plate`, `visit_at`, `note`, `status`, `created_by`, `approved_by`, `approved_at`, `checked_in_at`, `checked_out_at`, `created_at`, `updated_at`) VALUES (19, NULL, NULL, 'non_unit', 'Marketing Perumahan', 'Ibu Yuliana', '0857467263423', 'Survey / lihat unit', 2, 'D 4745 RIU', '2026-01-29 06:36:18', NULL, 'checked_in', 1, NULL, NULL, '2026-01-29 06:36:18', NULL, '2026-01-29 06:36:18', '2026-01-29 13:36:47');
COMMIT;

-- ----------------------------
-- Table structure for house_claims
-- ----------------------------
DROP TABLE IF EXISTS `house_claims`;
CREATE TABLE `house_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `house_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  `claim_type` enum('owner','tenant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_type` enum('house','kavling') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','approved','rejected','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `source` enum('registration','additional') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'additional',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reject_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_house_claims_house` (`house_id`),
  KEY `idx_house_claims_person` (`person_id`),
  KEY `idx_house_claims_status` (`status`),
  KEY `idx_house_claims_requested_at` (`requested_at`),
  KEY `fk_house_claims_reviewed_by` (`reviewed_by`),
  KEY `idx_house_claims_house_status` (`house_id`,`status`),
  KEY `idx_house_claims_source_status` (`source`,`status`),
  CONSTRAINT `fk_house_claims_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_house_claims_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_house_claims_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of house_claims
-- ----------------------------
BEGIN;
INSERT INTO `house_claims` (`id`, `house_id`, `person_id`, `claim_type`, `unit_type`, `is_primary`, `status`, `source`, `requested_at`, `reviewed_by`, `reviewed_at`, `note`, `reject_note`) VALUES (1, 140, 3, 'owner', 'house', 1, 'approved', 'registration', '2026-01-21 09:48:36', 1, '2026-01-24 03:08:00', 'Approved', NULL);
INSERT INTO `house_claims` (`id`, `house_id`, `person_id`, `claim_type`, `unit_type`, `is_primary`, `status`, `source`, `requested_at`, `reviewed_by`, `reviewed_at`, `note`, `reject_note`) VALUES (8, 88, 13, 'owner', 'house', 1, 'approved', 'registration', '2026-01-23 16:50:31', 1, '2026-01-28 08:12:26', 'Approved', NULL);
INSERT INTO `house_claims` (`id`, `house_id`, `person_id`, `claim_type`, `unit_type`, `is_primary`, `status`, `source`, `requested_at`, `reviewed_by`, `reviewed_at`, `note`, `reject_note`) VALUES (9, 89, 13, 'owner', 'house', 0, 'approved', 'registration', '2026-01-23 16:50:31', 1, '2026-01-28 08:29:36', 'Approved', NULL);
INSERT INTO `house_claims` (`id`, `house_id`, `person_id`, `claim_type`, `unit_type`, `is_primary`, `status`, `source`, `requested_at`, `reviewed_by`, `reviewed_at`, `note`, `reject_note`) VALUES (10, 90, 13, 'owner', 'kavling', 0, 'pending', 'additional', '2026-01-23 16:50:31', 1, '2026-01-24 08:09:24', 'Approved', NULL);
INSERT INTO `house_claims` (`id`, `house_id`, `person_id`, `claim_type`, `unit_type`, `is_primary`, `status`, `source`, `requested_at`, `reviewed_by`, `reviewed_at`, `note`, `reject_note`) VALUES (11, 116, 15, 'owner', 'house', 0, 'approved', 'registration', '2026-01-23 19:18:09', 1, '2026-01-23 20:06:12', 'Approved', NULL);
INSERT INTO `house_claims` (`id`, `house_id`, `person_id`, `claim_type`, `unit_type`, `is_primary`, `status`, `source`, `requested_at`, `reviewed_by`, `reviewed_at`, `note`, `reject_note`) VALUES (12, 116, 17, 'tenant', 'house', 1, 'approved', 'registration', '2026-01-23 20:10:39', 1, '2026-01-23 20:13:08', 'Approved', NULL);
COMMIT;

-- ----------------------------
-- Table structure for house_occupancies
-- ----------------------------
DROP TABLE IF EXISTS `house_occupancies`;
CREATE TABLE `house_occupancies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `house_id` bigint unsigned NOT NULL,
  `household_id` bigint unsigned DEFAULT NULL,
  `occupancy_type` enum('owner_live','tenant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','ended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_house_occupancies_house` (`house_id`),
  KEY `idx_house_occupancies_household` (`household_id`),
  KEY `idx_house_occupancies_house_status` (`house_id`,`status`),
  CONSTRAINT `fk_house_occupancies_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_house_occupancies_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of house_occupancies
-- ----------------------------
BEGIN;
INSERT INTO `house_occupancies` (`id`, `house_id`, `household_id`, `occupancy_type`, `start_date`, `end_date`, `status`, `note`, `created_at`) VALUES (1, 140, 2, 'owner_live', '2026-01-21', NULL, 'active', 'Onboarding', '2026-01-21 09:48:36');
INSERT INTO `house_occupancies` (`id`, `house_id`, `household_id`, `occupancy_type`, `start_date`, `end_date`, `status`, `note`, `created_at`) VALUES (7, 116, 8, 'tenant', '2026-01-23', NULL, 'active', 'Approved from registration', '2026-01-23 20:13:08');
INSERT INTO `house_occupancies` (`id`, `house_id`, `household_id`, `occupancy_type`, `start_date`, `end_date`, `status`, `note`, `created_at`) VALUES (8, 88, 6, 'owner_live', '2026-01-28', NULL, 'active', 'Approved from registration', '2026-01-28 08:12:26');
COMMIT;

-- ----------------------------
-- Table structure for house_ownerships
-- ----------------------------
DROP TABLE IF EXISTS `house_ownerships`;
CREATE TABLE `house_ownerships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `house_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_house_ownerships_house` (`house_id`),
  KEY `idx_house_ownerships_person` (`person_id`),
  KEY `idx_house_ownerships_start` (`start_date`),
  CONSTRAINT `fk_house_ownerships_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_house_ownerships_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of house_ownerships
-- ----------------------------
BEGIN;
INSERT INTO `house_ownerships` (`id`, `house_id`, `person_id`, `start_date`, `end_date`, `note`, `created_at`) VALUES (1, 116, 15, '2026-01-23', NULL, 'Approved from registration', '2026-01-23 20:06:12');
INSERT INTO `house_ownerships` (`id`, `house_id`, `person_id`, `start_date`, `end_date`, `note`, `created_at`) VALUES (3, 140, 3, '2026-01-24', NULL, 'Approved from registration', '2026-01-24 03:08:58');
INSERT INTO `house_ownerships` (`id`, `house_id`, `person_id`, `start_date`, `end_date`, `note`, `created_at`) VALUES (4, 90, 13, '2026-01-24', NULL, 'Created from approved claim', '2026-01-24 15:09:24');
INSERT INTO `house_ownerships` (`id`, `house_id`, `person_id`, `start_date`, `end_date`, `note`, `created_at`) VALUES (5, 88, 13, '2026-01-28', NULL, 'Approved from registration', '2026-01-28 08:12:26');
INSERT INTO `house_ownerships` (`id`, `house_id`, `person_id`, `start_date`, `end_date`, `note`, `created_at`) VALUES (6, 89, 13, '2026-01-28', NULL, 'Approved from registration', '2026-01-28 08:12:26');
COMMIT;

-- ----------------------------
-- Table structure for household_members
-- ----------------------------
DROP TABLE IF EXISTS `household_members`;
CREATE TABLE `household_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `household_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned NOT NULL,
  `relationship` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_household_members` (`household_id`,`person_id`),
  KEY `idx_household_members_household` (`household_id`),
  KEY `idx_household_members_person` (`person_id`),
  CONSTRAINT `fk_household_members_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_household_members_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of household_members
-- ----------------------------
BEGIN;
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (3, 2, 3, 'kepala_keluarga', '2026-01-21 16:48:35');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (4, 2, 4, 'istri', '2026-01-21 16:48:35');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (5, 2, 5, 'anak', '2026-01-21 16:48:35');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (6, 2, 6, 'anak', '2026-01-21 16:48:35');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (13, 6, 13, 'kepala_keluarga', '2026-01-23 23:50:31');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (14, 6, 14, 'anggota', '2026-01-23 23:50:31');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (15, 7, 15, 'kepala_keluarga', '2026-01-24 02:18:09');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (16, 7, 16, 'istri', '2026-01-24 02:18:09');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (17, 8, 17, 'kepala_keluarga', '2026-01-24 03:10:39');
INSERT INTO `household_members` (`id`, `household_id`, `person_id`, `relationship`, `created_at`) VALUES (18, 8, 18, 'istri', '2026-01-24 03:10:39');
COMMIT;

-- ----------------------------
-- Table structure for households
-- ----------------------------
DROP TABLE IF EXISTS `households`;
CREATE TABLE `households` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kk_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_person_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_households_kk` (`kk_number`),
  KEY `idx_households_head` (`head_person_id`),
  CONSTRAINT `fk_households_head_person` FOREIGN KEY (`head_person_id`) REFERENCES `persons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of households
-- ----------------------------
BEGIN;
INSERT INTO `households` (`id`, `kk_number`, `head_person_id`, `created_at`, `updated_at`) VALUES (2, '3204524079600001', 3, '2026-01-21 16:48:35', '2026-01-21 16:48:35');
INSERT INTO `households` (`id`, `kk_number`, `head_person_id`, `created_at`, `updated_at`) VALUES (6, '3276014501010001', 13, '2026-01-23 23:50:31', '2026-01-23 23:50:31');
INSERT INTO `households` (`id`, `kk_number`, `head_person_id`, `created_at`, `updated_at`) VALUES (7, '3276024501020002', 15, '2026-01-24 02:18:09', '2026-01-24 02:18:09');
INSERT INTO `households` (`id`, `kk_number`, `head_person_id`, `created_at`, `updated_at`) VALUES (8, '3276034501030003', 17, '2026-01-24 03:10:39', '2026-01-24 03:10:39');
COMMIT;

-- ----------------------------
-- Table structure for houses
-- ----------------------------
DROP TABLE IF EXISTS `houses`;
CREATE TABLE `houses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `block` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('house','kavling') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kavling',
  `status` enum('vacant','owned','occupied','rented','plot','unknown') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vacant',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_houses_code` (`code`),
  UNIQUE KEY `uk_houses_block_number` (`block`,`number`),
  KEY `idx_houses_status` (`status`),
  KEY `idx_houses_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of houses
-- ----------------------------
BEGIN;
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (1, 'RUKO', '1', 'RUKO-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:26');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (2, 'RUKO', '2', 'RUKO-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:21');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (3, 'RUKO', '3', 'RUKO-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:19');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (4, 'RUKO', '4', 'RUKO-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:18');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (5, 'RUKO', '5', 'RUKO-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:14');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (6, 'RUKO', '6', 'RUKO-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:12');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (7, 'RUKO', '7', 'RUKO-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:11');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (8, 'RUKO', '8', 'RUKO-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:10');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (9, 'RUKO', '9', 'RUKO-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:08');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (10, 'RUKO', '10', 'RUKO-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-24 02:21:06');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (11, 'UTAMA', '1', 'UTAMA-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (12, 'UTAMA', '2', 'UTAMA-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (13, 'UTAMA', '3', 'UTAMA-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (14, 'UTAMA', '4', 'UTAMA-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (15, 'UTAMA', '5', 'UTAMA-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (16, 'UTAMA', '6', 'UTAMA-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (17, 'UTAMA', '7', 'UTAMA-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (18, 'UTAMA', '8', 'UTAMA-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (19, 'UTAMA', '9', 'UTAMA-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (20, 'UTAMA', '10', 'UTAMA-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (21, 'UTAMA', '11', 'UTAMA-11', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (22, 'UTAMA', '12', 'UTAMA-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (23, 'UTAMA', '13', 'UTAMA-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (24, 'UTAMA', '14', 'UTAMA-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (25, 'UTAMA', '15', 'UTAMA-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (26, 'UTAMA', '16', 'UTAMA-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (27, 'UTAMA', '17', 'UTAMA-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (28, 'UTAMA', '18', 'UTAMA-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (29, 'UTAMA', '19', 'UTAMA-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (30, 'UTAMA', '20', 'UTAMA-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (31, 'UTAMA', '21', 'UTAMA-21', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (32, 'UTAMA', '22', 'UTAMA-22', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (33, 'UTAMA', '23', 'UTAMA-23', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (34, 'UTAMA', '24', 'UTAMA-24', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (35, 'UTAMA', '25', 'UTAMA-25', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (36, 'UTAMA', '26', 'UTAMA-26', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (37, 'UTAMA', '27', 'UTAMA-27', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (38, 'UTAMA', '28', 'UTAMA-28', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (39, 'UTAMA', '29', 'UTAMA-29', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (40, 'UTAMA', '30', 'UTAMA-30', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (41, 'UTAMA', '31', 'UTAMA-31', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (42, 'UTAMA', '32', 'UTAMA-32', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (43, 'A', '1', 'A-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (44, 'A', '2', 'A-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (45, 'A', '3', 'A-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (46, 'A', '4', 'A-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (47, 'A', '5', 'A-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (48, 'A', '6', 'A-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (49, 'A', '7', 'A-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (50, 'A', '8', 'A-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (51, 'A', '9', 'A-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (52, 'A', '10', 'A-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (53, 'A', '11', 'A-11', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (54, 'A', '12', 'A-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (55, 'A', '13', 'A-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (56, 'A', '14', 'A-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (57, 'A', '15', 'A-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (58, 'A', '16', 'A-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (59, 'A', '17', 'A-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (60, 'A', '18', 'A-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (61, 'A', '19', 'A-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (62, 'A', '20', 'A-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (63, 'A', '21', 'A-21', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (64, 'A', '22', 'A-22', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (65, 'A', '23', 'A-23', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (66, 'A', '24', 'A-24', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (67, 'A', '25', 'A-25', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (68, 'A', '26', 'A-26', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (69, 'A', '27', 'A-27', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (70, 'A', '28', 'A-28', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (71, 'A', '29', 'A-29', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (72, 'A', '30', 'A-30', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (73, 'A', '31', 'A-31', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (74, 'A', '32', 'A-32', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (75, 'A', '33', 'A-33', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (76, 'A', '34', 'A-34', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (77, 'A', '35', 'A-35', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (78, 'A', '36', 'A-36', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (79, 'A', '37', 'A-37', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (80, 'A', '38', 'A-38', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (81, 'B', '1', 'B-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (82, 'B', '2', 'B-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (83, 'B', '3', 'B-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (84, 'B', '4', 'B-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (85, 'B', '5', 'B-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (86, 'B', '6', 'B-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (87, 'B', '7', 'B-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (88, 'B', '8', 'B-8', 'house', 'occupied', '2026-01-21 15:49:37', '2026-01-28 15:12:26');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (89, 'B', '9', 'B-9', 'house', 'owned', '2026-01-21 15:49:37', '2026-01-28 15:12:26');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (90, 'B', '10', 'B-10', 'kavling', 'owned', '2026-01-21 15:49:37', '2026-01-24 15:09:24');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (91, 'B', '11', 'B-11', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (92, 'B', '12', 'B-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (93, 'B', '13', 'B-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (94, 'B', '14', 'B-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (95, 'B', '15', 'B-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (96, 'B', '16', 'B-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (97, 'B', '17', 'B-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (98, 'B', '18', 'B-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (99, 'B', '19', 'B-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (100, 'B', '20', 'B-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (101, 'B', '21', 'B-21', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (102, 'B', '22', 'B-22', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (103, 'B', '23', 'B-23', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (104, 'B', '24', 'B-24', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (105, 'B', '25', 'B-25', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (106, 'B', '26', 'B-26', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (107, 'B', '27', 'B-27', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (108, 'B', '28', 'B-28', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (109, 'B', '29', 'B-29', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (110, 'C', '1', 'C-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (111, 'C', '2', 'C-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (112, 'C', '3', 'C-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (113, 'C', '4', 'C-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (114, 'C', '5', 'C-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (115, 'C', '6', 'C-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (116, 'C', '7', 'C-7', 'house', 'rented', '2026-01-21 15:49:37', '2026-01-24 03:13:08');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (117, 'C', '8', 'C-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (118, 'C', '9', 'C-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (119, 'C', '10', 'C-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (120, 'C', '11', 'C-11', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (121, 'C', '12', 'C-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (122, 'C', '13', 'C-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (123, 'C', '14', 'C-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (124, 'C', '15', 'C-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (125, 'C', '16', 'C-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (126, 'C', '17', 'C-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (127, 'C', '18', 'C-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (128, 'C', '19', 'C-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (129, 'C', '20', 'C-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (130, 'D', '1', 'D-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (131, 'D', '2', 'D-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (132, 'D', '3', 'D-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (133, 'D', '4', 'D-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (134, 'D', '5', 'D-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (135, 'D', '6', 'D-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (136, 'D', '7', 'D-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (137, 'D', '8', 'D-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (138, 'D', '9', 'D-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (139, 'D', '10', 'D-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (140, 'D', '11', 'D-11', 'house', 'owned', '2026-01-21 15:49:37', '2026-01-24 03:07:03');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (141, 'D', '12', 'D-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (142, 'D', '13', 'D-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (143, 'D', '14', 'D-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (144, 'D', '15', 'D-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (145, 'D', '16', 'D-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (146, 'D', '17', 'D-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (147, 'D', '18', 'D-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (148, 'D', '19', 'D-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (149, 'D', '20', 'D-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (150, 'D', '21', 'D-21', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (151, 'D', '22', 'D-22', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (152, 'D', '23', 'D-23', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (153, 'D', '24', 'D-24', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (154, 'D', '25', 'D-25', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (155, 'D', '26', 'D-26', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (156, 'D', '27', 'D-27', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (157, 'D', '28', 'D-28', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (158, 'D', '29', 'D-29', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (159, 'D', '30', 'D-30', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (160, 'D', '31', 'D-31', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (161, 'D', '32', 'D-32', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (162, 'D', '33', 'D-33', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (163, 'D', '34', 'D-34', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (164, 'D', '35', 'D-35', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (165, 'D', '36', 'D-36', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (166, 'D', '37', 'D-37', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (167, 'D', '38', 'D-38', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (168, 'D', '39', 'D-39', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (169, 'D', '40', 'D-40', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (170, 'D', '41', 'D-41', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (171, 'D', '42', 'D-42', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (172, 'D', '43', 'D-43', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (173, 'D', '44', 'D-44', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (174, 'D', '45', 'D-45', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (175, 'D', '46', 'D-46', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (176, 'D', '47', 'D-47', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (177, 'D', '48', 'D-48', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (178, 'D', '49', 'D-49', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (179, 'D', '50', 'D-50', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (180, 'D', '51', 'D-51', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (181, 'D', '52', 'D-52', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (182, 'D', '53', 'D-53', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (183, 'D', '54', 'D-54', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (184, 'D', '55', 'D-55', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (185, 'D', '56', 'D-56', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (186, 'D', '57', 'D-57', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (187, 'D', '58', 'D-58', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (188, 'E', '1', 'E-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (189, 'E', '2', 'E-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (190, 'E', '3', 'E-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (191, 'E', '4', 'E-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (192, 'E', '5', 'E-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (193, 'E', '6', 'E-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (194, 'E', '7', 'E-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (195, 'E', '8', 'E-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (196, 'E', '9', 'E-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (197, 'E', '10', 'E-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (198, 'E', '11', 'E-11', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (199, 'E', '12', 'E-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (200, 'E', '13', 'E-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (201, 'E', '14', 'E-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (202, 'E', '15', 'E-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (203, 'E', '16', 'E-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (204, 'E', '17', 'E-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (205, 'E', '18', 'E-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (206, 'E', '19', 'E-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (207, 'E', '20', 'E-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (208, 'E', '21', 'E-21', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (209, 'E', '22', 'E-22', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (210, 'E', '23', 'E-23', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (211, 'E', '24', 'E-24', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (212, 'E', '25', 'E-25', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (213, 'E', '26', 'E-26', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (214, 'E', '27', 'E-27', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (215, 'E', '28', 'E-28', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (216, 'E', '29', 'E-29', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (217, 'E', '30', 'E-30', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (218, 'E', '31', 'E-31', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (219, 'E', '32', 'E-32', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (220, 'E', '33', 'E-33', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (221, 'E', '34', 'E-34', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (222, 'E', '35', 'E-35', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (223, 'E', '36', 'E-36', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (224, 'E', '37', 'E-37', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (225, 'E', '38', 'E-38', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (226, 'E', '39', 'E-39', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (227, 'F', '1', 'F-1', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (228, 'F', '2', 'F-2', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (229, 'F', '3', 'F-3', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (230, 'F', '4', 'F-4', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (231, 'F', '5', 'F-5', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (232, 'F', '6', 'F-6', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (233, 'F', '7', 'F-7', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (234, 'F', '8', 'F-8', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (235, 'F', '9', 'F-9', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (236, 'F', '10', 'F-10', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (237, 'F', '11', 'F-11', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (238, 'F', '12', 'F-12', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (239, 'F', '13', 'F-13', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (240, 'F', '14', 'F-14', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (241, 'F', '15', 'F-15', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (242, 'F', '16', 'F-16', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (243, 'F', '17', 'F-17', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (244, 'F', '18', 'F-18', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (245, 'F', '19', 'F-19', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (246, 'F', '20', 'F-20', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (247, 'F', '21', 'F-21', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (248, 'F', '22', 'F-22', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (249, 'F', '23', 'F-23', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (250, 'F', '24', 'F-24', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (251, 'F', '25', 'F-25', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (252, 'F', '26', 'F-26', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (253, 'F', '27', 'F-27', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (254, 'F', '28', 'F-28', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (255, 'F', '29', 'F-29', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (256, 'F', '30', 'F-30', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (257, 'F', '31', 'F-31', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (258, 'F', '32', 'F-32', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (259, 'F', '33', 'F-33', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (260, 'F', '34', 'F-34', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (261, 'F', '35', 'F-35', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
INSERT INTO `houses` (`id`, `block`, `number`, `code`, `type`, `status`, `created_at`, `updated_at`) VALUES (262, 'F', '36', 'F-36', 'kavling', 'vacant', '2026-01-21 15:49:37', '2026-01-21 15:49:37');
COMMIT;

-- ----------------------------
-- Table structure for important_contacts
-- ----------------------------
DROP TABLE IF EXISTS `important_contacts`;
CREATE TABLE `important_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_important_contacts_category` (`category`),
  KEY `idx_important_contacts_is_public` (`is_public`),
  KEY `idx_important_contacts_sort_order` (`sort_order`),
  KEY `fk_important_contacts_created_by` (`created_by`),
  CONSTRAINT `fk_important_contacts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of important_contacts
-- ----------------------------
BEGIN;
INSERT INTO `important_contacts` (`id`, `name`, `category`, `phone`, `whatsapp`, `description`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'Pemadam Kebakaran', 'Darurat', '113', NULL, 'Layanan pemadam kebakaran nasional.', 1, 1, 1, '2026-01-10 08:00:00', '2026-01-10 08:00:00');
INSERT INTO `important_contacts` (`id`, `name`, `category`, `phone`, `whatsapp`, `description`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'Kepolisian', 'Darurat', '110', NULL, 'Layanan darurat kepolisian.', 1, 2, 1, '2026-01-10 08:01:00', '2026-01-10 08:01:00');
INSERT INTO `important_contacts` (`id`, `name`, `category`, `phone`, `whatsapp`, `description`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES (3, 'Ambulans', 'Kesehatan', '119', NULL, 'Layanan ambulans dan kegawatdaruratan medis.', 1, 3, 1, '2026-01-10 08:02:00', '2026-01-26 22:02:04');
INSERT INTO `important_contacts` (`id`, `name`, `category`, `phone`, `whatsapp`, `description`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES (4, 'PLN Gangguan', 'Utilitas', '123', NULL, 'Layanan pengaduan gangguan listrik PLN.', 1, 4, 1, '2026-01-10 08:03:00', '2026-01-10 08:03:00');
INSERT INTO `important_contacts` (`id`, `name`, `category`, `phone`, `whatsapp`, `description`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES (5, 'PDAM Gangguan Air', 'Utilitas', '1500454', NULL, 'Layanan pengaduan gangguan air PDAM.', 1, 5, 1, '2026-01-10 08:04:00', '2026-01-10 08:04:00');
INSERT INTO `important_contacts` (`id`, `name`, `category`, `phone`, `whatsapp`, `description`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES (6, 'SAR / Basarnas', 'Darurat', '115', NULL, 'Layanan pencarian dan pertolongan (SAR).', 1, 6, 1, '2026-01-10 08:05:00', '2026-01-10 08:05:00');
COMMIT;

-- ----------------------------
-- Table structure for inventories
-- ----------------------------
DROP TABLE IF EXISTS `inventories`;
CREATE TABLE `inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition` enum('good','fair','damaged','maintenance','lost') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `qty` int NOT NULL DEFAULT '1',
  `unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acquired_at` date DEFAULT NULL,
  `purchase_price` decimal(14,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventories_code` (`code`),
  KEY `idx_inventories_status` (`status`),
  KEY `idx_inventories_category` (`category`),
  KEY `idx_inventories_condition` (`condition`),
  KEY `idx_inventories_created_at` (`created_at`),
  KEY `fk_inventories_created_by` (`created_by`),
  CONSTRAINT `fk_inventories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of inventories
-- ----------------------------
BEGIN;
INSERT INTO `inventories` (`id`, `code`, `name`, `category`, `location_text`, `condition`, `qty`, `unit`, `acquired_at`, `purchase_price`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'INV-1768995191656', 'Speaker Aktif Masjid 12 inch', 'audio_multimedia', 'Gudang Masjid', 'good', 2, 'pcs', '2025-12-15', 3850000.00, 'Dipakai untuk kajian & tarawih. Kabel power dan jack disimpan di kotak “Audio”, kunci di DKM.', 'active', 1, '2026-01-21 11:33:11', '2026-01-21 13:07:47');
INSERT INTO `inventories` (`id`, `code`, `name`, `category`, `location_text`, `condition`, `qty`, `unit`, `acquired_at`, `purchase_price`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'INV-1768995959045', 'HT (Handy Talky) Security', 'keamanan', 'Pos Satpam', 'good', 4, 'pcs', '2026-01-07', 1200000.00, '1 unit standby di pos, 3 unit untuk patroli. Charger kolektif disimpan di laci pos, penggantian baterai dicatat per 6 bulan.', 'archived', 1, '2026-01-21 11:45:59', '2026-01-21 11:48:39');
INSERT INTO `inventories` (`id`, `code`, `name`, `category`, `location_text`, `condition`, `qty`, `unit`, `acquired_at`, `purchase_price`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (3, 'INV-1768997881908', 'Mesin Potong Rumput (Brush Cutter)', 'kebersihan', 'Ruang Serbaguna / Aula', 'fair', 1, 'pcs', '2026-01-10', 1950000.00, 'Mesin Potong Rumput (Brush Cutter)', 'active', 1, '2026-01-21 12:18:02', '2026-01-21 12:18:02');
COMMIT;

-- ----------------------------
-- Table structure for inventory_logs
-- ----------------------------
DROP TABLE IF EXISTS `inventory_logs`;
CREATE TABLE `inventory_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `action` enum('create','update','move','checkout','return','adjust_qty','condition','archive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_change` int DEFAULT NULL,
  `from_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borrower_person_id` bigint unsigned DEFAULT NULL,
  `borrower_house_id` bigint unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_logs_inventory_id` (`inventory_id`),
  KEY `idx_inventory_logs_action` (`action`),
  KEY `idx_inventory_logs_created_at` (`created_at`),
  KEY `idx_inventory_logs_actor_user_id` (`actor_user_id`),
  KEY `fk_inventory_logs_borrower_person_id` (`borrower_person_id`),
  KEY `fk_inventory_logs_borrower_house_id` (`borrower_house_id`),
  CONSTRAINT `fk_inventory_logs_actor_user_id` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_logs_borrower_house_id` FOREIGN KEY (`borrower_house_id`) REFERENCES `houses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_logs_borrower_person_id` FOREIGN KEY (`borrower_person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_logs_inventory_id` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of inventory_logs
-- ----------------------------
BEGIN;
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (1, 1, 'create', NULL, NULL, NULL, NULL, NULL, 'Created', 1, '2026-01-21 11:33:11');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (2, 2, 'create', NULL, NULL, NULL, NULL, NULL, 'Created', 1, '2026-01-21 11:45:59');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (3, 1, 'update', NULL, NULL, NULL, NULL, NULL, 'Update from admin', 1, '2026-01-21 11:46:30');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (5, 2, 'archive', NULL, NULL, NULL, NULL, NULL, 'Archived', 1, '2026-01-21 11:48:39');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (6, 1, 'checkout', NULL, NULL, NULL, NULL, 7, 'Peminjam: Ahmad Fauzi\nRumah: 07\nRencana kembali: Rab, 21 Jan 2026, 22.00\nCatatan: Dipinjam untuk kajian rutin remaja masjid. Dikembalikan setelah acara selesai.', 1, '2026-01-21 12:15:25');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (7, 3, 'create', NULL, NULL, NULL, NULL, NULL, 'Created', 1, '2026-01-21 12:18:02');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (8, 1, 'return', NULL, NULL, NULL, NULL, NULL, 'Catatan: Barang dikembalikan sesuai jadwal, berfungsi normal dan lengkap.', 1, '2026-01-21 13:07:47');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `action`, `qty_change`, `from_location`, `to_location`, `borrower_person_id`, `borrower_house_id`, `note`, `actor_user_id`, `created_at`) VALUES (9, 1, 'condition', NULL, NULL, NULL, NULL, NULL, 'Condition set after return', 1, '2026-01-21 13:07:47');
COMMIT;

-- ----------------------------
-- Table structure for invoice_lines
-- ----------------------------
DROP TABLE IF EXISTS `invoice_lines`;
CREATE TABLE `invoice_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `house_id` bigint unsigned DEFAULT NULL,
  `line_type` enum('base','unit_fee','adjustment','discount','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(14,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_lines_invoice` (`invoice_id`),
  KEY `idx_invoice_lines_house` (`house_id`),
  KEY `idx_invoice_lines_type` (`line_type`),
  CONSTRAINT `fk_invoice_lines_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_lines_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of invoice_lines
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for invoices
-- ----------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `household_id` bigint unsigned NOT NULL,
  `charge_type_id` bigint unsigned NOT NULL,
  `period` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid','void') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoices_household_charge_period` (`household_id`,`charge_type_id`,`period`),
  KEY `idx_invoices_household` (`household_id`),
  KEY `idx_invoices_charge_type` (`charge_type_id`),
  KEY `idx_invoices_period` (`period`),
  KEY `idx_invoices_status` (`status`),
  CONSTRAINT `fk_invoices_charge_type` FOREIGN KEY (`charge_type_id`) REFERENCES `charge_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_invoices_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of invoices
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for ledger_accounts
-- ----------------------------
DROP TABLE IF EXISTS `ledger_accounts`;
CREATE TABLE `ledger_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('paguyuban','dkm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ledger_accounts_type_name` (`type`,`name`),
  KEY `idx_ledger_accounts_type` (`type`),
  KEY `idx_ledger_accounts_deleted_at` (`deleted_at`),
  KEY `idx_ledger_accounts_type_deleted` (`type`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of ledger_accounts
-- ----------------------------
BEGIN;
INSERT INTO `ledger_accounts` (`id`, `name`, `type`, `balance`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1, 'PAGUYUBAN - Main', 'paguyuban', 1000000.00, '2026-01-28 03:21:59', NULL, '2026-01-28 10:21:59', NULL, NULL, NULL);
COMMIT;

-- ----------------------------
-- Table structure for ledger_entries
-- ----------------------------
DROP TABLE IF EXISTS `ledger_entries`;
CREATE TABLE `ledger_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ledger_account_id` bigint unsigned NOT NULL,
  `direction` enum('in','out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `category` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occurred_at` datetime NOT NULL,
  `source_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ledger_entries_account` (`ledger_account_id`),
  KEY `idx_ledger_entries_occurred` (`occurred_at`),
  KEY `idx_ledger_entries_source` (`source_type`,`source_id`),
  KEY `fk_ledger_entries_created_by` (`created_by`),
  CONSTRAINT `fk_ledger_entries_account` FOREIGN KEY (`ledger_account_id`) REFERENCES `ledger_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ledger_entries_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of ledger_entries
-- ----------------------------
BEGIN;
INSERT INTO `ledger_entries` (`id`, `ledger_account_id`, `direction`, `amount`, `category`, `description`, `occurred_at`, `source_type`, `source_id`, `created_by`, `created_at`) VALUES (1, 1, 'in', 1000000.00, 'fundraiser_donation', 'Donasi fundraiser #1 - Donasi Perbaikan Penerangan Jalan Lingkungan', '2026-01-28 09:36:00', 'fundraiser_donation', 1, 1, '2026-01-28 03:21:59');
COMMIT;

-- ----------------------------
-- Table structure for local_businesses
-- ----------------------------
DROP TABLE IF EXISTS `local_businesses`;
CREATE TABLE `local_businesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_person_id` bigint unsigned DEFAULT NULL,
  `house_id` bigint unsigned DEFAULT NULL,
  `name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `whatsapp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_lapak` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','active','inactive','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_local_businesses_owner_person_id` (`owner_person_id`),
  KEY `idx_local_businesses_house_id` (`house_id`),
  KEY `idx_local_businesses_status` (`status`),
  KEY `idx_local_businesses_category` (`category`),
  KEY `idx_local_businesses_is_lapak` (`is_lapak`),
  KEY `fk_local_businesses_created_by` (`created_by`),
  KEY `fk_local_businesses_approved_by` (`approved_by`),
  CONSTRAINT `fk_local_businesses_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_local_businesses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_local_businesses_house_id` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_local_businesses_owner_person_id` FOREIGN KEY (`owner_person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of local_businesses
-- ----------------------------
BEGIN;
INSERT INTO `local_businesses` (`id`, `owner_person_id`, `house_id`, `name`, `category`, `description`, `whatsapp`, `phone`, `address`, `is_lapak`, `status`, `created_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES (1, 3, 140, 'Donat Ceria', 'Makanan & Minuman', 'Donat Ceria menyajikan donat rumahan yang empuk, lembut, dan fresh setiap hari. Cocok untuk camilan keluarga, acara, maupun teman minum teh dan kopi.', '085721334500', NULL, 'D-11', 0, 'active', 2, NULL, NULL, '2026-01-21 13:56:28', '2026-01-21 21:14:06');
COMMIT;

-- ----------------------------
-- Table structure for local_products
-- ----------------------------
DROP TABLE IF EXISTS `local_products`;
CREATE TABLE `local_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `price` decimal(14,2) DEFAULT NULL,
  `unit` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_local_products_business_id` (`business_id`),
  KEY `idx_local_products_status` (`status`),
  KEY `idx_local_products_price` (`price`),
  KEY `fk_local_products_created_by` (`created_by`),
  CONSTRAINT `fk_local_products_business_id` FOREIGN KEY (`business_id`) REFERENCES `local_businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_local_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of local_products
-- ----------------------------
BEGIN;
INSERT INTO `local_products` (`id`, `business_id`, `name`, `description`, `price`, `unit`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (1, 1, 'Donat Gula Ceria', 'Donat empuk dengan taburan gula halus, rasa manis pas dan lembut. Cocok untuk camilan sehari-hari.', 3000.00, 'pcs', 'http://sis-harmoni.test/uploads/images/20260121_150349_a371c9025e_images.jpeg', 'active', 2, '2026-01-21 15:03:49', '2026-01-21 15:03:49');
INSERT INTO `local_products` (`id`, `business_id`, `name`, `description`, `price`, `unit`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (2, 1, 'Donat Cokelat Ceria', 'Donat lembut dengan topping cokelat lumer dan meses. Favorit anak-anak dan keluarga.', 4000.00, 'pcs', 'http://sis-harmoni.test/uploads/images/20260121_150446_3a4bd35f91_images__1_.jpeg', 'active', 2, '2026-01-21 15:04:47', '2026-01-22 00:05:52');
INSERT INTO `local_products` (`id`, `business_id`, `name`, `description`, `price`, `unit`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (3, 1, 'Donat Keju Ceria', 'Donat empuk dengan parutan keju melimpah, gurih dan manis seimbang.', 4000.00, 'pcs', 'http://sis-harmoni.test/uploads/images/20260121_170910_4e1690a182_3cbfbbd8-035a-4157-914f-87d657908e62_items-publish-rutr7mdfh5c-49855-512.webp', 'active', 2, '2026-01-21 17:09:11', '2026-01-21 17:09:11');
INSERT INTO `local_products` (`id`, `business_id`, `name`, `description`, `price`, `unit`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (4, 1, 'Paket Donat Ceria Mix', 'Paket isi 10 donat mix (gula, cokelat, keju). Cocok untuk arisan, rapat, dan acara keluarga.', 35000.00, 'paket', 'http://sis-harmoni.test/uploads/images/20260121_171141_8fa474135f_images__2_.jpeg', 'active', 2, '2026-01-21 17:10:37', '2026-01-22 00:11:42');
COMMIT;

-- ----------------------------
-- Table structure for meeting_action_items
-- ----------------------------
DROP TABLE IF EXISTS `meeting_action_items`;
CREATE TABLE `meeting_action_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `meeting_minute_id` bigint unsigned NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_user_id` bigint unsigned DEFAULT NULL,
  `pic_person_id` bigint unsigned DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `status` enum('open','done','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `done_at` datetime DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_meeting_action_items_meeting_minute_id` (`meeting_minute_id`),
  KEY `idx_meeting_action_items_status` (`status`),
  KEY `idx_meeting_action_items_due_at` (`due_at`),
  KEY `fk_meeting_action_items_pic_user_id` (`pic_user_id`),
  KEY `fk_meeting_action_items_pic_person_id` (`pic_person_id`),
  CONSTRAINT `fk_meeting_action_items_meeting_minute_id` FOREIGN KEY (`meeting_minute_id`) REFERENCES `meeting_minutes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meeting_action_items_pic_person_id` FOREIGN KEY (`pic_person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_meeting_action_items_pic_user_id` FOREIGN KEY (`pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of meeting_action_items
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for meeting_minutes
-- ----------------------------
DROP TABLE IF EXISTS `meeting_minutes`;
CREATE TABLE `meeting_minutes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_at` datetime NOT NULL,
  `location_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agenda` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `decisions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `followups` json DEFAULT NULL,
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_meeting_minutes_meeting_at` (`meeting_at`),
  KEY `idx_meeting_minutes_status` (`status`),
  KEY `fk_meeting_minutes_created_by` (`created_by`),
  CONSTRAINT `fk_meeting_minutes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of meeting_minutes
-- ----------------------------
BEGIN;
INSERT INTO `meeting_minutes` (`id`, `title`, `meeting_at`, `location_text`, `agenda`, `summary`, `decisions`, `followups`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'Rapat Koordinasi Keamanan Lingkungan Januari 2026', '2026-01-18 17:03:00', 'Aula Masjid Al-Nufais', NULL, 'Rapat membahas kondisi keamanan lingkungan malam hari dan keluhan warga terkait lampu taman yang mati. Disepakati peningkatan ronda malam dan perbaikan fasilitas penerangan. Koordinasi dilakukan antara pengurus paguyuban dan petugas keamanan.', 'Peningkatan ronda malam mulai 20 Januari 2026\nPerbaikan lampu taman di 10 titik area blok C dan D\nPenjadwalan ulang jadwal jaga security malam', NULL, 'published', 1, '2026-01-21 10:04:08', '2026-01-21 11:15:15');
INSERT INTO `meeting_minutes` (`id`, `title`, `meeting_at`, `location_text`, `agenda`, `summary`, `decisions`, `followups`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'Rapat Pengurus DKM Persiapan Ramadhan 1447 H', '2026-01-20 18:29:00', 'Masjid Al-Nufais', NULL, 'Rapat membahas persiapan kegiatan Ramadhan meliputi jadwal imam, kebersihan masjid, dan kebutuhan dana operasional. Fokus pada kelancaran ibadah dan kenyamanan jamaah selama Ramadhan.', 'Penetapan jadwal imam tarawih Ramadhan 1447 H\nKerja bakti masjid pada Minggu, 16 Februari 2026\nPengadaan karpet tambahan ruang utama masjid', NULL, 'published', 1, '2026-01-21 11:29:46', '2026-01-21 11:29:46');
COMMIT;

-- ----------------------------
-- Table structure for payment_component_allocations
-- ----------------------------
DROP TABLE IF EXISTS `payment_component_allocations`;
CREATE TABLE `payment_component_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `charge_component_id` bigint unsigned NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_component` (`payment_id`,`charge_component_id`),
  KEY `idx_pca_payment` (`payment_id`),
  KEY `idx_pca_component` (`charge_component_id`),
  CONSTRAINT `fk_pca_component` FOREIGN KEY (`charge_component_id`) REFERENCES `charge_components` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pca_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of payment_component_allocations
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for payment_invoice_allocations
-- ----------------------------
DROP TABLE IF EXISTS `payment_invoice_allocations`;
CREATE TABLE `payment_invoice_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_invoice` (`payment_id`,`invoice_id`),
  KEY `idx_pia_payment` (`payment_id`),
  KEY `idx_pia_invoice` (`invoice_id`),
  CONSTRAINT `fk_pia_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pia_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of payment_invoice_allocations
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for payment_invoice_intents
-- ----------------------------
DROP TABLE IF EXISTS `payment_invoice_intents`;
CREATE TABLE `payment_invoice_intents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned NOT NULL,
  `intended_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pii_payment_invoice` (`payment_id`,`invoice_id`),
  KEY `idx_pii_payment` (`payment_id`),
  KEY `idx_pii_invoice` (`invoice_id`),
  CONSTRAINT `fk_pii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pii_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of payment_invoice_intents
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for payments
-- ----------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payer_household_id` bigint unsigned DEFAULT NULL,
  `ledger_entry_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `paid_at` datetime NOT NULL,
  `proof_file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `source` enum('resident','finance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'resident',
  `method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_paid_at` (`paid_at`),
  KEY `idx_payments_verified_by` (`verified_by`),
  KEY `idx_payments_payer_household` (`payer_household_id`),
  KEY `idx_payments_ledger_entry` (`ledger_entry_id`),
  CONSTRAINT `fk_payments_ledger_entry` FOREIGN KEY (`ledger_entry_id`) REFERENCES `ledger_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_payer_household` FOREIGN KEY (`payer_household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of payments
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_code` (`code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of permissions
-- ----------------------------
BEGIN;
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (1, 'billing.read', 'Lihat tagihan & pembayaran', 'finance', 'Mengakses daftar tagihan, status pembayaran, dan riwayat transaksi.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (2, 'billing.manage', 'Kelola penagihan & periode', 'finance', 'Mengatur periode penagihan, menerbitkan tagihan, dan pengelolaan penagihan.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (3, 'finance.verify', 'Verifikasi pembayaran & donasi', 'finance', 'Meninjau bukti, menyetujui atau menolak pembayaran tagihan dan donasi.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (4, 'content.manage', 'Kelola konten & program', 'content', 'Mengelola pengumuman, agenda, polling, serta program donasi.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (5, 'units.manage', 'Kelola unit & data warga', 'master', 'Mengelola data unit, penghuni/warga, serta kendaraan.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (6, 'poll.manage', 'Kelola polling warga', 'content', 'Membuat, mengatur, dan memantau polling beserta hasilnya.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (7, 'poll.vote', 'Ikut voting polling', 'app', 'Memberi suara pada polling yang sedang berjalan sesuai ketentuan.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (8, 'app.home.summary', 'Lihat ringkasan beranda', 'app', 'Melihat ringkasan informasi utama di beranda.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (9, 'app.home.shortcuts', 'Lihat menu cepat', 'app', 'Mengakses menu cepat untuk kebutuhan harian.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (10, 'app.home.events', 'Lihat kegiatan terdekat', 'app', 'Melihat agenda/kegiatan terdekat yang akan berlangsung.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (11, 'app.home.posts', 'Lihat info terbaru', 'app', 'Melihat pengumuman dan informasi terbaru.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (12, 'app.home.market', 'Lihat jualan warga', 'app', 'Mengakses katalog UMKM/jualan warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (13, 'app.home.polls', 'Lihat polling di beranda', 'app', 'Melihat polling aktif dan ringkasan hasil di beranda.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (14, 'app.profile.me', 'Akses data diri', 'app', 'Melihat dan memperbarui informasi profil pribadi.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (15, 'app.profile.members', 'Akses anggota keluarga', 'app', 'Melihat daftar anggota keluarga dalam satu KK/rumah tangga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (16, 'app.profile.vehicles', 'Akses kendaraan (profil)', 'app', 'Melihat data kendaraan yang terdaftar pada profil.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (17, 'app.profile.units', 'Akses unit & hunian', 'app', 'Melihat data unit, status hunian, dan keterkaitan kepemilikan/tinggal.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (18, 'app.profile.umkm', 'Akses UMKM (profil)', 'app', 'Melihat dan mengelola UMKM yang terhubung dengan profil.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (19, 'app.invoices.view', 'Lihat tagihan warga', 'app', 'Akses melihat tagihan dan status pembayaran.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (20, 'app.fundraisers.view', 'Lihat program donasi', 'app', 'Akses melihat dan mengikuti program donasi.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (21, 'app.guest_visits.view', 'Lihat buku tamu', 'app', 'Akses melihat riwayat buku tamu untuk unit.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (22, 'app.feedbacks.create', 'Kirim masukan', 'app', 'Akses membuat masukan/saran untuk pengurus.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (23, 'app.emergencies.create', 'Kirim laporan darurat', 'app', 'Akses membuat laporan darurat.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (24, 'app.umkm.view', 'Lihat UMKM warga', 'app', 'Akses melihat daftar UMKM warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (25, 'app.contacts.view', 'Lihat kontak umum', 'app', 'Akses melihat kontak umum.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (26, 'admin.registrations.review', 'Verifikasi pendaftaran warga', 'admin', 'Meninjau dan memutuskan pendaftaran warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (27, 'admin.house_claims.review', 'Verifikasi klaim unit', 'admin', 'Meninjau dan memutuskan klaim unit.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (28, 'admin.payments.verify', 'Verifikasi pembayaran', 'admin', 'Meninjau pembayaran tagihan dan menyetujui/menolak.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (29, 'admin.donations.verify', 'Verifikasi donasi masuk', 'admin', 'Meninjau donasi masuk dan menyetujui/menolak.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (30, 'admin.guest_visits.manage', 'Kelola buku tamu', 'admin', 'Mencatat dan mengelola buku tamu petugas.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (31, 'admin.emergencies.manage', 'Kelola laporan darurat', 'admin', 'Memantau dan menangani laporan darurat.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (32, 'admin.feedbacks.manage', 'Kelola masukan warga', 'admin', 'Membaca dan menanggapi masukan warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (33, 'admin.posts.manage', 'Kelola pengumuman', 'content', 'Membuat dan mengelola pengumuman.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (34, 'admin.events.manage', 'Kelola agenda kegiatan', 'content', 'Membuat dan mengelola agenda.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (35, 'admin.polls.manage', 'Kelola polling', 'content', 'Membuat dan mengelola polling warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (36, 'admin.fundraisers.manage', 'Kelola program donasi', 'content', 'Membuat dan mengelola program donasi.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (37, 'admin.charge_types.manage', 'Kelola jenis iuran', 'finance', 'Membuat dan mengatur jenis iuran dan komponen.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (38, 'admin.billing.generate', 'Generate tagihan periode', 'finance', 'Menerbitkan tagihan untuk periode tertentu.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (39, 'admin.invoices.manage', 'Kelola daftar tagihan', 'finance', 'Melihat dan mengelola tagihan warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (40, 'admin.ledger.accounts.manage', 'Kelola akun kas', 'finance', 'Mengelola akun kas dan saldo.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (41, 'admin.ledger.entries.manage', 'Kelola transaksi kas', 'finance', 'Mencatat dan meninjau transaksi kas.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (42, 'admin.houses.manage', 'Kelola unit', 'master', 'Mengelola data unit.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (43, 'admin.persons.manage', 'Kelola warga', 'master', 'Mengelola data warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (44, 'admin.vehicles.manage', 'Kelola kendaraan', 'master', 'Mengelola data kendaraan.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (45, 'admin.inventories.manage', 'Kelola inventaris', 'ops', 'Mengelola inventaris barang.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (46, 'admin.meeting_minutes.manage', 'Kelola notulen rapat', 'ops', 'Mengelola notulen rapat dan tindak lanjut.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (47, 'admin.contacts.manage', 'Kelola kontak umum', 'admin', 'Mengelola daftar kontak umum.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (48, 'admin.businesses.manage', 'Kelola UMKM', 'content', 'Meninjau dan mengelola data UMKM.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (49, 'admin.users.manage', 'Kelola pengguna', 'system', 'Mengelola akun pengguna dan statusnya.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (50, 'admin.rbac.manage', 'Kelola role & akses', 'system', 'Mengelola role dan permission.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (51, 'admin.api_console.use', 'Pengecekan sistem (API Console)', 'system', 'Mengakses halaman API Console untuk troubleshooting.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `permissions` (`id`, `code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES (52, 'admin.audit_logs.view', 'Lihat jejak aktivitas', 'system', 'Melihat catatan aktivitas (audit log).', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
COMMIT;

-- ----------------------------
-- Table structure for persons
-- ----------------------------
DROP TABLE IF EXISTS `persons`;
CREATE TABLE `persons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('M','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_place` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date NOT NULL,
  `religion` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blood_type` enum('A','B','AB','O','unknown') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `education` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','moved','left') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_persons_nik` (`nik`),
  KEY `idx_persons_full_name` (`full_name`),
  KEY `idx_persons_phone` (`phone`),
  KEY `idx_persons_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of persons
-- ----------------------------
BEGIN;
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (3, '3204052407960005', 'Gunali Rezqi Mauludi', 'M', 'Bandung', '1996-07-24', 'Islam', 'B', 'married', 'S1', 'Karywan Swasta', '085721334500', NULL, 'active', '2026-01-21 16:48:35', '2026-01-22 17:11:35');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (4, '3248509923940001', 'Hana Eliana', 'F', 'Cilacap', '1995-06-17', 'Islam', 'B', 'single', 'SMK', 'Ibu Rumah Tangga', '085321416000', NULL, 'active', '2026-01-21 16:48:35', '2026-01-22 16:52:48');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (5, '3249872832420002', 'Khaira Aqila Azzahra', 'F', 'Bandung', '2020-10-20', 'Islam', 'B', 'single', NULL, NULL, '', NULL, 'active', '2026-01-21 16:48:35', '2026-01-21 16:48:35');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (6, '3287892934232001', 'Zhafira Layina Nura', 'F', 'Bandung', '2024-02-19', 'Islam', 'B', 'single', NULL, NULL, '', NULL, 'active', '2026-01-21 16:48:35', '2026-01-21 16:48:35');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (13, '3276011205880001', 'Ahmad Rizal Prasetyo', 'M', 'Bandung', '1988-05-12', 'Islam', 'O', 'married', 'S1', 'Karyawan Swasta', '081234560001', NULL, 'active', '2026-01-23 23:50:31', '2026-01-23 23:50:31');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (14, '3276012207900002', 'Siti Nurjanah', 'F', 'Cimahi', '1990-07-22', 'Islam', 'A', 'married', 'D3', 'Ibu Rumah Tangga', '081234560002', NULL, 'active', '2026-01-23 23:50:31', '2026-01-23 23:50:31');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (15, '3276020410830001', 'Rudi Kurniawan', 'M', 'Garut', '1983-10-04', 'Islam', 'B', 'married', 'SMA', 'Wirausaha', '082112340001', NULL, 'active', '2026-01-24 02:18:09', '2026-01-24 02:18:09');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (16, '3276021205860002', 'Lina Marlina', 'F', 'Tasikmalaya', '1986-05-12', 'Islam', 'AB', 'married', 'S1', 'Guru', '082112340002', NULL, 'active', '2026-01-24 02:18:09', '2026-01-24 02:18:09');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (17, '3276031502910001', 'Andi Saputra', 'M', 'Subang', '1991-02-15', 'Islam', 'O', 'married', 'S1', 'Teknisi', '085612340001', NULL, 'active', '2026-01-24 03:10:39', '2026-01-24 03:10:39');
INSERT INTO `persons` (`id`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `religion`, `blood_type`, `marital_status`, `education`, `occupation`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES (18, '3276032104930002', 'Rina Aprilia', 'F', 'Indramayu', '1993-04-21', 'Islam', 'A', 'married', 'SMA', 'Freelancer', '085612340002', NULL, 'active', '2026-01-24 03:10:39', '2026-01-24 03:10:39');
COMMIT;

-- ----------------------------
-- Table structure for poll_options
-- ----------------------------
DROP TABLE IF EXISTS `poll_options`;
CREATE TABLE `poll_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` bigint unsigned NOT NULL,
  `label` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_poll_options_poll` (`poll_id`),
  CONSTRAINT `fk_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of poll_options
-- ----------------------------
BEGIN;
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (1, 1, 'Perbaikan penerangan jalan lingkungan', '2026-01-26 19:14:54');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (2, 1, 'Pembersihan dan perbaikan saluran air', '2026-01-26 19:14:54');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (3, 1, 'Penataan taman dan ruang terbuka', '2026-01-26 19:14:54');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (4, 1, 'Perbaikan fasilitas pos keamanan', '2026-01-26 19:14:54');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (5, 2, 'Perbaikan fasilitas wudhu dan toilet', '2026-01-26 19:15:56');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (6, 2, 'Penambahan kipas / AC ruang shalat', '2026-01-26 19:15:56');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (7, 2, 'Kegiatan pengajian tematik', '2026-01-26 19:15:56');
INSERT INTO `poll_options` (`id`, `poll_id`, `label`, `created_at`) VALUES (8, 2, 'Program sosial untuk jamaah dan warga sekitar', '2026-01-26 19:15:56');
COMMIT;

-- ----------------------------
-- Table structure for poll_votes
-- ----------------------------
DROP TABLE IF EXISTS `poll_votes`;
CREATE TABLE `poll_votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` bigint unsigned NOT NULL,
  `option_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `household_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_poll_votes_user_scope` (`poll_id`,`user_id`),
  UNIQUE KEY `uk_poll_votes_household_scope` (`poll_id`,`household_id`),
  KEY `idx_poll_votes_poll` (`poll_id`),
  KEY `idx_poll_votes_option` (`option_id`),
  KEY `idx_poll_votes_user` (`user_id`),
  KEY `idx_poll_votes_household` (`household_id`),
  CONSTRAINT `fk_poll_votes_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_poll_votes_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_poll_votes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of poll_votes
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for polls
-- ----------------------------
DROP TABLE IF EXISTS `polls`;
CREATE TABLE `polls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `status` enum('draft','published','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `vote_scope` enum('user','household') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_polls_status` (`status`),
  KEY `idx_polls_window` (`start_at`,`end_at`),
  KEY `idx_polls_created_by` (`created_by`),
  CONSTRAINT `fk_polls_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of polls
-- ----------------------------
BEGIN;
INSERT INTO `polls` (`id`, `title`, `description`, `start_at`, `end_at`, `status`, `vote_scope`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'Prioritas pembenahan lingkungan bulan ini', 'Silakan pilih pembenahan yang menurut Anda paling perlu didahulukan agar anggaran dan tenaga dapat difokuskan dengan tepat.', '2026-02-06 08:00:00', '2026-02-10 20:00:00', 'draft', 'user', 1, '2026-01-26 19:14:54', '2026-01-26 19:14:54');
INSERT INTO `polls` (`id`, `title`, `description`, `start_at`, `end_at`, `status`, `vote_scope`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'Prioritas kegiatan DKM bulan mendatang', 'Polling ini digunakan untuk menentukan kegiatan atau kebutuhan masjid yang perlu diprioritaskan dalam waktu dekat.', '2026-02-06 08:00:00', '2026-02-11 21:00:00', 'published', 'user', 1, '2026-01-26 19:15:56', '2026-01-26 19:16:03');
COMMIT;

-- ----------------------------
-- Table structure for posts
-- ----------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `org` enum('paguyuban','dkm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paguyuban',
  `category` enum('umum','keamanan','keuangan','layanan','fasilitas','lingkungan','administrasi','keagamaan','sosial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `status` enum('draft','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_posts_org` (`org`),
  KEY `idx_posts_category` (`category`),
  KEY `idx_posts_status` (`status`),
  KEY `idx_posts_created_by` (`created_by`),
  CONSTRAINT `fk_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of posts
-- ----------------------------
BEGIN;
INSERT INTO `posts` (`id`, `title`, `content`, `org`, `category`, `status`, `image_url`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'Pengumuman Administrasi Data Warga', 'Paguyuban mengimbau seluruh warga untuk memastikan data kependudukan dan kendaraan telah diperbarui di sistem. Data yang akurat akan membantu kelancaran layanan dan administrasi lingkungan.', 'paguyuban', 'administrasi', 'published', 'http://sis-harmoni.test/uploads/images/20260126_072523_87c338ac6d_kelly-sikkema-xoU52jUVUXA-unsplash.jpg', 1, '2026-01-26 14:25:23', '2026-01-26 14:25:23');
INSERT INTO `posts` (`id`, `title`, `content`, `org`, `category`, `status`, `image_url`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'Pengajian Rutin Malam Jumat', 'Assalamu’alaikum warahmatullahi wabarakatuh.\nDKM mengundang seluruh jamaah untuk menghadiri pengajian rutin malam Jumat yang akan dilaksanakan di Masjid Al-Harmoni. Kegiatan ini bertujuan untuk mempererat ukhuwah serta menambah pemahaman keislaman bersama.', 'dkm', 'keagamaan', 'published', 'http://sis-harmoni.test/uploads/images/20260126_114154_478310f7c3_aldin-nasrun-k1EYUS7v3kI-unsplash.webp', 1, '2026-01-26 18:41:54', '2026-01-26 18:41:54');
INSERT INTO `posts` (`id`, `title`, `content`, `org`, `category`, `status`, `image_url`, `created_by`, `created_at`, `updated_at`) VALUES (3, 'Jadwal Sholat & Imam Pekan Ini', 'Berikut kami informasikan jadwal imam dan muadzin Masjid Al-Harmoni untuk pekan ini. Mohon kerjasama jamaah untuk menjaga ketertiban dan kekhusyukan selama ibadah berlangsung.', 'dkm', 'keagamaan', 'published', 'http://sis-harmoni.test/uploads/images/20260126_114329_7d483112aa_masjid-pogung-raya-YRvvUCrV2RQ-unsplash.webp', 1, '2026-01-26 18:43:29', '2026-01-26 18:43:35');
INSERT INTO `posts` (`id`, `title`, `content`, `org`, `category`, `status`, `image_url`, `created_by`, `created_at`, `updated_at`) VALUES (4, 'Kerja Bakti Lingkungan Mingguan', 'Diberitahukan kepada seluruh warga, akan dilaksanakan kerja bakti lingkungan pada hari Minggu pagi. Fokus kegiatan meliputi pembersihan saluran air dan area taman. Kehadiran dan partisipasi warga sangat diharapkan.', 'paguyuban', 'lingkungan', 'published', 'http://sis-harmoni.test/uploads/images/20260126_114846_581a85f8e3_vitaly-gariev-SL4NGT-XP1I-unsplash.webp', 1, '2026-01-26 18:48:46', '2026-01-29 16:38:42');
COMMIT;

-- ----------------------------
-- Table structure for role_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of role_permissions
-- ----------------------------
BEGIN;
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 1, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 2, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 3, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 4, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 5, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 6, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 7, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 8, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 9, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 10, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 11, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 12, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 13, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 14, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 15, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 16, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 17, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 18, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 19, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 20, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 21, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 22, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 23, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 24, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 25, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 26, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 27, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 28, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 29, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 30, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 31, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 32, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 33, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 34, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 35, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 36, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 37, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 38, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 39, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 40, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 41, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 42, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 43, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 44, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 45, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 46, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 47, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 48, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 49, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 50, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 51, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (1, 52, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 1, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 7, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 8, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 9, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 10, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 11, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 12, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 13, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 14, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 15, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 16, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 17, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 18, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 19, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 20, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 21, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 22, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 23, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 24, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (2, 25, '2026-02-01 09:56:52');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 28, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 37, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 38, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 39, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 40, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 41, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (3, 51, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 26, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 27, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 28, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 29, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 30, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 31, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 32, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (4, 51, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (5, 29, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (5, 33, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (5, 34, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (5, 36, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (5, 51, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (6, 25, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (6, 30, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (6, 31, '2026-02-01 04:07:39');
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES (6, 51, '2026-02-01 04:07:39');
COMMIT;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of roles
-- ----------------------------
BEGIN;
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (1, 'admin', 'Super Admin', 'Akses penuh untuk pengelola sistem.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (2, 'resident', 'Warga', 'Akses layanan warga.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (3, 'finance', 'Keuangan', 'Kelola tagihan, pembayaran, dan kas.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (4, 'officer', 'Petugas', 'Verifikasi dan operasional harian.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (5, 'dkm', 'DKM', 'Akses program donasi dan aktivitas DKM.', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
INSERT INTO `roles` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (6, 'security', 'Keamanan', 'Tugas keamanan (buku tamu & darurat).', '2026-02-01 04:07:39', '2026-02-01 04:07:39');
COMMIT;

-- ----------------------------
-- Table structure for user_permissions
-- ----------------------------
DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `user_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `fk_user_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of user_permissions
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for user_roles
-- ----------------------------
DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of user_roles
-- ----------------------------
BEGIN;
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES (1, 1, '2026-01-21 15:49:37');
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES (2, 2, '2026-01-21 16:48:36');
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES (6, 2, '2026-01-23 23:50:31');
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES (7, 2, '2026-01-24 02:18:09');
INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES (8, 2, '2026-01-24 03:10:39');
COMMIT;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint unsigned DEFAULT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_person_id` (`person_id`),
  CONSTRAINT `fk_users_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `person_id`, `username`, `password_hash`, `email`, `status`, `created_at`, `updated_at`) VALUES (1, NULL, 'admin', '$2y$10$eG3lVs4soLPBz8NtnWNYFusmWC4kH571pcqDei7xxoEgf0pQZX/WO', NULL, 'active', '2026-01-21 15:49:37', '2026-01-25 11:33:13');
INSERT INTO `users` (`id`, `person_id`, `username`, `password_hash`, `email`, `status`, `created_at`, `updated_at`) VALUES (2, 3, 'gunalirezqi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'active', '2026-01-21 16:48:36', '2026-01-21 20:48:40');
INSERT INTO `users` (`id`, `person_id`, `username`, `password_hash`, `email`, `status`, `created_at`, `updated_at`) VALUES (6, 13, 'ahmadrizal', '$2y$10$CJohFZ0qQYqhwRa655xHI.o5A1soEQeiJrAyTEs6RP5l33153gBGm', NULL, 'active', '2026-01-23 23:50:31', '2026-01-28 08:12:26');
INSERT INTO `users` (`id`, `person_id`, `username`, `password_hash`, `email`, `status`, `created_at`, `updated_at`) VALUES (7, 15, 'rudikurniawan', '$2y$10$KVdDqilhV4s8pf0GZcR9oOs8TdEQKTzfKxf4qY81keUQTpjIjwp6S', NULL, 'active', '2026-01-24 02:18:09', '2026-01-23 20:06:12');
INSERT INTO `users` (`id`, `person_id`, `username`, `password_hash`, `email`, `status`, `created_at`, `updated_at`) VALUES (8, 17, 'andisaputra', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'active', '2026-01-24 03:10:39', '2026-02-02 12:14:08');
COMMIT;

-- ----------------------------
-- Table structure for vehicles
-- ----------------------------
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint unsigned NOT NULL,
  `type` enum('motor','mobil','lainnya') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `plate_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vehicles_person_plate` (`person_id`,`plate_number`),
  KEY `idx_vehicles_person` (`person_id`),
  KEY `idx_vehicles_plate` (`plate_number`),
  CONSTRAINT `fk_vehicles_person` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of vehicles
-- ----------------------------
BEGIN;
INSERT INTO `vehicles` (`id`, `person_id`, `type`, `plate_number`, `brand`, `color`, `status`, `created_at`, `updated_at`) VALUES (1, 3, 'motor', 'D 6879 VBT', 'Yamaha Byson', 'Merah', 'active', '2026-01-21 16:48:36', '2026-01-22 17:39:52');
INSERT INTO `vehicles` (`id`, `person_id`, `type`, `plate_number`, `brand`, `color`, `status`, `created_at`, `updated_at`) VALUES (2, 3, 'mobil', 'D 4738 YZU', 'Honda Jazz', 'Putih', 'active', '2026-01-22 17:45:18', '2026-01-22 17:45:18');
INSERT INTO `vehicles` (`id`, `person_id`, `type`, `plate_number`, `brand`, `color`, `status`, `created_at`, `updated_at`) VALUES (3, 13, 'motor', 'D 4821 ARZ', 'Honda Vario 160', 'Hitam', 'active', '2026-01-23 23:50:31', '2026-01-23 23:50:31');
INSERT INTO `vehicles` (`id`, `person_id`, `type`, `plate_number`, `brand`, `color`, `status`, `created_at`, `updated_at`) VALUES (4, 13, 'mobil', 'D 1783 RSP', 'Toyota Rush', 'Abu-abu', 'active', '2026-01-23 23:50:31', '2026-01-23 23:50:31');
INSERT INTO `vehicles` (`id`, `person_id`, `type`, `plate_number`, `brand`, `color`, `status`, `created_at`, `updated_at`) VALUES (5, 15, 'motor', 'B 1927 RDK', 'Mitsubishi Xpander', 'Putih', 'active', '2026-01-24 02:18:09', '2026-01-24 02:18:09');
INSERT INTO `vehicles` (`id`, `person_id`, `type`, `plate_number`, `brand`, `color`, `status`, `created_at`, `updated_at`) VALUES (6, 17, 'motor', 'D 3568 ASP', 'Yamaha NMAX', 'Hitam', 'active', '2026-01-24 03:10:39', '2026-01-24 03:10:39');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
