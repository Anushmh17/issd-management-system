<?php
require 'backend/db.php';
$stmt = $pdo->query("DESCRIBE users");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\nAdmin Users:\n";
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
