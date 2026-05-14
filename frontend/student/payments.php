<?php
// =====================================================
// ISSD Management - Student: Payments History (Premium UI)
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

<div id="page-content" style="background: transparent; box-shadow: none;">
  <div class="dark-layout-wrapper">
    
    <div class="welcome-header">
      <h1>My Payments</h1>
      <p>Your transaction ledger and outstanding balances.</p>
    </div>

    <div class="glass-card" style="padding:0; padding-top:30px; overflow-x:auto;">
        <?php if(empty($payments)): ?>
          <div style="padding:40px; text-align:center;">
            <div style="width:64px; height:64px; border-radius:16px; background:rgba(255,255,255,0.05); color:#94a3b8; margin:0 auto 16px; font-size:24px; display:flex; align-items:center; justify-content:center;">
              <i class="fas fa-wallet"></i>
            </div>
            <h3 style="font-weight:700; font-size:18px; color:inherit; margin-bottom:8px;">No payment records found</h3>
            <p style="color:#94a3b8; font-size:14px; margin:0;">You haven't made any payments yet.</p>
          </div>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse; text-align:left;">
            <thead>
                <tr>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Date</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Month</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Course</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Monthly Fee</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Amount Paid</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Balance Due</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Next Due Date</th>
                    <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payments as $p): ?>
                <tr>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size:12px; color:#94a3b8;"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-weight:600; font-size:13px; color:inherit;"><?= date('M Y', strtotime($p['month'] . '-01')) ?></td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div style="font-weight:600; font-size:13px; color:inherit;"><span class="dark-badge db-blue"><?= htmlspecialchars($p['course_code']) ?></span></div>
                        <div style="font-size:11px; color:#94a3b8; margin-top:4px;"><?= htmlspecialchars($p['course_name']) ?></div>
                    </td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size:13px; color:inherit;">Rs. <?= number_format($p['monthly_fee'], 2) ?></td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-weight:700; font-size:13px; color:#4ade80;">Rs. <?= number_format($p['amount_paid'], 2) ?></td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-weight:700; font-size:13px; color:#f87171;">Rs. <?= number_format($p['balance'], 2) ?></td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size:12px; font-weight:600; color:#fbbf24;"><i class="fas fa-clock"></i> <?= date('d M Y', strtotime($p['next_due_date'])) ?></td>
                    <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <?php if ($p['status'] === 'paid'): ?>
                            <span class="dark-badge db-green">Paid</span>
                        <?php elseif ($p['status'] === 'partial'): ?>
                            <span class="dark-badge db-blue">Partial</span>
                        <?php elseif ($p['status'] === 'overdue'): ?>
                            <span class="dark-badge db-red">Overdue</span>
                        <?php else: ?>
                            <span class="dark-badge db-gray">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
  </div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
