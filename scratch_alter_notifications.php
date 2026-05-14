<?php
require 'backend/config.php';
require 'backend/db.php';
try {
    $pdo->exec("ALTER TABLE notifications MODIFY user_id VARCHAR(50) DEFAULT NULL");
    echo "Successfully altered notifications table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
