<?php
// =====================================================
// ISSD Management - Alert & Reminder System
// backend/alert_system.php
// =====================================================

// Allow running from CLI directly or via HTTP
if (php_sapi_name() === 'cli' || (isset($_GET['cron']) && $_GET['cron'] == '1')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    runDailyCron($pdo);
    exit;
}

// -------------------------------------------------------
// Dashboard Alerts: For Student
// -------------------------------------------------------
function getStudentPaymentAlerts(PDO $pdo, int $studentId): array {
    $alerts = [];
    $now = date('Y-m-d');
    
    // Check for nearest upcoming due dates (within 7 days) and overdues
    $stmt = $pdo->prepare("
        SELECT p.id, p.next_due_date, p.balance, p.status, c.course_name, c.course_code
        FROM student_payments p
        JOIN courses c ON p.course_id = c.id
        WHERE p.student_id = ? AND p.status IN ('pending', 'partial', 'overdue')
    ");
    $stmt->execute([$studentId]);
    $payments = $stmt->fetchAll();

    foreach ($payments as $p) {
        $due = $p['next_due_date'];
        $diffDays = (strtotime($due) - strtotime($now)) / (60 * 60 * 24);

        if ($p['status'] === 'overdue' || $diffDays < 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-exclamation-circle',
                'title' => 'Payment Overdue!',
                'message' => "Your payment for <strong>{$p['course_code']} - {$p['course_name']}</strong> was due on " . date('d M Y', strtotime($due)) . ". Remaining Balance: Rs. " . number_format($p['balance'], 2) . "."
            ];
        } elseif ($diffDays >= 0 && $diffDays <= 7) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-clock',
                'title' => 'Payment Reminder',
                'message' => "Your payment for <strong>{$p['course_code']} - {$p['course_name']}</strong> is due soon on " . date('d M Y', strtotime($due)) . ". Remaining Balance: Rs. " . number_format($p['balance'], 2) . "."
            ];
        }
    }

    return $alerts;
}

// -------------------------------------------------------
// Dashboard Alerts: For Admin (Pending Lecturer Payments)
// -------------------------------------------------------
function getAdminLecturerAlerts(PDO $pdo): ?array {
    $stmt = $pdo->query("
        SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0) as tot
        FROM lecturer_payments 
        WHERE status = 'pending'
    ");
    $res = $stmt->fetch();
    
    if ($res['cnt'] > 0) {
        return [
            'type' => 'warning',
            'count' => $res['cnt'],
            'total' => $res['tot']
        ];
    }
    return null;
}

// -------------------------------------------------------
// Daily CRON Job script
// -------------------------------------------------------
function runDailyCron(PDO $pdo) {
    echo "Starting Daily Alert CRON job...\n";
    $now = date('Y-m-d');

    // 1. Sync overdues explicitly
    $pdo->exec("
        UPDATE student_payments 
        SET status = 'overdue' 
        WHERE CURRENT_DATE > next_due_date AND status NOT IN ('paid', 'overdue')
    ");
    echo "Synced overdue student payments.\n";

    // 2. Mock Email Notifications for Students (Near Due / Overdue)
    $stmt = $pdo->query("
        SELECT p.id, p.next_due_date, p.balance, p.status, c.course_name, s.full_name, u.email
        FROM student_payments p
        JOIN courses c ON p.course_id = c.id
        JOIN students s ON p.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE p.status IN ('pending', 'partial', 'overdue')
    ");
    $payments = $stmt->fetchAll();

    $emailsSent = 0;
    foreach ($payments as $p) {
        $due = $p['next_due_date'];
        $diffDays = (strtotime($due) - strtotime($now)) / (60 * 60 * 24);

        $subject = "";
        $body = "";

        if ($diffDays < 0) {
            $subject = "URGENT: Payment Overdue - {$p['course_name']}";
            $body = "Dear {$p['full_name']},\n\nYour payment for {$p['course_name']} was due on " . date('d M Y', strtotime($due)) . ".\nPlease clear your balance of Rs. " . number_format($p['balance'],2) . " immediately.";
        } elseif ($diffDays == 3 || $diffDays == 1) {
            $subject = "REMINDER: Payment Due in {$diffDays} Days - {$p['course_name']}";
            $body = "Dear {$p['full_name']},\n\nYour payment for {$p['course_name']} is due on " . date('d M Y', strtotime($due)) . ".\nRemaining Balance: Rs. " . number_format($p['balance'],2) . ".";
        }

        if ($subject && $p['email']) {
            // Mocking mail() function call here:
            // mail($p['email'], $subject, $body, "From: noreply@issd.com");
            error_log("CRON EMail -> To: {$p['email']} | Subject: {$subject}");
            $emailsSent++;
        }
    }
    
    // 3. Admin Notification (Lecturer Payments)
    $adminAlerts = getAdminLecturerAlerts($pdo);
    if ($adminAlerts && $adminAlerts['count'] > 0) {
        $subject = "ADMIN ALERT: Pending Lecturer Payments";
        $body = "There are {$adminAlerts['count']} pending lecturer payments totaling Rs. " . number_format($adminAlerts['total'], 2) . ". Please review.";
        // Mocking email to admin
        error_log("CRON EMail -> To: admin@issd.com | Subject: {$subject}");
        $emailsSent++;
    }

    echo "CRON Job Complete! Simulated {$emailsSent} emails sent.\n";
}


