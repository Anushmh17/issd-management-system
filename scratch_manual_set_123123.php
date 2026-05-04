<?php
include 'backend/db.php';
$username = 'nimal_silva';
$password = '123123';
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE lecturers SET password = ? WHERE username = ?");
if ($stmt->execute([$hashed, $username])) {
    echo "Password manually updated to 123123 for $username\n";
} else {
    echo "Manual update failed.\n";
}
?>
