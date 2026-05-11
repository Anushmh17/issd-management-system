<?php
require_once 'backend/db.php';
try {
    $stmt = $pdo->query("SHOW CREATE TABLE course_assignments");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
