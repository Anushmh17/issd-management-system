<?php
// =====================================================
// LEARN Management - Admin Dashboard
// =====================================================
define('PAGE_TITLE', 'Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/alert_system.php';

requireRole(ROLE_ADMIN);

// --- Fetch stats ---
$stats = [];
$queries = [
    'students'   => "SELECT COUNT(*) FROM users WHERE role='student' AND status='active'",
    'lecturers'  => "SELECT COUNT(*) FROM users WHERE role='lecturer' AND status='active'",
    'courses'    => "SELECT COUNT(*) FROM courses WHERE status='active'",
    'enrollments'=> "SELECT COUNT(*) FROM enrollments",
    'revenue'    => "SELECT COALESCE(SUM(amount_paid),0) FROM student_payments",
    'notices'    => "SELECT COUNT(*) FROM notices",
];
foreach ($queries as $key => $sql) {
    $stats[$key] = $pdo->query($sql)->fetchColumn();
}

// --- Recent enrollments ---
$recentEnrollments = $pdo->query("
    SELECT e.enrolled_at, s.full_name AS student, c.course_name AS course, e.status
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    ORDER BY e.enrolled_at DESC LIMIT 6
")->fetchAll();

// --- Recent payments ---
$recentPayments = $pdo->query("
    SELECT p.payment_date as paid_date, s.full_name AS student, c.course_name AS course, p.amount_paid as amount, p.status
    FROM student_payments p
    JOIN students s ON s.id = p.student_id
    JOIN courses c ON c.id = p.course_id
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

// --- Latest notices ---
$notices = $pdo->query("
    SELECT n.title, n.target_role, n.created_at, u.name AS posted_by
    FROM notices n JOIN users u ON u.id = n.posted_by
    ORDER BY n.created_at DESC LIMIT 4
")->fetchAll();

// --- Pending Lecturer Payments ---
$pendingLecturerPay = getAdminLecturerAlerts($pdo);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Admin Dashboard</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Home &rsaquo; <span>Dashboard</span>
      </div>
    </div>
    <div class="d-flex gap-10">
      <a href="<?= BASE_URL ?>/frontend/admin/students.php?action=add" class="btn-primary-grad">
        <i class="fas fa-user-plus"></i> Add Student
      </a>
      <a href="<?= BASE_URL ?>/frontend/admin/notices.php?action=add" class="btn-lms btn-outline">
        <i class="fas fa-bell"></i> Post Notice
      </a>
    </div>
  </div>

  <?php if ($pendingLecturerPay && $pendingLecturerPay['count'] > 0): ?>
    <div class="alert-lms warning mb-20" style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <i class="fas fa-triangle-exclamation"></i>
        <strong>Pending Payouts:</strong> You have <?= $pendingLecturerPay['count'] ?> pending lecturer payment(s) totaling Rs. <?= number_format($pendingLecturerPay['total'], 2) ?>.
      </div>
      <a href="<?= BASE_URL ?>/admin/lecturer_payments/index.php?status=pending" class="btn-lms btn-sm btn-outline" style="background:#fff;color:#d97706;border-color:#d97706;">Review</a>
    </div>
  <?php endif; ?>

  <!-- Stats Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fas fa-user-graduate"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['students'] ?>"><?= $stats['students'] ?></div>
        <div class="stat-label">Total Students</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Active</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fas fa-chalkboard-user"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['lecturers'] ?>"><?= $stats['lecturers'] ?></div>
        <div class="stat-label">Total Lecturers</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Active</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-book-open"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['courses'] ?>"><?= $stats['courses'] ?></div>
        <div class="stat-label">Active Courses</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Running</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['enrollments'] ?>"><?= $stats['enrollments'] ?></div>
        <div class="stat-label">Enrollments</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Total</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
      <div>
        <div class="stat-value">Rs. <?= number_format($stats['revenue'], 0) ?></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Collected</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><i class="fas fa-bell"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['notices'] ?>"><?= $stats['notices'] ?></div>
        <div class="stat-label">Notices Posted</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Active</div>
      </div>
    </div>
  </div>

  <!-- Main Grid: Enrollments + Payments + Notices -->
  <div class="row g-4">

    <!-- Recent Enrollments -->
    <div class="col-lg-7">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-list-check"></i> Recent Enrollments</div>
          <a href="<?= BASE_URL ?>/frontend/admin/enrollments.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;overflow-x:auto;">
          <?php if (empty($recentEnrollments)): ?>
            <div class="empty-state"><i class="fas fa-list-check"></i><p>No enrollments yet.</p></div>
          <?php else: ?>
          <table class="table-lms searchable-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Course</th>
                <th class="td-nowrap">Date</th>
                <th class="td-nowrap">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentEnrollments as $e): ?>
              <tr>
                <td style="white-space:nowrap;">
                  <div class="d-flex align-center gap-10">
                    <div class="avatar-initials"><?= strtoupper(substr($e['student'],0,1)) ?></div>
                    <?= htmlspecialchars($e['student']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($e['course']) ?></td>
                <td class="td-nowrap"><?= date('M d, Y', strtotime($e['enrolled_at'])) ?></td>
                <td class="td-nowrap">
                  <span class="badge-lms <?= $e['status']==='active'?'success':($e['status']==='completed'?'info':'danger') ?>">
                    <?= ucfirst($e['status']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-5 d-flex flex-column gap-4">

      <!-- Recent Payments -->
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-money-bill-wave"></i> Recent Payments</div>
          <a href="<?= BASE_URL ?>/admin/payments/index.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($recentPayments)): ?>
            <div class="empty-state"><i class="fas fa-wallet"></i><p>No payments recorded.</p></div>
          <?php else: ?>
          <table class="table-lms">
            <thead><tr><th>Student</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recentPayments as $p): ?>
              <tr>
                <td>
                  <div><?= htmlspecialchars($p['student']) ?></div>
                  <small class="text-muted"><?= date('M d', strtotime($p['paid_date'])) ?></small>
                </td>
                <td class="fw-700" style="color:var(--accent)">Rs.<?= number_format($p['amount'],0) ?></td>
                <td><span class="badge-lms <?=$p['status']==='paid'?'success':($p['status']==='pending'?'warning':'danger')?>"><?=ucfirst($p['status'])?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Notices -->
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-bell"></i> Latest Notices</div>
          <a href="<?= BASE_URL ?>/frontend/admin/notices.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body">
          <?php if (empty($notices)): ?>
            <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notices posted yet.</p></div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($notices as $n): ?>
              <div style="padding:12px;background:var(--bg-page);border-radius:10px;border:1px solid var(--border-color);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                  <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($n['title']) ?></div>
                  <span class="badge-lms <?= $n['target_role']==='all'?'primary':($n['target_role']==='student'?'success':($n['target_role']==='lecturer'?'warning':'info')) ?>"><?= ucfirst($n['target_role']) ?></span>
                </div>
                <div class="text-muted" style="font-size:11px;margin-top:4px;">
                  By <?= htmlspecialchars($n['posted_by']) ?> &bull; <?= date('M d, Y', strtotime($n['created_at'])) ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div><!-- /row -->

</div><!-- /#page-content -->

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
