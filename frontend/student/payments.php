<?php
// =====================================================
// LEARN Management - Student: Payments History
// frontend/student/payments.php
// =====================================================
define('PAGE_TITLE', 'Payment History');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);

$userId = currentUserId();

// Find specific student_id
$studentStmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$studentStmt->execute([$userId]);
$studentId = (int)$studentStmt->fetchColumn();

// Fetch payments
$payments = [];
if ($studentId) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.course_name, c.course_code
        FROM student_payments p
        JOIN courses c ON p.course_id = c.id
        WHERE p.student_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$studentId]);
    $payments = $stmt->fetchAll();
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Payments</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Student &rsaquo; <span>Payments</span></div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-receipt" style="color:#059669;"></i> Transaction Ledger</div>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
        <?php if(empty($payments)): ?>
            <div class="empty-state"><i class="fas fa-wallet"></i><p>No payment records found.</p></div>
        <?php else: ?>
        <table class="table-lms">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Month</th>
                    <th>Course</th>
                    <th>Monthly Fee</th>
                    <th>Amount Paid</th>
                    <th>Balance Due</th>
                    <th>Next Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payments as $p): ?>
                <tr>
                    <td style="font-size:12px;color:#64748b;"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                    <td class="fw-600"><?= date('M Y', strtotime($p['month'] . '-01')) ?></td>
                    <td>
                        <div class="fw-600" style="font-size:12px;"><?= htmlspecialchars($p['course_code']) ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($p['course_name']) ?></div>
                    </td>
                    <td>Rs. <?= number_format($p['monthly_fee'], 2) ?></td>
                    <td class="fw-700" style="color:#059669;">Rs. <?= number_format($p['amount_paid'], 2) ?></td>
                    <td class="fw-700" style="color:#dc2626;">Rs. <?= number_format($p['balance'], 2) ?></td>
                    <td style="font-size:12px;color:#d97706;font-weight:600;"><i class="fas fa-clock"></i> <?= date('d M Y', strtotime($p['next_due_date'])) ?></td>
                    <td>
                        <?php if ($p['status'] === 'paid'): ?>
                            <span class="badge-lms" style="background:#d1fae5;color:#059669;">Paid</span>
                        <?php elseif ($p['status'] === 'partial'): ?>
                            <span class="badge-lms" style="background:#fef3c7;color:#d97706;">Partial</span>
                        <?php elseif ($p['status'] === 'overdue'): ?>
                            <span class="badge-lms" style="background:#fee2e2;color:#dc2626;">Overdue</span>
                        <?php else: ?>
                            <span class="badge-lms" style="background:#f1f5f9;color:#64748b;">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
