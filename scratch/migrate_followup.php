<?php
require_once 'backend/db.php';

try {
    $sql = "ALTER TABLE `students` 
            ADD COLUMN `next_follow_up` DATE DEFAULT NULL AFTER `boarding_address`,
            ADD COLUMN `follow_up_note` TEXT DEFAULT NULL AFTER `next_follow_up`,
            ADD COLUMN `follow_up_status` ENUM('pending', 'completed') DEFAULT 'pending' AFTER `follow_up_note`";
    
    $pdo->exec($sql);
    echo "Migration successful!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration already applied.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
