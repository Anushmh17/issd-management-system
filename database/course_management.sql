-- =====================================================
-- LEARN Management - Course Management Migration
-- =====================================================

USE `learn_management`;

-- -------------------------------------------------------
-- 1. Rename columns in existing courses table
-- -------------------------------------------------------
ALTER TABLE `courses`
  CHANGE COLUMN `title`  `course_name`  VARCHAR(200) NOT NULL,
  CHANGE COLUMN `code`   `course_code`  VARCHAR(20)  NOT NULL,
  CHANGE COLUMN `fee`    `monthly_fee`  DECIMAL(10,2) DEFAULT 0.00;

-- -------------------------------------------------------
-- 2. course_assignments: one lecturer per course
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id`     INT UNSIGNED NOT NULL,
  `lecturer_id`   INT UNSIGNED NOT NULL,
  `assigned_date` DATE DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_course_lecturer` (`course_id`),
  FOREIGN KEY (`course_id`)   REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. student_courses: students enrolled in courses
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_courses` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `course_id`  INT UNSIGNED NOT NULL,
  `start_date` DATE         DEFAULT NULL,
  `end_date`   DATE         DEFAULT NULL,
  `status`     ENUM('ongoing','completed','dropped') NOT NULL DEFAULT 'ongoing',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_course` (`student_id`, `course_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
