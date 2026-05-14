<?php
require 'backend/config.php';
require 'backend/db.php';
$stmt = $pdo->query("SHOW CREATE TABLE notifications");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
