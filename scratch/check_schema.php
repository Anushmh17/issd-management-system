<?php
require_once 'backend/db.php';
$stmt = $pdo->query("DESCRIBE students");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
?>
