<?php
require_once 'backend/db.php';
$cols = $pdo->query('DESCRIBE students')->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);
