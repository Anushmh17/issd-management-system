<?php
include 'backend/db.php';
$email = 'nimal@learn.com';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "User found in USERS table: " . $user['email'] . " (Role: " . $user['role'] . ")\n";
} else {
    echo "Email not found in USERS table.\n";
}
?>
