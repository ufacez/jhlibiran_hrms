-- ============================================================
-- Migration: Project-Based Employee Archiving on Project Completion
-- TrackSite Construction Management System
-- Date: 2026-02-25
-- ============================================================
-- This migration adds:
--   1. employment_type column to workers (regular / project_based)
--   2. archived_at, archived_by, archive_reason columns to projects
--   3. completed_at column to projects
--   4. Updated employment_status enum to include 'end_of_contract'
-- ============================================================

-- 1. Add employment_type to workers table
ALTER TABLE `workers`
  ADD COLUMN `employment_type` ENUM('regular', 'project_based') NOT NULL DEFAULT 'project_based'
  AFTER `employment_status`;

-- 2. Add archive metadata columns to projects table
ALTER TABLE `projects`
  ADD COLUMN `completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_archived`,
  ADD COLUMN `completed_by` INT(11) DEFAULT NULL AFTER `completed_at`,
  ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `archived_by` INT(11) DEFAULT NULL AFTER `archived_at`,
  ADD COLUMN `archive_reason` TEXT DEFAULT NULL AFTER `archived_by`;

-- 3. Update employment_status enum to include 'end_of_contract'
ALTER TABLE `workers`
  MODIFY COLUMN `employment_status` ENUM('active','on_leave','terminated','blocklisted','end_of_contract') NOT NULL DEFAULT 'active';

-- 4. Update view if it exists (drop and recreate)
DROP VIEW IF EXISTS `vw_workers_with_rates`;

-- ============================================================
-- Verification queries (run after migration to verify)
-- ============================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
--   WHERE TABLE_SCHEMA = 'construction_management' AND TABLE_NAME = 'workers' AND COLUMN_NAME = 'employment_type';
-- SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
--   WHERE TABLE_SCHEMA = 'construction_management' AND TABLE_NAME = 'projects' AND COLUMN_NAME IN ('completed_at','archived_at','archived_by','archive_reason');
