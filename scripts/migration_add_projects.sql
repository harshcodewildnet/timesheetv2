-- ============================================================
-- Migration: Add Project Time Tracking & Classification Module
-- System:    timesheetv2
-- Created:   2026-04-16
-- Run on:    timesheetsite4de_timesheet (local + test DB)
-- ============================================================
-- Run steps in order. Safe to re-run (uses IF NOT EXISTS / MODIFY).
-- ============================================================

-- STEP 1: Add new roles to `employee` table
-- ============================================================
ALTER TABLE `employee`
  MODIFY COLUMN `role` ENUM(
    'admin',
    'hod',
    'rm',
    'executive',
    'sales_manager',
    'project_manager'
  ) NOT NULL DEFAULT 'executive';

-- ============================================================
-- STEP 2: Create `project` table
-- ============================================================
CREATE TABLE IF NOT EXISTS `project` (
  `project_id`                INT(11)         NOT NULL AUTO_INCREMENT,
  `client_id`                 VARCHAR(100)    NOT NULL,
  `project_name`              VARCHAR(255)    NOT NULL,
  `project_type`              ENUM(
                                'fixed_cost',
                                'time_material',
                                'staff_augmentation'
                              )               NOT NULL,
  `contact_email`             VARCHAR(255)    DEFAULT NULL,

  -- Fixed Cost specific
  `total_estimated_hours`     DECIMAL(10,2)   DEFAULT NULL,
  `original_estimated_hours`  DECIMAL(10,2)   DEFAULT NULL,
  `project_duration_days`     INT(11)         DEFAULT NULL,
  `project_start_date`        DATE            DEFAULT NULL,
  `project_end_date`          DATE            DEFAULT NULL,

  -- T&M / Staff Augmentation specific
  `monthly_allocated_hours`   DECIMAL(10,2)   DEFAULT NULL,

  -- Ownership
  `created_by`                INT(11)         DEFAULT NULL,
  `managed_by`                INT(11)         DEFAULT NULL,

  -- Status
  `is_active`                 TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`                DATETIME        DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`project_id`),
  UNIQUE KEY `uq_client_project` (`client_id`, `project_name`),

  CONSTRAINT `fk_project_client`
    FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_project_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `employee` (`emp_id`)
    ON DELETE SET NULL,

  CONSTRAINT `fk_project_managed_by`
    FOREIGN KEY (`managed_by`) REFERENCES `employee` (`emp_id`)
    ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- STEP 3: Create `project_member` table
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_member` (
  `id`                INT(11)         NOT NULL AUTO_INCREMENT,
  `project_id`        INT(11)         NOT NULL,
  `emp_id`            INT(11)         NOT NULL,
  `allocated_hours`   DECIMAL(10,2)   DEFAULT NULL,
  `role_in_project`   VARCHAR(100)    DEFAULT NULL,
  `assigned_at`       DATE            DEFAULT NULL,
  `is_active`         TINYINT(1)      NOT NULL DEFAULT 1,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_member` (`project_id`, `emp_id`),

  CONSTRAINT `fk_pm_project`
    FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_pm_emp`
    FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`)
    ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- STEP 4: Create `project_hours_log` cache table
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_hours_log` (
  `id`            INT(11)         NOT NULL AUTO_INCREMENT,
  `project_id`    INT(11)         NOT NULL,
  `emp_id`        INT(11)         DEFAULT NULL,
  `month`         CHAR(7)         DEFAULT NULL,
  `actual_hours`  DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `computed_at`   DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_emp_month` (`project_id`, `emp_id`, `month`),

  CONSTRAINT `fk_phl_project`
    FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`)
    ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- STEP 5: Alter `task` — add project_id column
-- ============================================================
ALTER TABLE `task`
  ADD COLUMN IF NOT EXISTS `project_id` INT(11) DEFAULT NULL AFTER `client_id`;

-- Add FK separately (in case column already existed without FK)
-- First check if constraint already exists before adding
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'task'
    AND CONSTRAINT_NAME = 'fk_task_project'
);

SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE `task` ADD CONSTRAINT `fk_task_project`
   FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE SET NULL',
  'SELECT ''FK fk_task_project already exists, skipping.'''
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Migration complete.
-- All existing task records remain valid (project_id = NULL).
-- All existing client records remain valid (untouched).
-- Next step: Run Phase 2 code changes.
-- ============================================================
