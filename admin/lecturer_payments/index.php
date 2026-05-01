<?php
// =====================================================
// ISSD Management - Admin: Lecturer Payouts Hub
// admin/lecturer_payments/index.php
// =====================================================
define('PAGE_TITLE', 'Lecturer Payroll');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

$page = max(1, (int)($_GET['page'] ?? 1));
$result = getLecturerPaymentsList($pdo, $page);
$payments = $result['payments'];
$total = $result['total'];
$pages = $result['pages'];

$stats = getFinancialStats($pdo);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Lecturer Payouts & Payroll</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Staff Payments</span></div>
    </div>
    <a href="add.php" class="btn btn-primary rounded-pill px-4 fw-800 shadow-sm"><i class="fas fa-plus me-2"></i>Record Payout</a>
  </div>

  <div class="row g-4 mb-30">
    <div class="col-md-4">
        <div class="bento-card" style="border-bottom: 4px solid var(--info);">
            <div class="stat-label">Total Staff Payouts</div>
            <div class="stat-value">Rs. <?= number_format($stats['monthly_expense'], 2) ?></div>
            <div class="text-muted" style="font-size:11px;">Total recorded expenses for <?= date('F') ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bento-card" style="border-bottom: 4px solid var(--accent);">
            <div class="stat-label">Active Lecturers</div>
            <div class="stat-value"><?= $pdo->query("SELECT COUNT(*) FROM users WHERE role='lecturer'")->fetchColumn() ?></div>
            <div class="text-muted" style="font-size:11px;">Registered academic staff</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bento-card" style="background: var(--info); color: #fff;">
            <div class="stat-label text-white opacity-75">Payroll Status</div>
            <div class="stat-value" style="font-size: 24px;">Processing...</div>
            <div class="text-white opacity-75" style="font-size:11px;">Current cycle: <?= date('M Y') ?></div>
        </div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
        <div class="card-lms-title"><i class="fas fa-money-check-dollar"></i> Payout History</div>
    </div>
    <div class="card-lms-body p-0">
        <?php if (empty($payments)): ?>
            <div class="p-50 text-center text-muted">No lecturer payouts recorded yet.</div>
        <?php else: ?>
            <table class="table-lms mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Lecturer Name</th>
                        <th>Month</th>
                        <th>Amount Paid</th>
                        <th>Notes</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td style="font-size:12px;"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                        <td>
                            <div class="fw-800"><?= htmlspecialchars($p['lecturer_name']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark fw-800"><?= $p['payment_month'] ?></span></td>
                        <td class="fw-800 text-info">Rs. <?= number_format($p['amount'], 2) ?></td>
                        <td class="text-muted" style="font-size:12px; max-width:200px;"><?= htmlspecialchars($p['notes']) ?></td>
                        <td>
                            <span class="badge-lms" style="background:var(--accent-light); color:var(--accent-dark); font-size:10px;">COMPLETED</span>
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
