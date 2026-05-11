<?php
require_once 'backend/db.php';
$stmt = $pdo->query('DESCRIBE lecturers');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query('SELECT * FROM users WHERE role = "lecturer" LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query('SELECT * FROM lecturers LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
