<?php
// =====================================================
// LEARN Management - Lecturer Payment Controller
// backend/lecturer_payment_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// -------------------------------------------------------
// Add Lecturer Payment
// -------------------------------------------------------
function addLecturerPayment(PDO $pdo, array $d): array {
    $errors = [];
    $lecturerId = (int)($d['lecturer_id'] ?? 0);
    $amount = (float)($d['amount'] ?? 0);
    $paymentMonth = trim($d['payment_month'] ?? '');
    $status = trim($d['status'] ?? 'pending');
    $notes = trim($d['notes'] ?? '');

    if (!$lecturerId) $errors[] = 'Lecturer is required.';
    if ($amount <= 0) $errors[] = 'Amount must be greater than 0.';
    if (!$paymentMonth) $errors[] = 'Payment month is required.';

    if ($errors) return ['success' => false, 'errors' => $errors];

    $paymentDate = ($status === 'paid') ? date('Y-m-d H:i:s') : null;

    try {
        $pdo->prepare("
            INSERT INTO lecturer_payments (lecturer_id, amount, payment_month, payment_date, status, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$lecturerId, $amount, $paymentMonth, $paymentDate, $status, $notes]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('addLecturerPayment: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to add payment.']];
    }
}

// -------------------------------------------------------
// Get Lecturer Payments List
// -------------------------------------------------------
function getLecturerPaymentsList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 15): array {
    $where  = [];
    $params = [];

    $status = trim($filters['status'] ?? '');
    if ($status) {
        $where[]  = "p.status = ?";
        $params[] = $status;
    }
    
    $month = trim($filters['month'] ?? '');
    if ($month) {
        $where[]  = "p.payment_month = ?";
        $params[] = $month;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM lecturer_payments p {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT p.*, l.name as lecturer_name
        FROM lecturer_payments p
        JOIN lecturers l ON p.lecturer_id = l.id
        {$whereSQL}
        ORDER BY p.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    return compact('payments','total','pages','page');
}

// -------------------------------------------------------
// Mark Payment as Paid
// -------------------------------------------------------
function markLecturerPaymentPaid(PDO $pdo, int $paymentId): bool {
    try {
        $pdo->prepare("
            UPDATE lecturer_payments 
            SET status = 'paid', payment_date = NOW() 
            WHERE id = ? AND status = 'pending'
        ")->execute([$paymentId]);
        return true;
    } catch (PDOException $e) {
        error_log('markLecturerPaymentPaid: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Get Pending Lecturer Payments Summary (For Dashboard)
// -------------------------------------------------------
function getPendingLecturerPaymentsSummary(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT COUNT(*) as pending_count, SUM(amount) as pending_total
        FROM lecturer_payments 
        WHERE status = 'pending'
    ");
    return $stmt->fetch();
}
