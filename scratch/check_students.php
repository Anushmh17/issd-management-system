<?php
require_once 'backend/db.php';
$stmt = $pdo->query("SELECT id, full_name, profile_picture FROM students");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($students, JSON_PRETTY_PRINT);
?>
