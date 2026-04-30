-- =====================================================
-- ISSD Management - Student Documents Table Migration
-- =====================================================

USE `issd_management`;

-- -------------------------------------------------------
-- Table: student_documents
-- One row per student, stores file paths + tracking fields
-- for each of the 10 document types.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL UNIQUE,

  -- â"€â"€ NIC Front â"€â"€
  `nic_front`              VARCHAR(255) DEFAULT NULL,
  `nic_front_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `nic_front_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `nic_front_date`         DATE DEFAULT NULL,

  -- â"€â"€ NIC Back â"€â"€
  `nic_back`              VARCHAR(255) DEFAULT NULL,
  `nic_back_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `nic_back_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `nic_back_date`         DATE DEFAULT NULL,

  -- â"€â"€ GS/JP Letter â"€â"€
  `gs_jp_letter`              VARCHAR(255) DEFAULT NULL,
  `gs_jp_letter_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `gs_jp_letter_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `gs_jp_letter_date`         DATE DEFAULT NULL,

  -- â"€â"€ CV â"€â"€
  `cv`              VARCHAR(255) DEFAULT NULL,
  `cv_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `cv_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `cv_date`         DATE DEFAULT NULL,

  -- â"€â"€ O/L Results â"€â"€
  `ol_results`              VARCHAR(255) DEFAULT NULL,
  `ol_results_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `ol_results_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `ol_results_date`         DATE DEFAULT NULL,

  -- â"€â"€ A/L Results â"€â"€
  `al_results`              VARCHAR(255) DEFAULT NULL,
  `al_results_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `al_results_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `al_results_date`         DATE DEFAULT NULL,

  -- â"€â"€ School Leaving Certificate â"€â"€
  `school_leaving_certificate`              VARCHAR(255) DEFAULT NULL,
  `school_leaving_certificate_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `school_leaving_certificate_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `school_leaving_certificate_date`         DATE DEFAULT NULL,

  -- â"€â"€ Bank Passbook â"€â"€
  `bank_passbook`              VARCHAR(255) DEFAULT NULL,
  `bank_passbook_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `bank_passbook_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `bank_passbook_date`         DATE DEFAULT NULL,

  -- â"€â"€ Reference Letter â"€â"€
  `reference_letter`              VARCHAR(255) DEFAULT NULL,
  `reference_letter_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `reference_letter_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `reference_letter_date`         DATE DEFAULT NULL,

  -- â"€â"€ Registration Fee Certificate â"€â"€
  `registration_fee_certificate`              VARCHAR(255) DEFAULT NULL,
  `registration_fee_certificate_status`       TINYINT(1)   NOT NULL DEFAULT 0,
  `registration_fee_certificate_collected_by` ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `registration_fee_certificate_date`         DATE DEFAULT NULL,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: student_other_documents
-- Stores additional supporting documents for each student
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_other_documents` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`     INT UNSIGNED NOT NULL,
  `label`          VARCHAR(255) NOT NULL,
  `file_path`      VARCHAR(255) NOT NULL,
  `collected_by`   ENUM('W1','W2','H1','H2') DEFAULT NULL,
  `collected_date` DATE DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

