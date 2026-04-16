-- Migration Script to update 'timesheetsite4de_timesheet3' (Test DB) 
-- to match schema of 'timesheetsite4de_timesheet' (Local DB)

-- 1. Table: client
-- Change 'contact' from bigint(10) to varchar(15) and allow NULL
ALTER TABLE `client` MODIFY `contact` varchar(15) DEFAULT NULL;
-- Add 'added' column
ALTER TABLE `client` ADD `added` timestamp NOT NULL DEFAULT current_timestamp();
-- Convert charset to match Source (optional but recommended)
ALTER TABLE `client` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- 2. Table: email_logs
-- Allow NULL for 'timesheet_date' (Source has DEFAULT NULL, Target was NOT NULL)
ALTER TABLE `email_logs` MODIFY `timesheet_date` date DEFAULT NULL;


-- 3. Table: employee
-- Add missing columns
ALTER TABLE `employee` ADD `password_changed_at` datetime DEFAULT NULL;
ALTER TABLE `employee` ADD `password_reminder_sent` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `employee` ADD `created_at` datetime DEFAULT current_timestamp();


-- 4. Table: task
-- Add 'client_id' column
ALTER TABLE `task` ADD `client_id` varchar(100) DEFAULT NULL AFTER `subcat_id`;


-- 5. Table: calendar
-- Create missing table
CREATE TABLE IF NOT EXISTS `calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `type` varchar(20) NOT NULL,
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 6. Table: employee_client
-- Create missing table with constraints
CREATE TABLE IF NOT EXISTS `employee_client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `client_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ec_client` (`client_id`),
  UNIQUE KEY `uq_emp_client` (`emp_id`,`client_id`),
  CONSTRAINT `fk_ec_client_new` FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_emp_new` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

