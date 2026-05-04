<?php
require 'backend/db.php';
$stmt = $pdo->query("SELECT DISTINCT status FROM student_courses");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
