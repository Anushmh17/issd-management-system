<?php
// =====================================================
// LEARN Management - Notifications API
// api/notifications.php
// =====================================================
header('Content-Type: application/json');
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

if ($action === 'list') {
    $category = $_GET['category'] ?? 'all';
    // Only show UNREAD notifications in the dropdown/history icon
    $notifications = getRecentNotifications($pdo, $userId, $user['role'], $category, 15, true);
    $unreadCount   = count($notifications); // Already filtered to unread
    
    // Check for urgent follow-ups (Call alerts)
    $urgentCalls = [];
    if (hasRole(ROLE_ADMIN)) {
        $urgentCalls = getUrgentAlerts($pdo);
    }

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
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
}
?>
