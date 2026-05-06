<?php
require_once __DIR__ . '/backend/db.php';
$tables = ['assignments', 'students', 'student_courses', 'assignment_submissions'];
echo "<pre>";
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "</pre>";
?>
