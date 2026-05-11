<?php
require_once 'backend/db.php';
$stmt = $pdo->query("DESCRIBE students");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) {
    if ($c['Field'] === 'profile_picture') {
        echo json_encode($c, JSON_PRETTY_PRINT);
    }
}
?>
