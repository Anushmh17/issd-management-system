<?php
require_once 'backend/db.php';
echo "--- STUDENTS TABLE ---\n";
print_r($pdo->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- LECTURERS TABLE ---\n";
print_r($pdo->query("SELECT * FROM lecturers")->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- USERS TABLE ---\n";
print_r($pdo->query("SELECT id, name, role FROM users")->fetchAll(PDO::FETCH_ASSOC));
