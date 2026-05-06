<?php
require 'c:/xampp/htdocs/Webbuilders Projects/issd_management/backend/db.php';
$stmt = $pdo->query("SELECT * FROM read_notices");
print_r($stmt->fetchAll());
echo "\n--- Students ---\n";
$stmt = $pdo->query("SELECT id, user_id, full_name FROM students");
print_r($stmt->fetchAll());
echo "\n--- Users ---\n";
$stmt = $pdo->query("SELECT id, name, role FROM users");
print_r($stmt->fetchAll());
