<?php
include 'backend/db.php';
$username = 'nimal_silva';
$pass1 = '123456';
$pass2 = '123123';

$stmt = $pdo->prepare("SELECT * FROM lecturers WHERE username = ?");
$stmt->execute([$username]);
$lect = $stmt->fetch();

if ($lect) {
    echo "Lecturer found: " . $lect['username'] . "\n";
    if (password_verify($pass1, $lect['password'])) {
        echo "Password is STILL 123456 (Change FAILED).\n";
    } elseif (password_verify($pass2, $lect['password'])) {
        echo "Password is now 123123 (Change SUCCESSFUL).\n";
    } else {
        echo "Password is something else entirely.\n";
    }
}
?>
