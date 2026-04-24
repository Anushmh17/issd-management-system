-- =====================================================
-- LEARN Management - Students Table Migration
-- Run this script to add the standalone students table
-- =====================================================

USE `learn_management`;

-- -------------------------------------------------------
-- Table: students (standalone, no FK to users)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`            VARCHAR(30)   NOT NULL UNIQUE,
  `full_name`             VARCHAR(150)  NOT NULL,
  `nic_number`            VARCHAR(20)   NOT NULL,
  `batch_number`          VARCHAR(30)   NOT NULL,
  `join_date`             DATE          DEFAULT NULL,
  `office_email`          VARCHAR(150)  DEFAULT NULL,
  `office_email_password` VARCHAR(255)  DEFAULT NULL,
  `personal_email`        VARCHAR(150)  DEFAULT NULL,
  `phone_number`          VARCHAR(20)   NOT NULL,
  `whatsapp_number`       VARCHAR(20)   DEFAULT NULL,
  `guardian_name`         VARCHAR(150)  DEFAULT NULL,
  `guardian_phone`        VARCHAR(20)   DEFAULT NULL,
  `house_address`         TEXT          DEFAULT NULL,
  `boarding_address`      TEXT          DEFAULT NULL,
  `status`                ENUM('new_joined','dropout','completed') NOT NULL DEFAULT 'new_joined',
  `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
