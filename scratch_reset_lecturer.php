<?php
include 'backend/db.php';
$username = 'nimal_silva';
$password = '123456';
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE lecturers SET password = ? WHERE username = ?");
if ($stmt->execute([$hashed, $username])) {
    echo "Password updated successfully for $username\n";
} else {
    echo "Failed to update password.\n";
}
?>
