<?php
require 'backend/db.php';
require 'backend/payment_controller.php';
$students = getStudentsWithActiveCourses($pdo);
foreach($students as $s) {
    echo "ID: " . $s['student_id'] . " | Name: " . $s['full_name'] . " | Course: " . $s['course_name'] . " (Status: " . $s['status'] . ")\n";
}
