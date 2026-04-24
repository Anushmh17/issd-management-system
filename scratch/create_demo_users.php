<?php
require_once 'backend/db.php';
$pdo = getDBConnection();

// Create a Lecturer (Teacher)
$lectPassword = password_hash('Teacher123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO lecturers (name, email, username, password, status) VALUES (?, ?, ?, ?, ?)")
    ->execute(['Demo Teacher', 'teacher@learn.com', 'demo_teacher', $lectPassword, 'active']);

// Create a Student
$stdPassword = password_hash('Student123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)")
    ->execute(['Demo Student', 'student@learn.com', $stdPassword, 'student', 'active']);

echo "Demo accounts created successfully!\n";
echo "Teacher: teacher@learn.com / Teacher123\n";
echo "Student: student@learn.com / Student123\n";
