<?php
require 'backend/db.php';
echo "Tables:\n";
$stmt = $pdo->query("SHOW TABLES");
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "- " . $row[0] . "\n";
}

echo "\nStudent Payments Structure:\n";
try {
    $stmt = $pdo->query("DESCRIBE student_payments");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n"; }
} catch(Exception $e) { echo "Table student_payments not found.\n"; }

echo "\nLecturer Payments Structure:\n";
try {
    $stmt = $pdo->query("DESCRIBE lecturer_payments");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n"; }
} catch(Exception $e) { echo "Table lecturer_payments not found.\n"; }
?>
