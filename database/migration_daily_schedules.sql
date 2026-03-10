-- =====================================================
-- Migration: Daily Schedules (Per-Date Schedule System)
-- TrackSite Construction Management System
-- Date: 2026-03-10
--
-- This adds a daily_schedules table that stores per-date
-- schedule overrides. The existing 'schedules' table 
-- remains as the weekly template/default.
-- =====================================================

CREATE TABLE IF NOT EXISTS `daily_schedules` (
  `daily_schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_rest_day` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`daily_schedule_id`),
  UNIQUE KEY `unique_worker_date` (`worker_id`, `schedule_date`),
  KEY `idx_schedule_date` (`schedule_date`),
  KEY `idx_worker_active` (`worker_id`, `is_active`, `schedule_date`),
  CONSTRAINT `fk_daily_schedules_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
