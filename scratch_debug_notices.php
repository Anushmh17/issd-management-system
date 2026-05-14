<?php
require 'backend/config.php';
require 'backend/db.php';
session_start();

$noticeId = 1; // Assuming there is a notice 1
$userId = 'L1'; // Assuming L1
$csrfToken = 'test';

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO read_notices (user_id, notice_id) VALUES (?, ?)");
    $stmt->execute([$userId, $noticeId]);
    echo "Success inserting into read_notices";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
