<?php
require_once 'backend/db.php';
echo "--- Submissions Table ---\n";
$stmt = $pdo->query('SELECT * FROM submissions LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Assignment Submissions Table ---\n";
$stmt = $pdo->query('SELECT * FROM assignment_submissions LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
