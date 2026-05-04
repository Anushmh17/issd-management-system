<?php
include 'backend/db.php';
$username = 'nimal_silva';
$password = '123456';

$stmt = $pdo->prepare("SELECT * FROM lecturers WHERE username = ?");
$stmt->execute([$username]);
$lect = $stmt->fetch();

if ($lect) {
    echo "Lecturer found: " . $lect['username'] . "\n";
    echo "Stored hash: " . $lect['password'] . "\n";
    if (password_verify($password, $lect['password'])) {
        echo "Password VERIFIED successfully.\n";
    } else {
        echo "Password verification FAILED.\n";
    }
} else {
    echo "Lecturer not found.\n";
}
?>
