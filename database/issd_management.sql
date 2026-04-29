-- =====================================================
-- LEARN Management - Database Schema
-- =====================================================

CREATE DATABASE IF NOT EXISTS `learn_management`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `learn_management`;

-- -------------------------------------------------------
-- Table: users (shared login table for all roles)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)  NOT NULL,
  `email`      VARCHAR(150)  NOT NULL UNIQUE,
  `password`   VARCHAR(255)  NOT NULL,
  `role`       ENUM('admin','lecturer','student') NOT NULL DEFAULT 'student',
  `phone`      VARCHAR(20)   DEFAULT NULL,
  `avatar`     VARCHAR(255)  DEFAULT NULL,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account  (password: Admin@1234)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Super Admin', 'admin@learn.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- -------------------------------------------------------
-- Table: courses
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_name` VARCHAR(200) NOT NULL,
  `course_code` VARCHAR(20)  NOT NULL UNIQUE,
  `description` TEXT,
  `duration`    VARCHAR(50)  DEFAULT NULL,
  `monthly_fee` DECIMAL(10,2) DEFAULT 0.00,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: lecturer_profiles
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lecturer_profiles` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `employee_id` VARCHAR(30)  DEFAULT NULL,
  `department`  VARCHAR(100) DEFAULT NULL,
  `qualification` VARCHAR(200) DEFAULT NULL,
  `joined_date` DATE         DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: student_profiles
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `student_id`   VARCHAR(30)  DEFAULT NULL,
  `dob`          DATE         DEFAULT NULL,
  `gender`       ENUM('male','female','other') DEFAULT NULL,
  `address`      TEXT,
  `guardian_name` VARCHAR(100) DEFAULT NULL,
  `guardian_phone` VARCHAR(20) DEFAULT NULL,
  `enrolled_date` DATE        DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: enrollments
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`  INT UNSIGNED NOT NULL,
  `course_id`   INT UNSIGNED NOT NULL,
  `lecturer_id` INT UNSIGNED DEFAULT NULL,
  `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status`      ENUM('active','completed','dropped') DEFAULT 'active',
  UNIQUE KEY `unique_enrollment` (`student_id`, `course_id`),
  FOREIGN KEY (`student_id`)  REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)   REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: assignments
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id`   INT UNSIGNED NOT NULL,
  `lecturer_id` INT UNSIGNED NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT,
  `due_date`    DATETIME     DEFAULT NULL,
  `max_marks`   INT          DEFAULT 100,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`)   REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: submissions
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `submissions` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT UNSIGNED NOT NULL,
  `student_id`    INT UNSIGNED NOT NULL,
  `file_path`     VARCHAR(255) DEFAULT NULL,
  `remarks`       TEXT,
  `marks`         INT          DEFAULT NULL,
  `submitted_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: payments
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_payments` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`       INT UNSIGNED NOT NULL,
  `course_id`        INT UNSIGNED NOT NULL,
  `month`            VARCHAR(7)   NOT NULL,
  `monthly_fee`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `previous_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_due`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`           ENUM('paid','partial','pending','overdue') NOT NULL DEFAULT 'pending',
  `payment_date`     DATETIME      NOT NULL,
  `next_due_date`    DATE          NOT NULL,
  `method`           ENUM('cash','bank_transfer','online') DEFAULT 'cash',
  `reference`        VARCHAR(100)  DEFAULT NULL,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: notices
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notices` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(200) NOT NULL,
  `content`     TEXT         NOT NULL,
  `target_role` ENUM('all','admin','lecturer','student') DEFAULT 'all',
  `posted_by`   INT UNSIGNED NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Table: activity_log
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `action`     VARCHAR(255) NOT NULL,
  `details`    TEXT,
  `ip_address` VARCHAR(50)  DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sample data
-- -------------------------------------------------------
INSERT IGNORE INTO `courses` (`title`, `code`, `description`, `duration`, `fee`) VALUES
('Web Development Fundamentals', 'WD101', 'HTML, CSS, JavaScript basics', '3 Months', 15000.00),
('PHP & MySQL Mastery', 'PHP201', 'Server-side programming with PHP and MySQL', '4 Months', 20000.00),
('UI/UX Design Principles', 'UX101', 'Design thinking and user experience', '2 Months', 12000.00),
('Python for Beginners', 'PY101', 'Introduction to Python programming', '3 Months', 18000.00);
