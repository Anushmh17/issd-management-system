<?php
include 'backend/db.php';
$stmt = $pdo->query('DESCRIBE course_assignments');
while($r = $stmt->fetch()) {
    echo $r['Field'] . " | " . $r['Type'] . " | " . $r['Key'] . "\n";
}
?>
