<?php
// =====================================================
// ISSD Management - Notifications API
// api/notifications.php
// =====================================================
header('Content-Type: application/json');
ob_start();
require_once dirname(__DIR__) . '/backend/config.php';
require_once dirname(__DIR__) . '/backend/db.php';
require_once dirname(__DIR__) . '/backend/notification_controller.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Only allow logged in users
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = currentUser();
$userId = $user['id'];
$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $category = $_GET['category'] ?? 'all';
        // Show both read and unread to allow viewing history
        $notifications = getRecentNotifications($pdo, $userId, $user['role'], $category, 50, false);
        $unreadCount   = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
        
        // Check for urgent follow-ups (Call alerts)
        $urgentCalls = [];
        if (hasRole(ROLE_ADMIN)) {
            $urgentCalls = getUrgentAlerts($pdo);
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'urgentCalls' => $urgentCalls
        ]);
    } elseif ($action === 'read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            markAsRead($pdo, $id);
            ob_clean();
            echo json_encode(['success' => true]);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
    } elseif ($action === 'dismiss') {
        $type    = $_POST['type']    ?? 'system';
        $title   = $_POST['title']   ?? 'Alert Closed';
        $message = $_POST['message'] ?? '';
        $link    = $_POST['link']    ?? null;
        
        // For dismissed alerts, we create them as 'read' so they appear in history
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, status) VALUES (?, ?, ?, ?, ?, 'read')");
        if ($stmt->execute([$userId, $type, $title, $message, $link])) {
            ob_clean();
            echo json_encode(['success' => true]);
        } else {
            ob_clean();
            echo json_encode(['success' => false]);
        }
    } elseif ($action === 'clear') {
        // Delete all read notifications for this user
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND status = 'read'");
        if ($stmt->execute([$userId])) {
            ob_clean();
            echo json_encode(['success' => true]);
        } else {
            ob_clean();
            echo json_encode(['success' => false]);
        }
    }
} catch (\Throwable $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

