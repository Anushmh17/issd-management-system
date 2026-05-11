<?php
require_once 'backend/db.php';
require_once 'includes/auth.php';

$userId = currentUserId();
echo "Current User ID: " . $userId . "\n";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
print_r($user);

$stmt = $pdo->prepare("SELECT * FROM lecturers WHERE user_id = ?");
$stmt->execute([$userId]);
$lecturer = $stmt->fetch();
echo "Lecturer Table Entry:\n";
print_r($lecturer);

echo "Course Assignments for user_id $userId:\n";
$stmt = $pdo->prepare("SELECT * FROM course_assignments WHERE lecturer_id = ?");
$stmt->execute([$userId]);
print_r($stmt->fetchAll());

if ($lecturer) {
    echo "Course Assignments for lecturer_id " . $lecturer['id'] . ":\n";
    $stmt = $pdo->prepare("SELECT * FROM course_assignments WHERE lecturer_id = ?");
    $stmt->execute([$lecturer['id']]);
    print_r($stmt->fetchAll());
}
