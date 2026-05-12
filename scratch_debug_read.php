<?php
require_once __DIR__ . '/backend/db.php';
$pdo = getDBConnection();
$res = $pdo->query("SELECT * FROM read_notices LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
