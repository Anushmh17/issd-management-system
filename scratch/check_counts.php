<?php
require_once 'backend/db.php';
echo "--- COUNTS ---\n";
echo "Users (Students): " . $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn() . "\n";
echo "Users (Lecturers): " . $pdo->query("SELECT COUNT(*) FROM users WHERE role='lecturer'")->fetchColumn() . "\n";
echo "Students Table: " . $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() . "\n";
echo "Lecturers Table: " . $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn() . "\n";
echo "Enrollments: " . $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn() . "\n";
echo "Courses: " . $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn() . "\n";
echo "Student Payments: " . $pdo->query("SELECT COUNT(*) FROM student_payments")->fetchColumn() . "\n";
echo "Total Revenue: " . $pdo->query("SELECT SUM(amount_paid) FROM student_payments")->fetchColumn() . "\n";
echo "--- MONTHLY REVENUE (GROUPED) ---\n";
$res = $pdo->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as m, SUM(amount_paid) as s FROM student_payments GROUP BY m")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
