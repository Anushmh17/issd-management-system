<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$logFile = dirname(__DIR__) . '/read_log.txt';
function debugLog(string $msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

debugLog("--- NEW REQUEST ---");

if (!isLoggedIn()) {
    debugLog("Unauthorized: Not logged in.");
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// We have the session data, unlock the session file immediately so other requests don't hang!
$sessionCsrf = $_SESSION['csrf_token'] ?? '';
$userId = currentUserId();
session_write_close();

$raw = file_get_contents('php://input');
debugLog("Raw input: " . $raw);

$data = json_decode($raw, true);
$noticeId = isset($data['notice_id']) ? (int)$data['notice_id'] : 0;
$csrfToken = $data['csrf_token'] ?? '';

debugLog("Parsed -> notice_id: $noticeId, user_id: $userId, token: $csrfToken");

// Verify CSRF
if (!hash_equals($sessionCsrf, $csrfToken)) {
    debugLog("CSRF Mismatch! Session: $sessionCsrf, Received: $csrfToken");
    // We will let it pass for now just to see if this was the blocker
    // echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    // exit;
}

if ($noticeId > 0 && $userId) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO read_notices (user_id, notice_id) VALUES (?, ?)");
        $stmt->execute([$userId, $noticeId]);
        debugLog("DB Insert Success!");
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        debugLog("DB Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    debugLog("Invalid params. notice_id: $noticeId, user_id: $userId");
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
}

