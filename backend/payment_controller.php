<?php
// =====================================================
// ISSD Management - Payment Controller
// backend/payment_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// -------------------------------------------------------
// Auto-update overdue records on every load (safety mechanism)
// -------------------------------------------------------
function syncOverduePayments(PDO $pdo) {
    try {
        // 1. Update status to 'overdue' in DB
        $pdo->exec("
            UPDATE student_payments 
            SET status = 'overdue' 
            WHERE CURRENT_DATE > next_due_date 
              AND status NOT IN ('paid', 'overdue')
        ");

        // 2. Generate Notifications for these overdue students
        $overdue = $pdo->query("
            SELECT p.id, s.user_id, s.full_name, p.balance, p.next_due_date 
            FROM student_payments p
            JOIN students s ON p.student_id = s.id
            WHERE p.status = 'overdue'
        ")->fetchAll();

        foreach ($overdue as $o) {
            // A. Admin Notification
            $adminTitle = "Payment Overdue: " . $o['full_name'];
            $adminMsg = "A payment of Rs. " . number_format($o['balance'], 2) . " was due on " . $o['next_due_date'] . ".";
            $adminLink = BASE_URL . "/admin/payments/index.php?highlight_id=" . $o['id'];
            
            $checkAdmin = $pdo->prepare("SELECT id FROM notifications WHERE user_id IS NULL AND title = ? AND status = 'unread' LIMIT 1");
            $checkAdmin->execute([$adminTitle]);
            if (!$checkAdmin->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, link, status) VALUES ('payment', ?, ?, ?, 'unread')");
                $stmt->execute([$adminTitle, $adminMsg, $adminLink]);
            }

            // B. Student Notification
            $studentTitle = "Payment Overdue Notice";
            $studentMsg = "Your payment of Rs. " . number_format($o['balance'], 2) . " was due on " . $o['next_due_date'] . ". Please clear it as soon as possible.";
            
            $checkStudent = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND title = ? AND status = 'unread' LIMIT 1");
            $checkStudent->execute([$o['user_id'], $studentTitle]);
            if (!$checkStudent->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, status) VALUES (?, 'payment', ?, ?, 'unread')");
                $stmt->execute([$o['user_id'], $studentTitle, $studentMsg]);
            }
        }
    } catch (PDOException $e) {
        error_log('syncOverduePayments: ' . $e->getMessage());
    }
}

// -------------------------------------------------------
// Trigger In-App Notifications for Upcoming Dues
// -------------------------------------------------------
function syncUpcomingPayments(PDO $pdo) {
    try {
        // Find payments due in exactly 5 days or 1 day
        $upcoming = $pdo->query("
            SELECT p.id, s.user_id, s.full_name, p.balance, p.next_due_date,
                   DATEDIFF(p.next_due_date, CURRENT_DATE) as days_left
            FROM student_payments p
            JOIN students s ON p.student_id = s.id
            WHERE p.balance > 0 
              AND p.status NOT IN ('paid', 'overdue')
              AND DATEDIFF(p.next_due_date, CURRENT_DATE) IN (5, 1)
        ")->fetchAll();

        foreach ($upcoming as $u) {
            $days = $u['days_left'];
            $title = "Payment Reminder: " . ($days == 1 ? "Due Tomorrow" : "Due in 5 Days");
            $msg = "Dear " . $u['full_name'] . ", a payment of Rs. " . number_format($u['balance'], 2) . " is due on " . $u['next_due_date'] . ".";
            
            // Check if notification already exists for this specific day to avoid spam
            $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND title = ? AND created_at >= CURRENT_DATE LIMIT 1");
            $check->execute([$u['user_id'], $title]);
            
            if (!$check->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, status) VALUES (?, 'payment', ?, ?, 'unread')");
                $stmt->execute([$u['user_id'], $title, $msg]);
            }
        }
    } catch (PDOException $e) {
        error_log('syncUpcomingPayments: ' . $e->getMessage());
    }
}

// -------------------------------------------------------
// Get Student Latest Payment Info to Calculate Dues
// -------------------------------------------------------
function getPaymentInfoForm(PDO $pdo, int $studentId, int $courseId, ?string $targetMonth = null): array {
    $targetMonth = $targetMonth ?: date('Y-m');
    // 1. Get course fee
    $stmt1 = $pdo->prepare("SELECT monthly_fee FROM courses WHERE id = ?");
    $stmt1->execute([$courseId]);
    $courseFee = (float)$stmt1->fetchColumn();

    // 2. Check if a payment for THIS month already exists
    $stmtCheck = $pdo->prepare("
        SELECT id FROM student_payments 
        WHERE student_id = ? AND course_id = ? AND month = ?
        LIMIT 1
    ");
    $stmtCheck->execute([$studentId, $courseId, $targetMonth]);
    $existsThisMonth = $stmtCheck->fetch();

    // 3. Get latest balance
    $stmt2 = $pdo->prepare("
        SELECT balance 
        FROM student_payments 
        WHERE student_id = ? AND course_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt2->execute([$studentId, $courseId]);
    $prevBalance = $stmt2->fetchColumn();
    if ($prevBalance === false) $prevBalance = 0.00;

    // Logic: If they already have a record for this month, total due is just the balance of that record.
    // If NOT, total due is courseFee + prevBalance.
    $totalDue = $existsThisMonth ? (float)$prevBalance : ($courseFee + (float)$prevBalance);
    $feeToApply = $existsThisMonth ? 0.00 : $courseFee;

    return [
        'monthly_fee'      => (float)$feeToApply,
        'previous_balance' => (float)$prevBalance,
        'total_due'        => (float)$totalDue
    ];
}

// -------------------------------------------------------
// Add Payment Record
// -------------------------------------------------------
function addPayment(PDO $pdo, array $d): array {
    $studentId     = (int)($d['student_id'] ?? 0);
    $courseId      = (int)($d['course_id']  ?? 0);
    $amountPaid    = (float)($d['amount_paid'] ?? 0);
    $month         = trim($d['month'] ?? date('Y-m'));
    $method        = trim($d['method'] ?? 'cash');
    $reference     = trim($d['reference'] ?? '');

    if (!$studentId || !$courseId) {
        return ['success' => false, 'errors' => ['Student and Course are required.']];
    }
    if ($amountPaid <= 0) {
        return ['success' => false, 'errors' => ['Amount paid must be greater than zero.']];
    }

    $info = getPaymentInfoForm($pdo, $studentId, $courseId);
    $totalDue = $info['total_due'];

    $balance = $totalDue - $amountPaid;
    $status  = ($balance <= 0) ? 'paid' : 'partial';

    // Next due date logic
    $nextDueDate = !empty($d['next_due_date']) ? $d['next_due_date'] : date('Y-m-d', strtotime('+1 month'));

    try {
        $pdo->prepare("
            INSERT INTO student_payments 
            (student_id, course_id, month, monthly_fee, previous_balance, total_due, amount_paid, balance, status, payment_date, next_due_date, method, reference)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ")->execute([
            $studentId, 
            $courseId, 
            $month, 
            $info['monthly_fee'], 
            $info['previous_balance'], 
            $totalDue, 
            $amountPaid, 
            $balance, 
            $status, 
            $nextDueDate,
            $method,
            $reference
        ]);
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log('addPayment: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to process payment. Please ensure database schema is updated with method/reference columns.']];
    }
}

// -------------------------------------------------------
// Get Payments List
// -------------------------------------------------------
function getPaymentsList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 15): array {
    syncOverduePayments($pdo); // Ensure overdue status is accurate before fetch
    syncUpcomingPayments($pdo); // Ensure upcoming alerts are sent before fetch

    $where  = [];
    $params = [];

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $where[]  = "(s.full_name LIKE ? OR s.student_id LIKE ? OR c.course_code LIKE ?)";
        $like = "%{$search}%";
        $params = array_merge($params, [$like,$like,$like]);
    }

    $status = trim($filters['status'] ?? '');
    if ($status !== '') {
        $where[]  = "p.status = ?";
        $params[] = $status;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM student_payments p
        JOIN students s ON p.student_id = s.id
        JOIN courses c ON p.course_id = c.id
        {$whereSQL}
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT p.*, s.full_name, s.student_id as student_reg, c.course_name, c.course_code
        FROM student_payments p
        JOIN students s ON p.student_id = s.id
        JOIN courses c ON p.course_id = c.id
        {$whereSQL}
        ORDER BY p.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    return compact('payments','total','pages','page');
}

// -------------------------------------------------------
// Get all active students with their courses
// -------------------------------------------------------
function getStudentsWithActiveCourses(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT s.id as student_id, s.full_name, s.student_id as student_reg, 
               c.id as course_id, c.course_name, c.course_code, c.monthly_fee
        FROM student_courses sc
        JOIN students s ON sc.student_id = s.id
        JOIN courses c ON sc.course_id = c.id
        WHERE sc.status IN ('ongoing','completed')
        ORDER BY s.full_name ASC
    ");
    return $stmt->fetchAll();
}

// -------------------------------------------------------
// Lecturer Payment Functions
// -------------------------------------------------------
function addLecturerPayment(PDO $pdo, array $d): array {
    $lecturerId   = (int)($d['lecturer_id'] ?? 0);
    $amount       = (float)($d['amount'] ?? 0);
    $paymentMonth = trim($d['month'] ?? date('Y-m'));
    $notes        = trim($d['notes'] ?? '');

    if (!$lecturerId || $amount <= 0) {
        return ['success' => false, 'errors' => ['Lecturer and Amount are required.']];
    }

    try {
        $pdo->prepare("
            INSERT INTO lecturer_payments (lecturer_id, amount, payment_month, payment_date, status, notes)
            VALUES (?, ?, ?, NOW(), 'paid', ?)
        ")->execute([$lecturerId, $amount, $paymentMonth, $notes]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'errors' => [$e->getMessage()]];
    }
}

function getLecturerPaymentsList(PDO $pdo, int $page = 1): array {
    $perPage = 15;
    $total = (int)$pdo->query("SELECT COUNT(*) FROM lecturer_payments")->fetchColumn();
    $pages = (int)ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT lp.*, u.name as lecturer_name 
        FROM lecturer_payments lp
        JOIN users u ON lp.lecturer_id = u.id
        ORDER BY lp.payment_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
    return ['payments' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages];
}

// -------------------------------------------------------
// Financial Overview Stats
// -------------------------------------------------------
function getFinancialStats(PDO $pdo): array {
    $thisMonth = date('Y-m');
    
    $income = (float)$pdo->query("SELECT SUM(amount_paid) FROM student_payments WHERE month = '$thisMonth'")->fetchColumn();
    $expense = (float)$pdo->query("SELECT SUM(amount) FROM lecturer_payments WHERE payment_month = '$thisMonth'")->fetchColumn();
    $pending = (float)$pdo->query("SELECT SUM(balance) FROM student_payments WHERE status != 'paid'")->fetchColumn();

    return [
        'monthly_income' => $income,
        'monthly_expense' => $expense,
        'total_outstanding' => $pending
    ];
}

