<?php
// =====================================================
// ISSD Management - Mark Notice as Read
// backend/notice_read.php
// =====================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$noticeId = isset($data['notice_id']) ? (int)$data['notice_id'] : 0;
$userId = (int)$_SESSION['user']['id'];

if ($noticeId > 0 && $userId > 0) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO read_notices (user_id, notice_id) VALUES (?, ?)");
        $stmt->execute([$userId, $noticeId]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
}

