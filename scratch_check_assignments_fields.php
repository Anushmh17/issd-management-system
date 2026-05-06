<?php
require_once __DIR__ . '/backend/db.php';
$stmt = $pdo->query("DESCRIBE assignments");
echo "--- Table: assignments ---\n";
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Field: " . $row['Field'] . "\n";
}
?>
