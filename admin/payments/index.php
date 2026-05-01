<?php
// =====================================================
// ISSD Management - Admin: Unified Payment Management Hub
// admin/payments/index.php
// =====================================================
define('PAGE_TITLE', 'Finance Hub');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'status');
$result  = getPaymentsList($pdo, $filters, $page, 15);
$payments = $result['payments'];
$total = $result['total'];
$pages = $result['pages'];

$stats = getFinancialStats($pdo);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
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
      <h1>Financial Management Hub</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Finance</span></div>
    </div>
    <div class="d-flex gap-2">
        <a href="alerts.php" class="btn btn-warning rounded-pill px-4 fw-800 shadow-sm"><i class="fas fa-bell me-2"></i>Alerts</a>
        <a href="add.php" class="btn-primary-grad px-4"><i class="fas fa-plus"></i> New Payment</a>
    </div>
  </div>

  <!-- Real-time Stats Ticker -->
  <div class="row g-3 mb-25">
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--accent);">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-body">
                <div class="stat-value">Rs. <?= number_format($stats['monthly_income'], 0) ?></div>
                <div class="stat-label">Income (<?= date('M') ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--danger);">
            <div class="stat-icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="stat-body">
                <div class="stat-value">Rs. <?= number_format($stats['total_outstanding'], 0) ?></div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--info);">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Receipts</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="--sc-color: var(--warning);">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= count(array_filter($payments, function($p){ return $p['status']=='overdue'; })) ?></div>
                <div class="stat-label">Urgent Issues</div>
            </div>
        </div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="list-legend">
            <div class="list-legend-label">Transaction Records</div>
            <div class="list-legend-title">Payment History</div>
        </div>

        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width: 600px;">
            <div class="search-bar flex-grow-1" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:0 15px; display:flex; align-items:center;">
                <i class="fas fa-search text-muted me-2"></i>
                <input type="text" name="search" placeholder="Search Student name or course..." class="border-0 bg-transparent py-2 w-100" value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="form-select border-0 bg-light" style="width:140px; border-radius:12px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="paid"    <?= $status==='paid'?'selected':'' ?>>Paid</option>
                <option value="partial" <?= $status==='partial'?'selected':'' ?>>Partial</option>
                <option value="overdue" <?= $status==='overdue'?'selected':'' ?>>Overdue</option>
            </select>
            <button type="submit" class="btn btn-primary rounded-3 px-3"><i class="fas fa-filter"></i></button>
        </form>
    </div>

    <div class="card-lms-body p-0 overflow-x-auto">
        <?php if (empty($payments)): ?>
            <div class="empty-state p-5 text-center">
                <i class="fas fa-receipt fa-4x mb-3 opacity-20"></i>
                <p class="text-muted">No transaction history found.</p>
            </div>
        <?php else: ?>
            <table class="table-lms mb-0">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Student Details</th>
                        <th>Course / Program</th>
                        <th>Amount Paid</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $highlightId = (int)($_GET['highlight_id'] ?? 0);
                    foreach ($payments as $p): 
                        $isHighlighted = ($highlightId === (int)$p['id']);
                    ?>
                    <tr class="<?= $isHighlighted ? 'row-highlight' : '' ?>" id="payment-<?= $p['id'] ?>">
                        <td>
                            <div class="fw-800 text-muted" style="font-size:11px;">#RCPT-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            <div style="font-size:10px;"><?= date('d M Y', strtotime($p['payment_date'])) ?></div>
                        </td>
                        <td>
                            <div class="fw-700"><?= htmlspecialchars($p['full_name']) ?></div>
                            <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($p['student_reg']) ?></div>
                        </td>
                        <td>
                            <div class="fw-600" style="font-size:12px;"><?= htmlspecialchars($p['course_code']) ?></div>
                            <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($p['course_name']) ?></div>
                        </td>
                        <td class="fw-800 text-success">Rs. <?= number_format($p['amount_paid'], 2) ?></td>
                        <td class="fw-800 text-danger">Rs. <?= number_format($p['balance'], 2) ?></td>
                        <td>
                            <?php 
                            $b_class = $p['status'] == 'paid' ? 'accent' : ($p['status'] == 'partial' ? 'warning' : 'danger');
                            ?>
                            <span class="badge-lms" style="background:var(--<?= $b_class ?>-light); color:var(--<?= $b_class ?>-dark); text-transform:uppercase; font-size:10px;">
                                <?= $p['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="receipt.php?id=<?= $p['id'] ?>" class="btn-lms btn-sm" style="background: #f1f5f9;" title="Print Receipt"><i class="fas fa-print"></i></a>
                                <a href="details.php?student_id=<?= $p['student_id'] ?>" class="btn-lms btn-sm btn-outline" title="View Student History"><i class="fas fa-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white p-3">
        <div class="pagination-lms justify-content-center">
            <?php for ($i=1; $i<=$pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
