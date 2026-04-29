<?php
// =====================================================
// LEARN Management - Notification Controller
// backend/notification_controller.php
// =====================================================
require_once __DIR__ . '/db.php';

/**
 * Add a persistent notification
 */
function addNotification(PDO $pdo, $userId, $type, $title, $message, $link = null) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, link)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $type, $title, $message, $link]);
}

/**
 * Mark notification as read
 */
function markAsRead(PDO $pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get notifications for a user with categorization
 */
function getRecentNotifications(PDO $pdo, $userId, $role, $category = 'all', $limit = 15, $onlyUnread = false) {
    $sql = "SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL)";
    $params = [$userId];

    if ($onlyUnread) {
        $sql .= " AND status = 'unread'";
    }

    if ($category !== 'all') {
        $sql .= " AND type = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map DB fields to UI expected fields
    return array_map(function($n) {
        $icon = match($n['type']) {
            'call' => 'fa-phone-volume',
            'payment' => 'fa-money-bill-wave',
            'enrollment' => 'fa-user-plus',
            default => 'fa-bell',
        };
        return [
            'id' => $n['id'],
            'type' => $n['type'],
            'title' => $n['title'],
            'body' => $n['message'],
            'icon' => $icon,
            'time' => $n['created_at'],
            'link' => $n['link'] ?? '#',
            'is_read' => ($n['status'] === 'read')
        ];
    }, $results);
}

/**
 * Sync dynamic alerts to persistent notifications (e.g. Daily Check)
 */
function syncDynamicAlerts(PDO $pdo) {
    // This could be called by a cron or when admin logs in
    // For now, let's just make sure call alerts are handled
}

/**
 * Check for urgent alerts (e.g. calls due TODAY)
 */
function getUrgentAlerts(PDO $pdo) {
    $today = date('Y-m-d');
    $alerts = [];

    // 1. Student Reminders
    $stmt1 = $pdo->prepare("
        SELECT id, full_name as name, phone_number as phone, follow_up_note as note, next_follow_up as time, 'student' as type
        FROM students
        WHERE next_follow_up <= ? AND status != 'dropout'
        ORDER BY next_follow_up ASC
    ");
    $stmt1->execute([$today]);
    while($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $alerts[] = $row;
    }

    // 2. Lead Reminders
    $stmt2 = $pdo->prepare("
        SELECT id, name, phone, notes as note, next_followup_datetime as time, 'lead' as type
        FROM leads
        WHERE next_followup_datetime <= ? AND status NOT IN ('converted', 'not_interested')
        ORDER BY next_followup_datetime ASC
    ");
    // Append today's end time to catch all for today
    $stmt2->execute([$today . ' 23:59:59']);
    while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $alerts[] = $row;
    }

    return $alerts;
}
?>
