-- ============================================================
-- Project Management Migration
-- TrackSite Construction Management System
-- Run this SQL in phpMyAdmin or MySQL CLI against
-- the `construction_management` database.
-- ============================================================

USE `construction_management`;

-- --------------------------------------------------------
-- Table: projects
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planning',
  `created_by` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`project_id`),
  KEY `idx_projects_status` (`status`),
  KEY `idx_projects_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: project_workers  (many-to-many link)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_workers` (
  `project_worker_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `assigned_date` date DEFAULT NULL,
  `removed_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`project_worker_id`),
  UNIQUE KEY `uq_project_worker` (`project_id`, `worker_id`),
  KEY `idx_pw_worker` (`worker_id`),
  CONSTRAINT `fk_pw_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pw_worker`  FOREIGN KEY (`worker_id`)  REFERENCES `workers` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
