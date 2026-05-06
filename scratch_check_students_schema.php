<?php
require_once __DIR__ . '/backend/db.php';
$stmt = $pdo->query("DESCRIBE students");
echo "--- Table: students ---\n";
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Field: " . $row['Field'] . "\n";
}
$stmt = $pdo->query("DESCRIBE student_courses");
echo "\n--- Table: student_courses ---\n";
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Field: " . $row['Field'] . "\n";
}
?>
