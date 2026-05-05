<?php
require_once __DIR__ . '/../backend/db.php';
$stmt = $pdo->query("DESCRIBE leads");
$cols = $stmt->fetchAll();
echo json_encode($cols, JSON_PRETTY_PRINT);
