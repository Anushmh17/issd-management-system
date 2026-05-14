<?php
// =====================================================
// ISSD Management - Notifications API
// api/notifications.php
// =====================================================
require_once dirname(__DIR__) . '/backend/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/backend/db.php';
require_once dirname(__DIR__) . '/backend/notification_controller.php';
require_once dirname(__DIR__) . '/backend/payment_controller.php';

header('Content-Type: application/json');
ob_start();

// Only allow logged in users
if (!isLoggedIn()) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user   = currentUser();
$userId = (string) currentUserId();
$role   = $user['role'] ?? 'student';

// Lecturers use 'L' prefix in notifications table
if ($role === ROLE_LECTURER) {
    $userId = 'L' . $userId;
}

// Throttled admin payment sync
if ($role === ROLE_ADMIN) {
    $lastSync = $_SESSION['lms_last_payment_sync'] ?? 0;
    if (time() - $lastSync > 900) {
        syncOverduePayments($pdo);
        syncUpcomingPayments($pdo);
        syncLecturerPaymentAlerts($pdo);
        $_SESSION['lms_last_payment_sync'] = time();
    }
}

$action = $_GET['action'] ?? 'list';

try {
    // ── LIST (no CSRF needed for GET) ────────────────────────────────────────
    if ($action === 'list') {
        $category       = $_GET['category'] ?? 'all';
        $includeCleared = isset($_GET['history']) && $_GET['history'] == 1;

        $notifications = getRecentNotifications($pdo, $userId, $role, $category, 50, false, $includeCleared);
        $unreadCount   = count(array_filter($notifications, fn($n) => !$n['is_read']));

        ob_clean();
        echo json_encode([
            'success'       => true,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'urgentCalls'   => []
        ]);

    // ── MARK ONE READ ─────────────────────────────────────────────────────────
    } elseif ($action === 'read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Ownership check — only mark notifications belonging to this user
            $check = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ? LIMIT 1");
            $check->execute([$id, $userId]);
            if ($check->fetch()) {
                markAsRead($pdo, $id);
                ob_clean();
                echo json_encode(['success' => true]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Not found']);
            }
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }

    // ── MARK ALL READ ────────────────────────────────────────────────────────
    } elseif ($action === 'read_all') {
        $affected = markAllAsRead($pdo, $userId, $role);
        ob_clean();
        echo json_encode(['success' => true, 'affected' => $affected]);

    // ── CLEAR READ NOTIFICATIONS ──────────────────────────────────────────────
    } elseif ($action === 'clear') {
        if ($role === ROLE_ADMIN) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_cleared = 1 WHERE (user_id = ? OR user_id IS NULL) AND status = 'read'");
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_cleared = 1 WHERE user_id = ? AND status = 'read'");
        }
        $stmt->execute([$userId]);

        ob_clean();
        echo json_encode(['success' => true, 'cleared' => $stmt->rowCount()]);

    // ── DISMISS ───────────────────────────────────────────────────────────────
    } elseif ($action === 'dismiss') {
        $type    = in_array($_POST['type'] ?? '', ['call','payment','enrollment','system']) ? $_POST['type'] : 'system';
        $title   = htmlspecialchars(strip_tags(trim($_POST['title']   ?? 'Alert Closed')), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')),             ENT_QUOTES, 'UTF-8');
        $link    = filter_var($_POST['link'] ?? '', FILTER_SANITIZE_URL) ?: null;

        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, status) VALUES (?, ?, ?, ?, ?, 'read')");
        ob_clean();
        echo json_encode(['success' => $stmt->execute([$userId, $type, $title, $message, $link])]);

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }

} catch (\Throwable $e) {
    error_log('Notifications API error: ' . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
