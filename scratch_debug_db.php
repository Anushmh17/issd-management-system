<?php
require 'backend/config.php';
require 'backend/db.php';
$stmt = $pdo->query("SELECT * FROM read_notices");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
