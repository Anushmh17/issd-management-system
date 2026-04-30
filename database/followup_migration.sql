-- =====================================================
-- ISSD Management - Follow-up System Migration
-- Add follow-up tracking to students table
-- =====================================================

USE `issd_management`;

ALTER TABLE `students` 
ADD COLUMN `next_follow_up` DATE DEFAULT NULL AFTER `boarding_address`,
ADD COLUMN `follow_up_note` TEXT DEFAULT NULL AFTER `next_follow_up`,
ADD COLUMN `follow_up_status` ENUM('pending', 'completed') DEFAULT 'pending' AFTER `follow_up_note`;

