<?php
// =====================================================
// ISSD Management - Admin: Financial Alerts & Analytics
// admin/payments/alerts.php
// =====================================================
define('PAGE_TITLE', 'Payment Alerts');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

$stats = getFinancialStats($pdo);

// Get urgent overdue student payments
$overdueStudents = $pdo->query("
    SELECT p.*, s.full_name, s.student_id as student_reg, c.course_name 
    FROM student_payments p
    JOIN students s ON p.student_id = s.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.status = 'overdue'
    ORDER BY p.next_due_date ASC
")->fetchAll();

// Get upcoming student payments (due in next 7 days)
$upcomingStudents = $pdo->query("
    SELECT p.*, s.full_name, s.student_id as student_reg, c.course_name 
    FROM student_payments p
    JOIN students s ON p.student_id = s.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.next_due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
      AND p.status != 'paid'
    ORDER BY p.next_due_date ASC
")->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Payment Tracking & Alerts</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Financial Control</span></div>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn-lms btn-outline px-4 shadow-sm" style="border-radius:50px;">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
  </div>

  <!-- Financial Summary -->
  <div class="row g-4 mb-30">
    <div class="col-md-4">
      <div class="bento-card" style="border-left: 5px solid var(--accent);">
        <div class="d-flex justify-content-between mb-10">
          <div class="stat-label">Monthly Income</div>
          <i class="fas fa-arrow-trend-up text-success"></i>
        </div>
        <div class="stat-value" style="font-size: 28px;">Rs. <?= number_format($stats['monthly_income'], 2) ?></div>
        <div class="text-muted" style="font-size: 11px;">Total collections for <?= date('F Y') ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bento-card" style="border-left: 5px solid var(--danger);">
        <div class="d-flex justify-content-between mb-10">
          <div class="stat-label">Total Outstanding</div>
          <i class="fas fa-hand-holding-dollar text-danger"></i>
        </div>
        <div class="stat-value" style="font-size: 28px;">Rs. <?= number_format($stats['total_outstanding'], 2) ?></div>
        <div class="text-muted" style="font-size: 11px;">Expected revenue from overdue/partial fees</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bento-card" style="border-left: 5px solid var(--info);">
        <div class="d-flex justify-content-between mb-10">
          <div class="stat-label">Lecturer Payouts</div>
          <i class="fas fa-users-gear text-info"></i>
        </div>
        <div class="stat-value" style="font-size: 28px;">Rs. <?= number_format($stats['monthly_expense'], 2) ?></div>
        <div class="text-muted" style="font-size: 11px;">Total payments to staff this month</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- OVERDUE ALERTS -->
    <div class="col-md-7">
      <div class="card-lms h-100">
        <div class="card-lms-header d-flex justify-content-between align-items-center">
            <div class="card-lms-title text-danger"><i class="fas fa-circle-exclamation"></i> Overdue Student Fees</div>
            <span class="badge bg-danger rounded-pill px-3"><?= count($overdueStudents) ?> High Priority</span>
        </div>
        <div class="card-lms-body p-0">
          <?php if (empty($overdueStudents)): ?>
            <div class="p-50 text-center text-muted">
                <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                <p>All student payments are currently up to date!</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table-lms mb-0">
                <thead>
                  <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Balance</th>
                    <th>Due Since</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($overdueStudents as $s): ?>
                  <tr>
                    <td>
                        <div class="fw-800" style="font-size:13px;"><?= htmlspecialchars($s['full_name']) ?></div>
                        <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($s['student_reg']) ?></div>
                    </td>
                    <td style="font-size:12px;"><?= htmlspecialchars($s['course_name']) ?></td>
                    <td class="fw-800 text-danger">Rs. <?= number_format($s['balance'], 2) ?></td>
                    <td style="font-size:11px; font-weight:700; color:var(--text-muted);">
                        <?= date('d M Y', strtotime($s['next_due_date'])) ?>
                    </td>
                    <td>
                        <a href="add.php?student_id=<?= $s['student_id'] ?>&course_id=<?= $s['course_id'] ?>" class="btn-lms btn-sm btn-primary">Receive</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- UPCOMING / RECENT ACTIONS -->
    <div class="col-md-5">
      <div class="card-lms">
        <div class="card-lms-header">
            <div class="card-lms-title text-warning"><i class="fas fa-clock"></i> Upcoming Dues (Next 7 Days)</div>
        </div>
        <div class="card-lms-body p-0">
          <div class="list-group list-group-flush">
            <?php foreach ($upcomingStudents as $s): ?>
            <div class="list-group-item p-3 border-bottom d-flex justify-content-between align-items-center" style="background:transparent;">
                <div>
                    <div class="fw-700" style="font-size:13px;"><?= htmlspecialchars($s['full_name']) ?></div>
                    <div class="text-muted" style="font-size:11px;">Due on <?= date('D, d M', strtotime($s['next_due_date'])) ?></div>
                </div>
                <div class="text-end">
                    <div class="fw-800 text-primary" style="font-size:13px;">Rs. <?= number_format($s['balance'], 2) ?></div>
                    <a href="add.php?student_id=<?= $s['student_id'] ?>&course_id=<?= $s['course_id'] ?>" class="text-decoration-none fw-800" style="font-size:10px; color:var(--accent);">Pre-pay &rsaquo;</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($upcomingStudents)): ?>
                <div class="p-40 text-center text-muted" style="font-size:13px;">No upcoming dues for the next week.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="bento-card mt-4" style="background: var(--primary); color: #fff;">
        <h4 class="fw-900 mb-10" style="font-size:16px;">Financial Tip</h4>
        <p style="font-size:12px; opacity:0.9; line-height:1.6;">
            Regularly auditing overdue payments helps maintain a healthy cash flow. Consider sending automated SMS reminders to students listed in the "High Priority" section.
        </p>
        <button class="btn btn-light btn-sm rounded-pill fw-800 mt-2 px-3">Learn More</button>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
