-- =====================================================
-- LEARN Management - Lecturer Management Migration
-- =====================================================

USE `learn_management`;

-- -------------------------------------------------------
-- 1. Standalone lecturers table
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lecturers` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(150)  NOT NULL,
  `photo`          VARCHAR(255)  DEFAULT NULL,
  `email`          VARCHAR(150)  NOT NULL UNIQUE,
  `phone`          VARCHAR(20)   DEFAULT NULL,
  `qualifications` TEXT          DEFAULT NULL,
  `username`       VARCHAR(80)   NOT NULL UNIQUE,
  `password`       VARCHAR(255)  NOT NULL,
  `department`     VARCHAR(100)  DEFAULT NULL,
  `employee_id`    VARCHAR(30)   DEFAULT NULL UNIQUE,
  `joined_date`    DATE          DEFAULT NULL,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Migrate course_assignments.lecturer_id FK
--    from users(id) → lecturers(id)
-- -------------------------------------------------------

-- Drop old FK & index
ALTER TABLE `course_assignments`
  DROP FOREIGN KEY `course_assignments_ibfk_2`;

-- Flush data that references old users (safe — no assignments exist yet)
DELETE FROM `course_assignments` WHERE 1;

-- Reset lecturer_id column to match lecturers PK type
ALTER TABLE `course_assignments`
  MODIFY COLUMN `lecturer_id` INT UNSIGNED NOT NULL;

-- Add new FK pointing to lecturers table
ALTER TABLE `course_assignments`
  ADD CONSTRAINT `fk_ca_lecturer`
  FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers`(`id`) ON DELETE CASCADE;

-- -------------------------------------------------------
-- 3. Ensure upload directory marker (handled in PHP)
-- -------------------------------------------------------
