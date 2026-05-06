<?php
require_once __DIR__ . '/../../backend/db.php';
$stmt = $pdo->query("DESCRIBE students");
$columns = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($columns, JSON_PRETTY_PRINT);
