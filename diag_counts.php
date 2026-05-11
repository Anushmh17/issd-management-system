<?php
require_once 'backend/db.php';

// Assuming lecturer ID 2 (Demo Teacher) based on previous check, 
// but I'll check for all lecturers to be sure what's in the DB.
echo "--- Lecturers ---\n";
$stmt = $pdo->query("SELECT id, name FROM lecturers");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($lecturers);

foreach ($lecturers as $l) {
    $lid = $l['id'];
    echo "\n--- Data for Lecturer: " . $l['name'] . " (ID: $lid) ---\n";
    
    // Assigned Courses
    $stmt = $pdo->prepare("SELECT c.course_name FROM course_assignments ca JOIN courses c ON c.id = ca.course_id WHERE ca.lecturer_id = ?");
    $stmt->execute([$lid]);
    echo "Assigned Courses: ";
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    // Students in those courses
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.student_id) 
        FROM enrollments e
        JOIN course_assignments ca ON e.course_id = ca.course_id
        WHERE ca.lecturer_id = ?
    ");
    $stmt->execute([$lid]);
    echo "Total Students: " . $stmt->fetchColumn() . "\n";
    
    // Assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE lecturer_id = ?");
    $stmt->execute([$lid]);
    echo "Assignments Count: " . $stmt->fetchColumn() . "\n";
    
    // Submissions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM assignment_submissions s
        JOIN assignments a ON a.id = s.assignment_id
        WHERE a.lecturer_id = ?
    ");
    $stmt->execute([$lid]);
    echo "Submissions Count: " . $stmt->fetchColumn() . "\n";
}
