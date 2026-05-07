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

// --- Live Payroll Status ---
$currentMonth   = date('Y-m');
$totalLecturers = (int)$pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$stmtPaid       = $pdo->prepare("SELECT COUNT(DISTINCT lecturer_id) FROM lecturer_payments WHERE payment_month = ?");
$stmtPaid->execute([$currentMonth]);
$paidThisMonth  = (int)$stmtPaid->fetchColumn();


$payrollPct    = $totalLecturers > 0 ? round(($paidThisMonth / $totalLecturers) * 100) : 0;
$unpaidCount   = $totalLecturers - $paidThisMonth;

if ($totalLecturers === 0) {
    $payrollStatus = 'No Staff';
    $payrollColor  = '#64748b';
    $payrollBg     = 'linear-gradient(135deg, #64748b, #475569)';
    $payrollIcon   = 'fa-user-slash';
} elseif ($paidThisMonth === $totalLecturers) {
    $payrollStatus = 'All Paid ✓';
    $payrollColor  = '#10b981';
    $payrollBg     = 'linear-gradient(135deg, #10b981, #059669)';
    $payrollIcon   = 'fa-circle-check';
} elseif ($paidThisMonth > 0) {
    $payrollStatus = $paidThisMonth . ' / ' . $totalLecturers . ' Paid';
    $payrollColor  = '#f59e0b';
    $payrollBg     = 'linear-gradient(135deg, #f59e0b, #d97706)';
    $payrollIcon   = 'fa-clock';
} else {
    $payrollStatus = 'Unpaid — ' . $totalLecturers . ' Pending';
    $payrollColor  = '#ef4444';
    $payrollBg     = 'linear-gradient(135deg, #ef4444, #dc2626)';
    $payrollIcon   = 'fa-triangle-exclamation';
}

// --- Unpaid Lecturers this month ---
$stmtUnpaid = $pdo->prepare("
    SELECT l.id, l.name, l.department
    FROM lecturers l
    WHERE l.id NOT IN (
        SELECT DISTINCT lecturer_id FROM lecturer_payments WHERE payment_month = ?
    )
    ORDER BY l.name ASC
");
$stmtUnpaid->execute([$currentMonth]);
$unpaidLecturers = $stmtUnpaid->fetchAll();

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

    <!-- Card 1: Total Staff Payouts -->
    <div class="col-md-4">
      <div class="bento-card" style="border-left: 4px solid #6366f1; position:relative; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
          <div style="font-size:10px; font-weight:800; color:#94a3b8; letter-spacing:1px; text-transform:uppercase;">Total Staff Payouts</div>
          <div style="width:36px; height:36px; background:#eef2ff; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:16px; color:#6366f1;">
            <i class="fas fa-money-check-dollar"></i>
          </div>
        </div>
        <div style="font-size:26px; font-weight:800; color:#1e293b; margin-bottom:4px;">Rs. <?= number_format($stats['monthly_expense'], 2) ?></div>
        <div style="font-size:11px; color:#94a3b8;">Expenses recorded for <?= date('F Y') ?></div>
      </div>
    </div>

    <!-- Card 2: Active Lecturers -->
    <div class="col-md-4">
      <div class="bento-card" style="border-left: 4px solid #10b981; position:relative; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
          <div style="font-size:10px; font-weight:800; color:#94a3b8; letter-spacing:1px; text-transform:uppercase;">Active Lecturers</div>
          <div style="width:36px; height:36px; background:#d1fae5; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:16px; color:#10b981;">
            <i class="fas fa-chalkboard-user"></i>
          </div>
        </div>
        <div style="font-size:26px; font-weight:800; color:#1e293b; margin-bottom:4px;"><?= $totalLecturers ?></div>
        <div style="font-size:11px; color:#94a3b8;">Registered academic staff</div>
      </div>
    </div>

    <!-- Card 3: Payroll Status (dynamic + clickable) -->
    <div class="col-md-4">
      <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#payrollStatusModal"
         class="bento-card text-decoration-none d-block" 
         style="border-left: 4px solid <?= $payrollColor ?>; position:relative; overflow:hidden; cursor:pointer;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
          <div style="font-size:10px; font-weight:800; color:#94a3b8; letter-spacing:1px; text-transform:uppercase;">Payroll Status</div>
          <div style="width:36px; height:36px; background:<?= $payrollColor ?>18; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:16px; color:<?= $payrollColor ?>;">
            <i class="fas <?= $payrollIcon ?>"></i>
          </div>
        </div>
        <div style="font-size:22px; font-weight:800; color:<?= $payrollColor ?>; margin-bottom:4px;"><?= $payrollStatus ?></div>
        <div style="font-size:11px; color:#94a3b8; margin-bottom:10px;">Cycle: <?= date('F Y') ?> &nbsp;·&nbsp; <span style="color:<?= $payrollColor ?>; font-weight:700;">View details →</span></div>
        <?php if ($totalLecturers > 0): ?>
        <div style="background:#f1f5f9; border-radius:100px; height:6px; overflow:hidden;">
          <div style="width:<?= $payrollPct ?>%; height:100%; background:<?= $payrollColor ?>; border-radius:100px;"></div>
        </div>
        <div style="font-size:9px; color:#94a3b8; margin-top:5px; text-align:right;"><?= $payrollPct ?>% complete</div>
        <?php endif; ?>
      </a>
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
                        <td><span class="badge-lms" style="background:var(--primary-light); color:var(--primary); font-size:11px;"><?= $p['payment_month'] ?></span></td>
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

<!-- Payroll Status Modal -->
<div class="modal fade" id="payrollStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content border-0" style="border-radius: 22px; overflow:hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.18);">

      <!-- Modal Header -->
      <div class="modal-header border-0 px-4 pt-4 pb-2">
        <div>
          <div style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Cycle: <?= date('F Y') ?></div>
          <h5 class="modal-title fw-800" style="font-size:18px; color:#1e293b;">
            <i class="fas <?= $payrollIcon ?> me-2" style="color:<?= $payrollColor ?>;"></i>
            Payroll Breakdown
          </h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Progress bar -->
      <?php if ($totalLecturers > 0): ?>
      <div class="px-4 pb-3">
        <div style="display:flex; justify-content:space-between; font-size:11px; font-weight:700; margin-bottom:6px;">
          <span style="color:#64748b;"><?= $paidThisMonth ?> of <?= $totalLecturers ?> paid</span>
          <span style="color:<?= $payrollColor ?>; font-weight:800;"><?= $payrollPct ?>%</span>
        </div>
        <div style="background:#f1f5f9; border-radius:100px; height:8px; overflow:hidden;">
          <div style="width:<?= $payrollPct ?>%; height:100%; background:<?= $payrollColor ?>; border-radius:100px; transition:width 1s ease;"></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Modal Body: Unpaid list -->
      <div class="modal-body px-4 pt-0 pb-2" style="max-height:360px; overflow-y:auto;">
        <?php if (empty($unpaidLecturers)): ?>
          <div class="text-center py-5">
            <div style="width:64px; height:64px; background:#d1fae5; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; color:#10b981; margin:0 auto 14px;"><i class="fas fa-circle-check"></i></div>
            <div style="font-size:16px; font-weight:800; color:#1e293b; margin-bottom:4px;">All Paid! 🎉</div>
            <div style="font-size:12px; color:#94a3b8;">Every lecturer has been paid for <?= date('F Y') ?>.</div>
          </div>
        <?php else: ?>
          <div style="font-size:11px; font-weight:800; color:#ef4444; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:12px;">
            <i class="fas fa-clock me-1"></i> <?= count($unpaidLecturers) ?> Unpaid This Month
          </div>
          <div style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($unpaidLecturers as $ul):
              $initials = strtoupper(substr($ul['name'], 0, 1));
            ?>
            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:#f8fafc; border-radius:14px; border:1.5px solid #f1f5f9;">
              <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; flex-shrink:0;"><?= $initials ?></div>
                <div>
                  <div style="font-size:14px; font-weight:800; color:#1e293b;"><?= htmlspecialchars($ul['name']) ?></div>
                  <div style="font-size:11px; color:#94a3b8;"><?= htmlspecialchars($ul['department'] ?? 'Lecturer') ?></div>
                </div>
              </div>
              <a href="add.php?lecturer_id=<?= $ul['id'] ?>" 
                 style="display:inline-flex; align-items:center; gap:6px; background:#6366f1; color:#fff; font-size:11px; font-weight:800; padding:7px 14px; border-radius:10px; text-decoration:none; transition:all 0.2s;"
                 onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                <i class="fas fa-wallet"></i> Pay Now
              </a>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex gap-2">
        <button type="button" class="btn btn-light rounded-pill fw-700 flex-grow-1" data-bs-dismiss="modal">Close</button>
        <a href="add.php" class="btn btn-primary rounded-pill fw-800 flex-grow-1"><i class="fas fa-plus me-1"></i> Record Payout</a>
      </div>

    </div>
  </div>
</div>

