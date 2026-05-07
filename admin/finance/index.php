<?php
// =====================================================
// ISSD Management - Admin: Master Financial Hub
// admin/finance/index.php
// =====================================================
define('PAGE_TITLE', 'Financial Command Center');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

// Fetch Consolidated Stats
$stats = getFinancialStats($pdo);
$today = date('Y-m-d');
$next7Days = date('Y-m-d', strtotime('+7 days'));

// Fetch Upcoming Student Dues (Next 7 Days)
$upcomingDues = $pdo->prepare("
    SELECT p.*, s.full_name, s.student_id as student_reg, c.course_name
    FROM student_payments p
    JOIN students s ON p.student_id = s.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.balance > 0 
      AND p.next_due_date BETWEEN ? AND ?
    ORDER BY p.next_due_date ASC
");
$upcomingDues->execute([$today, $next7Days]);
$upcomingItems = $upcomingDues->fetchAll();

// Fetch Overdue Student Dues
$overdueDues = $pdo->prepare("
    SELECT p.*, s.full_name, s.student_id as student_reg, c.course_name
    FROM student_payments p
    JOIN students s ON p.student_id = s.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.balance > 0 
      AND p.next_due_date < ?
    ORDER BY p.next_due_date DESC
");
$overdueDues->execute([$today]);
$overdueItems = $overdueDues->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';

$highlightId = (int)($_GET['highlight_id'] ?? 0);
?>

<style>
@keyframes pulse-highlight {
  0% { background-color: rgba(91, 78, 250, 0.1); }
  50% { background-color: rgba(91, 78, 250, 0.25); }
  100% { background-color: rgba(91, 78, 250, 0.1); }
}
.row-highlight {
  animation: pulse-highlight 2s infinite;
  border-left: 5px solid var(--primary) !important;
}

/* Mobile-only fixes */
@media (max-width: 768px) {
  /* Stack page header vertically */
  #page-content > .d-flex.justify-content-between {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 12px !important;
    margin-bottom: 16px !important;
  }
  /* Stack action buttons vertically */
  #page-content > .d-flex.justify-content-between .d-flex.gap-2 {
    flex-direction: column !important;
    width: 100% !important;
    gap: 8px !important;
  }
  #page-content > .d-flex.justify-content-between .d-flex.gap-2 a {
    width: 100% !important;
    text-align: center !important;
    justify-content: center !important;
  }

  /* Financial summary card: 2x2 grid on mobile */
  .card-lms .row > .col-md-3 {
    flex: 0 0 50% !important;
    max-width: 50% !important;
    border-right: none !important;
    padding: 10px 12px !important;
    text-align: center !important;
  }
  .card-lms .row > .col-md-3 h3 {
    font-size: 1rem !important;
  }
  .card-lms .row > .col-md-3 .x-small {
    font-size: 9px !important;
  }
  /* Add dividers for 2x2 layout */
  .card-lms .row > .col-md-3:nth-child(1),
  .card-lms .row > .col-md-3:nth-child(2) {
    border-bottom: 1px solid rgba(255,255,255,0.1) !important;
  }
  .card-lms .row > .col-md-3:nth-child(1),
  .card-lms .row > .col-md-3:nth-child(3) {
    border-right: 1px solid rgba(255,255,255,0.1) !important;
  }
}
</style>

<div id="page-content">
    <div class="d-flex justify-content-between align-items-center mb-30">
        <div>
            <h2 class="fw-800 text-dark mb-1">Financial Oversight</h2>
            <div class="text-muted small">Real-time revenue and expenditure tracking</div>
        </div>
        <div class="d-flex gap-2">
            <a href="../payments/add.php" class="btn-primary-grad shadow-sm">
                <i class="fas fa-plus me-2"></i>New Student Payment
            </a>
            <a href="../lecturer_payments/add.php" class="btn-lms btn-outline px-4 shadow-sm" style="border-radius:50px;">
                <i class="fas fa-hand-holding-dollar me-2"></i>Pay Lecturer
            </a>
        </div>
    </div>

    <div class="row g-4 mb-20">
        <div class="col-md-3">
            <div class="bento-card">
                <div class="stat-header">
                    <div class="stat-icon emerald"><i class="fas fa-wallet"></i></div>
                    <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> Collected</div>
                </div>
                <div class="stat-value">Rs. <?= number_format($stats['monthly_income'] / 1000, 1) ?>k</div>
                <div class="stat-label">Income (<?= date('M') ?>)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bento-card">
                <div class="stat-header">
                    <div class="stat-icon rose"><i class="fas fa-hand-holding-dollar"></i></div>
                    <div class="stat-trend trend-down"><i class="fas fa-clock"></i> Pending</div>
                </div>
                <div class="stat-value">Rs. <?= number_format($stats['total_outstanding'] / 1000, 1) ?>k</div>
                <div class="stat-label">Total Receivables</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bento-card">
                <div class="stat-header">
                    <div class="stat-icon amber"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="stat-trend trend-neutral"><i class="fas fa-exclamation-triangle"></i> 7 Days</div>
                </div>
                <div class="stat-value"><?= count($upcomingItems) ?></div>
                <div class="stat-label">Upcoming Dues</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bento-card">
                <div class="stat-header">
                    <div class="stat-icon indigo"><i class="fas fa-receipt"></i></div>
                    <div class="stat-trend trend-up"><i class="fas fa-check-circle"></i> Done</div>
                </div>
                <div class="stat-value">Rs. <?= number_format($stats['monthly_expense'] / 1000, 1) ?>k</div>
                <div class="stat-label">Total Payouts</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-30">
        <!-- Upcoming Payments Column -->
        <div class="col-lg-4">
            <div class="card-lms h-100 border shadow-sm">
                <div class="card-lms-header border-bottom bg-white py-3 px-3">
                    <div class="card-lms-title fw-700" style="font-size:13px;">
                        <i class="fas fa-calendar-alt text-primary me-2"></i> Upcoming Collections
                    </div>
                </div>
                <div class="card-lms-body p-0">
                    <?php if (empty($upcomingItems)): ?>
                        <div class="py-4 text-center">
                            <div class="text-muted small fw-500">No collections scheduled.</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0" style="font-size:12px;">
                                <tbody>
                                    <?php foreach (array_slice($upcomingItems, 0, 4) as $item): ?>
                                        <tr class="border-bottom-light">
                                            <td class="ps-3 py-2">
                                                <div class="fw-600 text-dark"><?= htmlspecialchars($item['full_name']) ?></div>
                                            </td>
                                            <td><?= date('d M', strtotime($item['next_due_date'])) ?></td>
                                            <td class="text-end pe-3 fw-700">Rs. <?= number_format($item['balance'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Student Payouts -->
        <div class="col-lg-4">
            <div class="card-lms h-100 border shadow-sm">
                <div class="card-lms-header border-bottom bg-white py-3 px-3">
                    <div class="card-lms-title fw-700" style="font-size:13px;">
                        <i class="fas fa-history text-success me-2"></i> Recent Receipts
                    </div>
                </div>
                <div class="card-lms-body p-0">
                    <?php if (empty($recentStudentPayments)): ?>
                        <div class="py-4 text-center text-muted x-small">No recent receipts.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0" style="font-size:12px;">
                                <tbody>
                                    <?php foreach ($recentStudentPayments as $p): ?>
                                        <tr class="border-bottom-light">
                                            <td class="ps-3 py-2">
                                                <div class="fw-600 text-dark"><?= htmlspecialchars($p['full_name']) ?></div>
                                            </td>
                                            <td class="text-muted"><?= date('d M', strtotime($p['payment_date'])) ?></td>
                                            <td class="text-end pe-3 text-success fw-700">+<?= number_format($p['amount_paid'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Lecturer Payouts -->
        <div class="col-lg-4">
            <div class="card-lms h-100 border shadow-sm">
                <div class="card-lms-header border-bottom bg-white py-3 px-3">
                    <div class="card-lms-title fw-700" style="font-size:13px;">
                        <i class="fas fa-user-tie text-info me-2"></i> Recent Lecturer Payouts
                    </div>
                </div>
                <div class="card-lms-body p-0">
                    <?php if (empty($recentLecturerPayments)): ?>
                        <div class="py-4 text-center text-muted x-small">No recent payouts.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0" style="font-size:12px;">
                                <tbody>
                                    <?php foreach ($recentLecturerPayments as $lp): ?>
                                        <tr class="border-bottom-light">
                                            <td class="ps-3 py-2">
                                                <div class="fw-600 text-dark"><?= htmlspecialchars($lp['lecturer_name']) ?></div>
                                            </td>
                                            <td class="text-muted"><?= date('d M', strtotime($lp['payment_date'])) ?></td>
                                            <td class="text-end pe-3 text-danger fw-700">-<?= number_format($lp['amount'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Summary Section -->
    <div class="card-lms border-0 shadow-lg mb-30" style="background: linear-gradient(135deg, #1e4d4d 0%, #133333 100%); border-radius: 16px;">
        <div class="card-lms-body p-4">
            <div class="row align-items-center text-center text-md-start">
                <div class="col-md-3 border-end border-white border-opacity-10 mb-3 mb-md-0">
                    <div class="text-white opacity-60 x-small text-uppercase fw-600 mb-1">Monthly Revenue</div>
                    <h3 class="text-white fw-700 mb-0" style="font-size:1.3rem;">Rs. <?= number_format($stats['monthly_income'], 2) ?></h3>
                </div>
                <div class="col-md-3 border-end border-white border-opacity-10 mb-3 mb-md-0">
                    <div class="text-white opacity-60 x-small text-uppercase fw-600 mb-1">Total Payouts</div>
                    <h3 class="text-white fw-700 mb-0" style="font-size:1.3rem;">Rs. <?= number_format($stats['monthly_expense'], 2) ?></h3>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="text-white opacity-60 x-small text-uppercase fw-600 mb-1">Net Position</div>
                    <h3 class="text-white fw-700 mb-0" style="font-size:1.3rem;">Rs. <?= number_format($stats['monthly_income'] - $stats['monthly_expense'], 2) ?></h3>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 p-3 rounded-3 border border-white border-opacity-20 text-center">
                        <div class="text-white opacity-80 x-small fw-700 mb-1"><i class="fas fa-sync-alt fa-spin me-1"></i> SYSTEM SNAPSHOT</div>
                        <div class="text-white fw-500 x-small"><?= date('D, d M Y | H:i') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const highlightedRow = document.querySelector('.row-highlight');
    if (highlightedRow) {
        setTimeout(() => {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
});
</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
