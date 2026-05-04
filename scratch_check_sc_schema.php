<?php
include 'backend/db.php';
$stmt = $pdo->query('DESCRIBE student_courses');
while($r = $stmt->fetch()) {
    echo $r['Field'] . " | " . $r['Type'] . " | " . $r['Key'] . "\n";
}
?>
