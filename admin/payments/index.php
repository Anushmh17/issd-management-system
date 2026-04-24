<?php
// =====================================================
// LEARN Management - Admin: Payments List
// admin/payments/index.php
// =====================================================
define('PAGE_TITLE', 'Payment History');
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

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Payment History</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Payments</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad">
      <i class="fas fa-plus"></i> Add Payment
    </a>
  </div>

  <div class="card-lms">
    <div class="card-lms-header students-filter-bar">
      <div class="card-lms-title">
        <i class="fas fa-money-bill-wave" style="color:#059669;"></i> All Payments
        <span class="badge-lms info" style="margin-left:6px;"><?= $total ?></span>
      </div>
      <form method="GET" class="students-filters">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Student name or course..."
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="status" class="form-control-lms filter-select" onchange="this.form.submit()">
          <option value="">Status: All</option>
          <option value="paid"    <?= $status==='paid'?'selected':'' ?>>Paid</option>
          <option value="partial" <?= $status==='partial'?'selected':'' ?>>Partial</option>
          <option value="overdue" <?= $status==='overdue'?'selected':'' ?>>Overdue</option>
        </select>
        <button type="submit" style="display:none;"></button>
        <?php if ($search || $status): ?>
          <a href="index.php" class="btn-lms btn-outline btn-sm">Clear</a>
        <?php endif; ?>
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
              <th>Receipt #</th>
              <th>Student</th>
              <th>Course</th>
              <th>Paid Amount</th>
              <th>Balance Due</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td style="font-family:monospace;font-size:12px;color:#64748b;">
                RCPT-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
              </td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($p['full_name']) ?></div>
                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($p['student_reg']) ?></div>
              </td>
              <td>
                <div class="fw-600" style="font-size:12px;"><?= htmlspecialchars($p['course_code']) ?></div>
                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($p['course_name']) ?></div>
              </td>
              <td>
                <div class="fw-700" style="color:#059669;">Rs. <?= number_format($p['amount_paid'], 2) ?></div>
              </td>
              <td>
                <div class="fw-600" style="color:#dc2626;">Rs. <?= number_format($p['balance'], 2) ?></div>
              </td>
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
              <td style="font-size:12px;color:#64748b;">
                <?= date('d M Y, h:i A', strtotime($p['payment_date'])) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div class="pagination-lms">
          <div class="pagination-info">
            Showing <?= (($page-1)*15)+1 ?>–<?= min($page*15,$total) ?> of <?= $total ?> payments
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
