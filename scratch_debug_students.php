<?php
require_once __DIR__ . '/backend/db.php';
$pdo = getDBConnection();
$res = $pdo->query("SELECT id, full_name FROM students WHERE id IN (1, 2, 5)")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
