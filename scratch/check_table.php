<?php
require 'c:/xampp/htdocs/Webbuilders Projects/issd_management/backend/db.php';
try {
    $pdo->query("SELECT 1 FROM read_notices LIMIT 1");
    echo "Table exists\n";
} catch (Exception $e) {
    echo "Table missing: " . $e->getMessage() . "\n";
    // Create it
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS read_notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notice_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_notice (user_id, notice_id)
        )
    ");
    echo "Table created.\n";
}
