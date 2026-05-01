<?php
require 'backend/db.php';
$tables = ['student_payments', 'courses', 'students', 'enrollments', 'notifications'];
foreach($tables as $t) {
    try {
        echo "\nStructure for $t:\n";
        $stmt = $pdo->query("DESCRIBE $t");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '  ' . $row['Field'] . ' (' . $row['Type'] . ')' . ($row['Null'] == 'NO' ? ' NOT NULL' : '') . ($row['Key'] == 'PRI' ? ' PRIMARY KEY' : '') . "\n";
        }
    } catch(Exception $e) {
        echo "Table $t not found.\n";
    }
}
?>
