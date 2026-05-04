<?php
include 'backend/db.php';
$target = '$2y$10$lYlgfHVZTl.GtW/n.xBj2OCUMHcNnkI/YNEKPEc6pp6Xh9ZIgUvn.';

echo "Checking Users table...\n";
$stmt = $pdo->query('SELECT email, password FROM users');
while($r = $stmt->fetch()) {
    if ($r['password'] === $target) echo "Match in Users: " . $r['email'] . "\n";
}

echo "Checking Lecturers table...\n";
$stmt2 = $pdo->query('SELECT username, password FROM lecturers');
while($r = $stmt2->fetch()) {
    if ($r['password'] === $target) echo "Match in Lecturers: " . $r['username'] . "\n";
}
?>
