<?php
require 'backend/config.php';
require 'backend/db.php';
// Insert a dummy payment for student_id = 1 (Kamal Perera, user_id = 5)
$stmt = $pdo->prepare("
    INSERT INTO student_payments (student_id, course_id, month, monthly_fee, previous_balance, total_due, amount_paid, balance, status, payment_date, next_due_date, method, reference)
    VALUES (1, 4, '2026-05', 18000.00, 0.00, 18000.00, 18000.00, 0.00, 'paid', NOW(), '2026-06-14', 'online', 'TEST-PAY-01')
");
$stmt->execute();
echo "Inserted payment for student_id 1\n";
