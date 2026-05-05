<?php
require_once dirname(__DIR__) . '/backend/db.php';
try {
    $pdo->exec("ALTER TABLE lecturers ADD COLUMN IF NOT EXISTS payment_mode ENUM('flat_monthly','per_student') NOT NULL DEFAULT 'flat_monthly'");
    $pdo->exec("ALTER TABLE lecturers ADD COLUMN IF NOT EXISTS per_student_rate DECIMAL(10,2) DEFAULT NULL");
    echo "Migration successful: payment_mode and per_student_rate added to lecturers table\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
