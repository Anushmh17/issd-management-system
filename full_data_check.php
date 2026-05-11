<?php
require_once 'backend/db.php';

echo "--- Course Assignments ---\n";
$stmt = $pdo->query("SELECT ca.*, c.course_name FROM course_assignments ca JOIN courses c ON c.id = ca.course_id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- All Enrollments ---\n";
$stmt = $pdo->query("SELECT e.*, c.course_name FROM enrollments e JOIN courses c ON c.id = e.course_id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- All Assignments ---\n";
$stmt = $pdo->query("SELECT * FROM assignments");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
