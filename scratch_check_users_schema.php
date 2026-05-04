<?php
include 'backend/db.php';
$stmt = $pdo->query('DESCRIBE users');
while($r = $stmt->fetch()) {
    echo $r['Field'] . " | " . $r['Type'] . " | " . $r['Null'] . "\n";
}
?>
