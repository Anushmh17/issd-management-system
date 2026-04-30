<?php
// =====================================================
// ISSD Management - Admin: Lecturer Payments List
// admin/lecturer_payments/index.php
// =====================================================
define('PAGE_TITLE', 'Lecturer Payments');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/lecturer_payment_controller.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'mark_paid') {
    $payId = (int)($_POST['id'] ?? 0);
    if (markLecturerPaymentPaid($pdo, $payId)) {
        setFlash('success', 'Payment marked as paid.');
    } else {
        setFlash('danger', 'Failed to update payment status.');
    }
    header('Location: index.php'); exit;
}

$status = trim($_GET['status'] ?? '');
$month  = trim($_GET['month'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('status', 'month');
$result  = getLecturerPaymentsList($pdo, $filters, $page, 15);
$payments = $result['payments'];
$total = $result['total'];
$pages = $result['pages'];

// Generate last few months for filter dropdown
$months = [];
for ($i = 0; $i < 6; $i++) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[$m] = date('F Y', strtotime("-$i months"));
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Lecturer Payments</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Lecturer Payouts</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad">
      <i class="fas fa-plus"></i> Add Payment
    </a>
  </div>

  <div class="card-lms">
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Payroll Management</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>Lecturer Payouts</span>
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="d-flex gap-2" style="flex: 1;">
          <select name="month" class="form-control-lms filter-select"
                  style="min-width: 180px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="this.form.submit()">
            <option value="">Month: All</option>
            <?php foreach ($months as $val => $label): ?>
              <option value="<?= $val ?>" <?= $month===$val?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>

          <select name="status" class="form-control-lms filter-select"
                  style="min-width: 160px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="this.form.submit()">
            <option value="">Status: All</option>
            <option value="paid"    <?= $status==='paid'?'selected':'' ?>>Paid</option>
            <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
          </select>
        </div>

        <div class="filter-actions d-flex gap-2">
          <button type="submit" class="btn-lms btn-primary px-4 rounded-3 shadow-sm" style="height: 46px; padding: 0 25px;">
            <i class="fas fa-filter me-1"></i> Filter
          </button>
          <?php if ($status || $month): ?>
            <a href="index.php" class="btn-lms btn-outline px-3 rounded-3 d-flex align-items-center justify-content-center" style="height: 46px; width: 46px;" title="Clear Filters">
              <i class="fas fa-xmark"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($payments)): ?>
        <div class="empty-state">
          <i class="fas fa-receipt"></i>
          <p>No payment records found.</p>
        </div>
      <?php else: ?>
        <table class="table-lms">
          <thead>
            <tr>
              <th>ID</th>
              <th>Lecturer</th>
              <th>Amount</th>
              <th>Month</th>
              <th>Status</th>
              <th>Notes</th>
              <th style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td style="font-family:monospace;font-size:12px;color:#64748b;">
                #L-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?>
              </td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($p['lecturer_name']) ?></div>
              </td>
              <td>
                <div class="fw-700" style="color:#10b981;">Rs. <?= number_format($p['amount'], 2) ?></div>
              </td>
              <td>
                <?= date('M Y', strtotime($p['payment_month'] . '-01')) ?>
              </td>
              <td>
                <?php if ($p['status'] === 'paid'): ?>
                  <span class="badge-lms" style="background:#d1fae5;color:#059669;">Paid</span>
                  <div style="font-size:11px;color:#64748b;margin-top:2px;">
                    <?= date('d M, y h:ia', strtotime($p['payment_date'])) ?>
                  </div>
                <?php else: ?>
                  <span class="badge-lms" style="background:#fef3c7;color:#d97706;">Pending</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:#64748b;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($p['notes']) ?>
              </td>
              <td style="text-align:center;">
                <?php if ($p['status'] === 'pending'): ?>
                <form method="POST" style="display:inline-block;" action="index.php">
                  <input type="hidden" name="act" value="mark_paid">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-lms btn-success btn-sm" data-confirm="Mark this payment as fully paid?">
                    <i class="fas fa-check-double"></i> Mark Paid
                  </button>
                </form>
                <?php else: ?>
                  <i class="fas fa-check" style="color:#10b981;font-size:18px;"></i>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div class="pagination-lms">
          <div class="pagination-info">
            Showing <?= (($page-1)*15)+1 ?>""<?= min($page*15,$total) ?> of <?= $total ?> records
          </div>
          <div class="pagination-controls">
            <?php if ($page>1): ?>
              <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page-1])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($p_nav=max(1,$page-2); $p_nav<=min($pages,$page+2); $p_nav++): ?>
              <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$p_nav])) ?>" class="page-btn <?= $p_nav===$page?'active':'' ?>"><?= $p_nav ?></a>
            <?php endfor; ?>
            <?php if ($page<$pages): ?>
              <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page+1])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

