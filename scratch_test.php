<?php
require 'backend/config.php';
require 'backend/db.php';
$stmt = $pdo->query("DESCRIBE notifications");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
