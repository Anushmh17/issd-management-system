<?php
require_once __DIR__ . '/../backend/db.php';
$stmt = $pdo->query("DESCRIBE activity_log");
$cols = $stmt->fetchAll();
echo json_encode($cols, JSON_PRETTY_PRINT);
