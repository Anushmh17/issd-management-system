<?php
include 'backend/db.php';
$stmt = $pdo->query('SELECT username, name FROM lecturers LIMIT 10');
while($r = $stmt->fetch()) {
    echo $r['username'] . " (" . $r['name'] . ")\n";
}
?>
