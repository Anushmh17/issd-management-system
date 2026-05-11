<?php
require_once 'backend/db.php';
$stmt = $pdo->query('DESCRIBE student_courses');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query('SELECT * FROM student_courses LIMIT 10');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
