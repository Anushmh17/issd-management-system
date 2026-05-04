<?php
include 'backend/db.php';
$stmt = $pdo->query('SELECT id, username, email, name FROM lecturers');
while($r = $stmt->fetch()) {
    echo $r['id'] . " | " . $r['username'] . " | " . $r['email'] . " | " . $r['name'] . "\n";
}
?>
