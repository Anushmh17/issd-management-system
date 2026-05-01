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
</style>

<div id="page-content">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Financial Oversight Dashboard</h1>
            <div class="breadcrumb-custom">
                <i class="fas fa-home"></i> Admin &rsaquo; <span>Financial Hub</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="../payments/add.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-plus me-2"></i>New Student Payment
            </a>
            <a href="../lecturer_payments/add.php" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-hand-holding-dollar me-2"></i>Pay Lecturer
            </a>
        </div>
    </div>

    <!-- Master Stats -->
    <div class="row g-4 mb-50">
        <div class="col-md-4">
            <div class="stat-card" style="--sc-color: var(--accent);">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-body">
                    <div class="stat-value">Rs. <?= number_format($stats['monthly_income'], 2) ?></div>
                    <div class="stat-label">Total Collected (<?= date('M') ?>)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="--sc-color: var(--danger);">
                <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-body">
                    <div class="stat-value">Rs. <?= number_format($stats['total_outstanding'], 2) ?></div>
                    <div class="stat-label">Total Outstanding Debt</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="--sc-color: var(--warning);">
                <div class="stat-icon"><i class="fas fa-users-viewfinder"></i></div>
                <div class="stat-body">
                    <div class="stat-value"><?= count($overdueItems) ?></div>
                    <div class="stat-label">Overdue Student Accounts</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Upcoming Payments Column -->
        <div class="col-lg-6">
            <div class="card-lms h-100">
                <div class="card-lms-header d-flex justify-content-between align-items-center">
                    <div class="card-lms-title">
                        <i class="fas fa-clock text-warning"></i> Upcoming Dues (Next 7 Days)
                    </div>
                </div>
                <div class="card-lms-body p-0">
                    <?php if (empty($upcomingItems)): ?>
                        <div class="p-4 text-center text-muted">No payments due this week.</div>
                    <?php else: ?>
                        <table class="table-lms mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Due Date</th>
                                    <th>Balance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingItems as $item): ?>
                                    <tr class="<?= ($highlightId === (int)$item['id']) ? 'row-highlight' : '' ?>">
                                        <td>
                                            <div class="fw-700"><?= htmlspecialchars($item['full_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['course_name']) ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?= date('d M', strtotime($item['next_due_date'])) ?></span></td>
                                        <td class="fw-800 text-primary">Rs. <?= number_format($item['balance'], 2) ?></td>
                                        <td>
                                            <a href="../payments/add.php?student_id=<?= $item['student_id'] ?>&course_id=<?= $item['course_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Record Payment">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Overdue Payments Column -->
        <div class="col-lg-6">
            <div class="card-lms h-100 border-danger-subtle">
                <div class="card-lms-header d-flex justify-content-between align-items-center bg-danger-subtle">
                    <div class="card-lms-title text-danger">
                        <i class="fas fa-triangle-exclamation"></i> Critical Overdue Accounts
                    </div>
                </div>
                <div class="card-lms-body p-0">
                    <?php if (empty($overdueItems)): ?>
                        <div class="p-4 text-center text-muted">No overdue accounts found.</div>
                    <?php else: ?>
                        <table class="table-lms mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Was Due</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueItems as $item): ?>
                                    <tr class="table-danger <?= ($highlightId === (int)$item['id']) ? 'row-highlight' : '' ?>">
                                        <td>
                                            <div class="fw-700"><?= htmlspecialchars($item['full_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['student_reg']) ?></small>
                                        </td>
                                        <td><span class="text-danger fw-700"><?= date('d M Y', strtotime($item['next_due_date'])) ?></span></td>
                                        <td class="fw-800 text-danger">Rs. <?= number_format($item['balance'], 2) ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="../payments/add.php?student_id=<?= $item['student_id'] ?>&course_id=<?= $item['course_id'] ?>" 
                                                   class="btn btn-sm btn-danger" title="Process Payment">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" title="Send Nudge" onclick="alert('Nudge sent to student via In-App notification!')">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Analytics Section -->
    <div class="row g-4 mt-1">
        <div class="col-md-12">
            <div class="card-lms">
                <div class="card-lms-header">
                    <div class="card-lms-title"><i class="fas fa-file-invoice-dollar"></i> Financial Summary (This Month)</div>
                </div>
                <div class="card-lms-body">
                    <div class="row text-center">
                        <div class="col-md-4 border-end">
                            <h4 class="text-success mb-1">Rs. <?= number_format($stats['monthly_income'], 2) ?></h4>
                            <p class="text-muted small mb-0">Total Income</p>
                        </div>
                        <div class="col-md-4 border-end">
                            <h4 class="text-danger mb-1">Rs. <?= number_format($stats['monthly_expense'], 2) ?></h4>
                            <p class="text-muted small mb-0">Lecturer Payouts</p>
                        </div>
                        <div class="col-md-4">
                            <h4 class="<?= ($stats['monthly_income'] - $stats['monthly_expense'] >= 0) ? 'text-primary' : 'text-danger' ?> mb-1">
                                Rs. <?= number_format($stats['monthly_income'] - $stats['monthly_expense'], 2) ?>
                            </h4>
                            <p class="text-muted small mb-0">Net Position</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
