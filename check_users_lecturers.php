<?php
require_once 'backend/db.php';
try {
    $stmt = $pdo->query("SELECT id, name, role FROM users WHERE role = 'lecturer'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
