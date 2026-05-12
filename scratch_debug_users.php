<?php
require_once __DIR__ . '/backend/db.php';
$pdo = getDBConnection();
$res = $pdo->query("SELECT id, name, role FROM users WHERE id IN (1, 2, 5)")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
