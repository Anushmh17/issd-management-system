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
        $pdo->exec("
            UPDATE student_payments 
            SET status = 'overdue' 
            WHERE CURRENT_DATE > next_due_date 
              AND status NOT IN ('paid', 'overdue')
        ");
    } catch (PDOException $e) {
        error_log('syncOverduePayments: ' . $e->getMessage());
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
    $nextDueDate = date('Y-m-d', strtotime('+1 month'));

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

