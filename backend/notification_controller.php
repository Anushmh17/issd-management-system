<?php
// =====================================================
// LEARN Management - Notification Controller
// backend/notification_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Get recent notifications for a specific user based on their role
 * Combines Notices, Payment Alerts, and potentially others.
 */
function getRecentNotifications(PDO $pdo, int $userId, string $role): array {
    $notifications = [];

    // 1. Get notices from the 'notices' table
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.content as message, n.created_at, 'info' as type, 'fa-bullhorn' as icon,
               CASE WHEN rn.notice_id IS NOT NULL THEN 1 ELSE 0 END as is_read
        FROM notices n
        LEFT JOIN read_notices rn ON n.id = rn.notice_id AND rn.user_id = ?
        WHERE n.target_role IN ('all', ?)
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId, $role]);
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($notices as $n) {
        $notifications[] = [
            'id'      => 'notice_' . $n['id'],
            'real_id' => $n['id'],
            'title'   => $n['title'],
            'body'    => $n['message'],
            'time'    => $n['created_at'],
            'type'    => $n['type'],
            'icon'    => $n['icon'],
            'is_read' => (bool)$n['is_read'],
            'link'    => BASE_URL . "/frontend/{$role}/notices.php"
        ];
    }

    // 2. Role-specific alerts
    if ($role === 'student') {
        // Get actual student ID from student_profiles or students table
        // Note: Earlier fixes used 'students' table. Let's find student_id.
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        $student = $stmt->fetch();
        
        if ($student) {
            require_once __DIR__ . '/alert_system.php';
            $paymentAlerts = getStudentPaymentAlerts($pdo, (int)$student['id']);
            foreach ($paymentAlerts as $pa) {
                $notifications[] = [
                    'id'    => 'pay_' . uniqid(),
                    'title' => $pa['title'],
                    'body'  => $pa['message'],
                    'time'  => date('Y-m-d H:i:s'), // Current context
                    'type'  => $pa['type'],
                    'icon'  => $pa['icon'],
                    'link'  => BASE_URL . '/frontend/student/payments.php'
                ];
            }
        }
    } elseif ($role === 'admin') {
        // Admin alerts for pending payments or new leads
        // Overdue payments for ANY student
        $stmt = $pdo->query("SELECT COUNT(*) FROM student_payments WHERE status = 'overdue'");
        $overdueCount = (int)$stmt->fetchColumn();
        if ($overdueCount > 0) {
            $notifications[] = [
                'id'    => 'adm_overdue',
                'title' => 'Overdue Payments',
                'body'  => "There are {$overdueCount} student payments categorized as Overdue.",
                'time'  => date('Y-m-d H:i:s'),
                'type'  => 'danger',
                'icon'  => 'fa-triangle-exclamation',
                'link'  => BASE_URL . '/frontend/admin/reports.php'
            ];
        }
        
        // New leads from last 24 hours
        $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $newLeads = (int)$stmt->fetchColumn();
        if ($newLeads > 0) {
            $notifications[] = [
                'id'    => 'adm_leads',
                'title' => 'New Leads Found',
                'body'  => "You received {$newLeads} new leads in the last 24 hours.",
                'time'  => date('Y-m-d H:i:s'),
                'type'  => 'success',
                'icon'  => 'fa-user-tag',
                'link'  => BASE_URL . '/admin/leads/index.php'
            ];
        }
    }

    // Sort all by time (descending)
    usort($notifications, function($a, $b) {
        return strtotime($b['time']) <=> strtotime($a['time']);
    });

    return $notifications;
}

/**
 * Human readable time diff
 */
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return date('d M', $time);
}
