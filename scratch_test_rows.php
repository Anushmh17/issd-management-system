<?php
require 'backend/config.php';
require 'backend/db.php';
$stmt = $pdo->query("SELECT id, user_id, type, title, status FROM notifications ORDER BY id DESC LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
