<?php
include 'backend/db.php';
$stmt = $pdo->query('SELECT id, email, role, name FROM users');
while($r = $stmt->fetch()) {
    echo $r['id'] . " | " . $r['email'] . " | " . $r['role'] . " | " . $r['name'] . "\n";
}
?>
