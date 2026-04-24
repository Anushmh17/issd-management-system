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

<?php
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$user = currentUser();
$adminName = explode(' ', $user['name'] ?? 'Admin')[0];
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

</div><!-- desktop end -->

<!-- ============================================================
     MOBILE-ONLY DASHBOARD  (hidden on desktop via CSS)
     ============================================================ -->
<div id="dash-mobile">

  <!-- Pending alert (if any) -->
  <?php if ($pendingLecturerPay && $pendingLecturerPay['count'] > 0): ?>
  <div class="dm-alert">
    <i class="fas fa-triangle-exclamation"></i>
    <span><strong><?= $pendingLecturerPay['count'] ?></strong> pending payout(s) · Rs.<?= number_format($pendingLecturerPay['total'],0) ?></span>
    <a href="<?= BASE_URL ?>/admin/lecturer_payments/index.php?status=pending" class="dm-alert-btn">Review</a>
  </div>
  <?php endif; ?>

  <!-- Hero Greeting -->
  <div class="dm-hero">
    <div class="dm-hero-orb dm-orb1"></div>
    <div class="dm-hero-orb dm-orb2"></div>
    <div class="dm-hero-top">
      <div>
        <div class="dm-greeting"><?= $greeting ?>, <?= htmlspecialchars($adminName) ?> 👋</div>
        <div class="dm-hero-sub">Here's your institute overview</div>
      </div>
      <div class="dm-hero-date"><?= date('D, d M') ?></div>
    </div>
    <!-- Revenue spotlight -->
    <div class="dm-revenue-spot">
      <div class="dm-rev-label"><i class="fas fa-coins"></i> Total Revenue</div>
      <div class="dm-rev-value">Rs.<?= number_format($stats['revenue'], 0) ?></div>
      <div class="dm-rev-meta"><?= $stats['enrollments'] ?> enrollments &nbsp;·&nbsp; <?= $stats['notices'] ?> notices</div>
    </div>
  </div>

  <!-- Quick Action Buttons -->
  <div class="dm-actions">
    <a href="<?= BASE_URL ?>/frontend/admin/students.php?action=add" class="dm-action-btn dm-action-primary">
      <i class="fas fa-user-plus"></i>
      <span>Add Student</span>
    </a>
    <a href="<?= BASE_URL ?>/frontend/admin/notices.php?action=add" class="dm-action-btn dm-action-outline">
      <i class="fas fa-bell"></i>
      <span>Post Notice</span>
    </a>
    <a href="<?= BASE_URL ?>/frontend/admin/reports.php" class="dm-action-btn dm-action-outline">
      <i class="fas fa-chart-bar"></i>
      <span>Reports</span>
    </a>
  </div>

  <!-- Stats Grid 2×3 -->
  <div class="dm-stats-grid">
    <div class="dm-stat" style="--sc:#5B4EFA;--sl:#ede9ff;">
      <div class="dm-stat-icon"><i class="fas fa-user-graduate"></i></div>
      <div class="dm-stat-val"><?= $stats['students'] ?></div>
      <div class="dm-stat-lbl">Students</div>
    </div>
    <div class="dm-stat" style="--sc:#FF9F43;--sl:#fff3e3;">
      <div class="dm-stat-icon"><i class="fas fa-chalkboard-user"></i></div>
      <div class="dm-stat-val"><?= $stats['lecturers'] ?></div>
      <div class="dm-stat-lbl">Lecturers</div>
    </div>
    <div class="dm-stat" style="--sc:#00C9A7;--sl:#e0faf4;">
      <div class="dm-stat-icon"><i class="fas fa-book-open"></i></div>
      <div class="dm-stat-val"><?= $stats['courses'] ?></div>
      <div class="dm-stat-lbl">Courses</div>
    </div>
    <div class="dm-stat" style="--sc:#4CC9F0;--sl:#e6f6ff;">
      <div class="dm-stat-icon"><i class="fas fa-list-check"></i></div>
      <div class="dm-stat-val"><?= $stats['enrollments'] ?></div>
      <div class="dm-stat-lbl">Enrolled</div>
    </div>
    <div class="dm-stat" style="--sc:#FF6B6B;--sl:#ffe8e8;">
      <div class="dm-stat-icon"><i class="fas fa-bell"></i></div>
      <div class="dm-stat-val"><?= $stats['notices'] ?></div>
      <div class="dm-stat-lbl">Notices</div>
    </div>
    <div class="dm-stat dm-stat-link" style="--sc:#5B4EFA;--sl:#ede9ff;" onclick="location.href='<?= BASE_URL ?>/frontend/admin/reports.php'">
      <div class="dm-stat-icon"><i class="fas fa-arrow-right"></i></div>
      <div class="dm-stat-val" style="font-size:11px;font-weight:600;color:var(--sc);">Full</div>
      <div class="dm-stat-lbl">Analytics</div>
    </div>
  </div>

  <!-- Recent Enrollments -->
  <div class="dm-section">
    <div class="dm-section-header">
      <div class="dm-section-title"><i class="fas fa-list-check"></i> Recent Enrollments</div>
      <a href="<?= BASE_URL ?>/frontend/admin/enrollments.php" class="dm-view-all">View all <i class="fas fa-chevron-right"></i></a>
    </div>
    <?php if (empty($recentEnrollments)): ?>
      <div class="dm-empty"><i class="fas fa-list-check"></i><p>No enrollments yet.</p></div>
    <?php else: ?>
    <div class="dm-list">
      <?php foreach (array_slice($recentEnrollments, 0, 4) as $e):
        $sc = $e['status']==='active' ? '#00C9A7' : ($e['status']==='completed' ? '#4CC9F0' : '#FF6B6B');
        $sl = $e['status']==='active' ? '#e0faf4' : ($e['status']==='completed' ? '#e6f6ff' : '#ffe8e8');
      ?>
      <div class="dm-list-item">
        <div class="dm-li-avatar"><?= strtoupper(substr($e['student'],0,1)) ?></div>
        <div class="dm-li-body">
          <div class="dm-li-name"><?= htmlspecialchars($e['student']) ?></div>
          <div class="dm-li-sub"><?= htmlspecialchars($e['course']) ?></div>
        </div>
        <div class="dm-li-right">
          <div class="dm-li-badge" style="background:<?= $sl ?>;color:<?= $sc ?>;"><?= ucfirst($e['status']) ?></div>
          <div class="dm-li-date"><?= date('d M', strtotime($e['enrolled_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Payments -->
  <div class="dm-section">
    <div class="dm-section-header">
      <div class="dm-section-title"><i class="fas fa-money-bill-wave"></i> Recent Payments</div>
      <a href="<?= BASE_URL ?>/admin/payments/index.php" class="dm-view-all">View all <i class="fas fa-chevron-right"></i></a>
    </div>
    <?php if (empty($recentPayments)): ?>
      <div class="dm-empty"><i class="fas fa-wallet"></i><p>No payments yet.</p></div>
    <?php else: ?>
    <div class="dm-list">
      <?php foreach (array_slice($recentPayments, 0, 4) as $p):
        $pc = $p['status']==='paid' ? '#00C9A7' : ($p['status']==='pending' ? '#FF9F43' : '#FF6B6B');
        $pl = $p['status']==='paid' ? '#e0faf4' : ($p['status']==='pending' ? '#fff3e3' : '#ffe8e8');
      ?>
      <div class="dm-list-item">
        <div class="dm-li-avatar" style="background:linear-gradient(135deg,#00C9A7,#00a386);"><?= strtoupper(substr($p['student'],0,1)) ?></div>
        <div class="dm-li-body">
          <div class="dm-li-name"><?= htmlspecialchars($p['student']) ?></div>
          <div class="dm-li-sub"><?= htmlspecialchars($p['course']) ?></div>
        </div>
        <div class="dm-li-right">
          <div class="dm-li-amount">Rs.<?= number_format($p['amount'],0) ?></div>
          <div class="dm-li-badge" style="background:<?= $pl ?>;color:<?= $pc ?>;"><?= ucfirst($p['status']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Latest Notices -->
  <div class="dm-section" style="margin-bottom:0;">
    <div class="dm-section-header">
      <div class="dm-section-title"><i class="fas fa-bell"></i> Latest Notices</div>
      <a href="<?= BASE_URL ?>/frontend/admin/notices.php" class="dm-view-all">View all <i class="fas fa-chevron-right"></i></a>
    </div>
    <?php if (empty($notices)): ?>
      <div class="dm-empty"><i class="fas fa-bell-slash"></i><p>No notices yet.</p></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <?php foreach ($notices as $n):
        $nb = match($n['target_role']) { 'student'=>'#00C9A7', 'lecturer'=>'#FF9F43', 'admin'=>'#FF6B6B', default=>'#5B4EFA' };
        $nl = match($n['target_role']) { 'student'=>'#e0faf4', 'lecturer'=>'#fff3e3', 'admin'=>'#ffe8e8', default=>'#ede9ff' };
      ?>
      <div class="dm-notice-item">
        <div class="dm-notice-dot" style="background:<?= $nb ?>;box-shadow:0 0 0 3px <?= $nl ?>"></div>
        <div class="dm-notice-body">
          <div class="dm-notice-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="dm-notice-meta"><?= htmlspecialchars($n['posted_by']) ?> · <?= date('d M Y', strtotime($n['created_at'])) ?></div>
        </div>
        <span class="dm-notice-tag" style="background:<?= $nl ?>;color:<?= $nb ?>;"><?= ucfirst($n['target_role']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /#dash-mobile -->

</div><!-- /#page-content -->

<style>
/* ============================================================
   ADMIN DASHBOARD — MOBILE-ONLY REDESIGN (≤768px)
   Desktop layout (#page-content > .page-header, .stats-grid,
   .row) is completely hidden on mobile.
   ============================================================ */

/* Hide mobile block on desktop */
#dash-mobile { display: none; }

@media (max-width: 768px) {
  /* Hide ALL desktop content inside page-content */
  #page-content > .page-header,
  #page-content > .alert-lms,
  #page-content > .stats-grid,
  #page-content > .row {
    display: none !important;
  }
  /* Show mobile block */
  #dash-mobile {
    display: block;
    padding: 0 20px 24px;
  }

  /* ── Pending Alert ── */
  .dm-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff8ec;
    border-left: 4px solid #FF9F43;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 14px;
    font-size: 12.5px;
    font-weight: 500;
    color: #8a5900;
  }
  .dm-alert i { color: #FF9F43; flex-shrink: 0; }
  .dm-alert span { flex: 1; }
  .dm-alert-btn {
    background: #FF9F43;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
    white-space: nowrap;
  }

  /* ── Hero Card ── */
  .dm-hero {
    position: relative;
    background: linear-gradient(135deg,#5B4EFA 0%,#3f35d4 55%,#00C9A7 100%);
    border-radius: 22px;
    padding: 22px 20px 20px;
    margin-bottom: 14px;
    overflow: hidden;
    box-shadow: 0 10px 32px rgba(91,78,250,0.32);
  }
  .dm-hero-orb {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
    pointer-events: none;
  }
  .dm-orb1 { width:160px;height:160px;top:-60px;right:-50px; }
  .dm-orb2 { width:90px;height:90px;bottom:-30px;left:-20px; }
  .dm-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px;
  }
  .dm-greeting {
    font-size: 17px;
    font-weight: 700;
    color: #fff;
    font-family: 'Poppins',sans-serif;
    line-height: 1.2;
  }
  .dm-hero-sub {
    font-size: 11px;
    color: rgba(255,255,255,0.65);
    margin-top: 4px;
    font-weight: 400;
  }
  .dm-hero-date {
    font-size: 11px;
    color: rgba(255,255,255,0.6);
    font-weight: 600;
    background: rgba(255,255,255,0.12);
    padding: 5px 10px;
    border-radius: 20px;
    white-space: nowrap;
  }
  .dm-revenue-spot {
    background: rgba(255,255,255,0.12);
    border-radius: 14px;
    padding: 14px 16px;
    backdrop-filter: blur(4px);
  }
  .dm-rev-label {
    font-size: 10px;
    color: rgba(255,255,255,0.7);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .dm-rev-value {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    font-family: 'Poppins',sans-serif;
    line-height: 1.1;
    letter-spacing: -0.5px;
  }
  .dm-rev-meta {
    font-size: 11px;
    color: rgba(255,255,255,0.55);
    margin-top: 5px;
    font-weight: 500;
  }

  /* ── Quick Actions ── */
  .dm-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
  }
  .dm-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;
    padding: 14px 8px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
    transition: transform 0.18s,box-shadow 0.18s;
    cursor: pointer;
    border: none;
  }
  .dm-action-btn:active { transform: scale(0.94); }
  .dm-action-btn i { font-size: 18px; }
  .dm-action-primary {
    background: linear-gradient(135deg,#5B4EFA,#4338e0);
    color: #fff;
    box-shadow: 0 6px 18px rgba(91,78,250,0.35);
  }
  .dm-action-outline {
    background: #fff;
    color: var(--primary);
    border: 1.5px solid var(--border-color);
    box-shadow: var(--shadow-sm);
  }

  /* ── Stats Grid 2×3 ── */
  .dm-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 14px;
  }
  .dm-stat {
    background: #fff;
    border-radius: 16px;
    padding: 14px 8px 12px;
    text-align: center;
    border: 1.5px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: transform 0.18s;
    cursor: default;
  }
  .dm-stat-link { cursor: pointer; }
  .dm-stat:active { transform: scale(0.93); }
  .dm-stat-icon {
    width: 38px; height: 38px;
    border-radius: 11px;
    background: var(--sl);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: var(--sc);
    margin-bottom: 8px;
  }
  .dm-stat-val {
    font-size: 20px;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1;
    font-family: 'Poppins',sans-serif;
  }
  .dm-stat-lbl {
    font-size: 9.5px;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-top: 4px;
  }

  /* ── Section Wrapper ── */
  .dm-section {
    background: #fff;
    border-radius: 18px;
    border: 1.5px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    margin-bottom: 14px;
    overflow: hidden;
  }
  .dm-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 16px 0;
    margin-bottom: 12px;
  }
  .dm-section-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-main);
    font-family: 'Poppins',sans-serif;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .dm-section-title i { color: var(--primary); font-size: 13px; }
  .dm-view-all {
    font-size: 11px;
    font-weight: 700;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    background: var(--primary-light);
  }

  /* ── List Items ── */
  .dm-list { padding: 0 0 8px; }
  .dm-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-top: 1px solid var(--border-color);
  }
  .dm-list-item:first-child { border-top: none; }
  .dm-li-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg,var(--primary),var(--accent));
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff;
    flex-shrink: 0;
  }
  .dm-li-body { flex: 1; min-width: 0; }
  .dm-li-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .dm-li-sub {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .dm-li-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
  }
  .dm-li-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    white-space: nowrap;
  }
  .dm-li-date {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 500;
  }
  .dm-li-amount {
    font-size: 13px;
    font-weight: 800;
    color: var(--accent);
    white-space: nowrap;
  }

  /* ── Notice Items ── */
  .dm-notice-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px 10px;
  }
  .dm-notice-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .dm-notice-body { flex: 1; min-width: 0; }
  .dm-notice-title {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .dm-notice-meta {
    font-size: 10px;
    color: var(--text-muted);
    margin-top: 2px;
  }
  .dm-notice-tag {
    font-size: 9.5px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
    text-transform: capitalize;
  }

  /* ── Empty State ── */
  .dm-empty {
    text-align: center;
    padding: 24px 20px;
    color: var(--text-muted);
  }
  .dm-empty i { font-size: 32px; opacity: 0.25; display: block; margin-bottom: 8px; }
  .dm-empty p { font-size: 12px; margin: 0; }

  /* ── Page padding override on mobile ── */
  #page-content { padding: 16px 20px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (window.innerWidth > 768) return;
  // Animate stat values counting up on mobile
  document.querySelectorAll('.dm-stat-val').forEach(function(el) {
    var target = parseInt(el.textContent.replace(/[^0-9]/g,''), 10);
    if (!target || isNaN(target)) return;
    var start = 0, dur = 700, step = 16;
    var inc = target / (dur / step);
    var timer = setInterval(function() {
      start += inc;
      if (start >= target) { el.textContent = target; clearInterval(timer); }
      else { el.textContent = Math.floor(start); }
    }, step);
  });
  // Animate list items sliding in
  var items = document.querySelectorAll('.dm-list-item, .dm-notice-item, .dm-stat');
  items.forEach(function(el, i) {
    el.style.opacity = '0';
    el.style.transform = 'translateY(14px)';
    el.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
    setTimeout(function() {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 80 + i * 40);
  });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
