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

require_once dirname(__DIR__) . '/backend/payment_controller.php';

// Only allow logged in users
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = currentUser();
$userId = currentUserId();

// Trigger sync if admin (Throttled to run only once every 15 minutes to improve performance)
if (hasRole(ROLE_ADMIN)) {
    $lastSync = $_SESSION['lms_last_payment_sync'] ?? 0;
    if (time() - $lastSync > 900) { // 900 seconds = 15 minutes
        syncOverduePayments($pdo);
        syncUpcomingPayments($pdo);
        syncLecturerPaymentAlerts($pdo);
        $_SESSION['lms_last_payment_sync'] = time();
    }
}

$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $category = $_GET['category'] ?? 'all';
        $includeCleared = isset($_GET['history']) && $_GET['history'] == 1;
        
        // Show both read and unread to allow viewing history
        $notifications = getRecentNotifications($pdo, $userId, $user['role'], $category, 50, false, $includeCleared);
        $unreadCount   = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
        
        // Check for urgent follow-ups (Call alerts) - Disabled as not currently used by UI
        $urgentCalls = [];
        /*
        if (hasRole(ROLE_ADMIN)) {
            $urgentCalls = getUrgentAlerts($pdo);
        }
        */

        ob_clean();
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'urgentCalls' => $urgentCalls
        ]);
    } elseif ($action === 'read') {
        verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // I1: ownership check — only mark notifications belonging to this user (or global ones)
            $check = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND (user_id = ? OR user_id IS NULL) LIMIT 1");
            $check->execute([$id, $userId]);
            if ($check->fetch()) {
                markAsRead($pdo, $id);
                ob_clean();
                echo json_encode(['success' => true]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Notification not found']);
            }
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
    } elseif ($action === 'read_all') {
        verifyCsrf();
        markAllAsRead($pdo, $userId);
        ob_clean();
        echo json_encode(['success' => true]);
    } elseif ($action === 'dismiss') {
        verifyCsrf();
        // I2: sanitize all input before writing to the notifications table
        $type    = in_array($_POST['type'] ?? '', ['call','payment','enrollment','system']) ? ($_POST['type']) : 'system';
        $title   = htmlspecialchars(strip_tags(trim($_POST['title']   ?? 'Alert Closed')), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')), ENT_QUOTES, 'UTF-8');
        $link    = filter_var($_POST['link'] ?? '', FILTER_SANITIZE_URL) ?: null;
        
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
        verifyCsrf();
        $pdo->prepare("UPDATE notifications SET is_cleared = 1 WHERE user_id = ? AND is_read = 1")->execute([$userId]);
        ob_clean();
        echo json_encode(['success' => true]);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    ob_clean();
    // I4: do not expose internal error details to client
    error_log('Notifications API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An internal error occurred. Please try again.']);
}
?>

