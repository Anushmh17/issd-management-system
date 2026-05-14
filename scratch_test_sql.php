<?php
require 'backend/config.php';
require 'backend/db.php';
$id = 2; // Testing for dasds notice
$stmt = $pdo->prepare("
            SELECT l.name, 'lecturer' as role, l.photo as avatar, rn.read_at
            FROM read_notices rn
            JOIN lecturers l ON l.id = SUBSTRING(rn.user_id, 2)
            WHERE rn.notice_id = ? AND rn.user_id LIKE 'L%'
");
$stmt->execute([$id]);
$readers = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($readers);
