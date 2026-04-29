<?php
/**
 * LEARN Management - Admin Dashboard
 * High-Density Bento Box Redesign
 */
define('PAGE_TITLE', 'Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

require_once dirname(__DIR__, 2) . '/backend/document_controller.php';

// =====================================================
// LIVE SYSTEM DATA
// =====================================================
// 1. Total Students
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// 2. Active Students (Ongoing enrollments)
$active_students = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM student_courses WHERE status = 'ongoing'")->fetchColumn();

// 3. Payment Alerts (Overdue student payments + Pending lecturer payments)
$overdue_count = (int)$pdo->query("SELECT COUNT(*) FROM student_payments WHERE status = 'overdue'")->fetchColumn();
$lecturer_pending_count = (int)$pdo->query("SELECT COUNT(*) FROM lecturer_payments WHERE status = 'pending'")->fetchColumn();
$pending_payments = $overdue_count + $lecturer_pending_count;

// 4. Monthly Revenue (Current Month)
$current_month = date('Y-m');
$stmtRev = $pdo->prepare("SELECT SUM(amount_paid) FROM student_payments WHERE month = ?");
$stmtRev->execute([$current_month]);
$monthly_revenue = (float)$stmtRev->fetchColumn();

// 5. Global Recent Activity Feed (Combined)
// Fixing "Illegal mix of collations" using CONVERT USING utf8mb4
$stmtActivity = $pdo->query("
    (SELECT CONVERT('student' USING utf8mb4) as type, CONVERT(full_name USING utf8mb4) as title, CONVERT('registered' USING utf8mb4) as action, created_at, id as target_id FROM students)
    UNION ALL
    (SELECT CONVERT('payment' USING utf8mb4) as type, CONVERT(CONCAT('Rs. ', FORMAT(amount_paid, 0)) USING utf8mb4) as title, CONVERT('payment received' USING utf8mb4) as action, created_at, id as target_id FROM student_payments)
    UNION ALL
    (SELECT CONVERT('lead' USING utf8mb4) as type, CONVERT(name USING utf8mb4) as title, CONVERT('new lead added' USING utf8mb4) as action, created_at, id as target_id FROM leads)
    UNION ALL
    (SELECT CONVERT('lecturer' USING utf8mb4) as type, CONVERT(name USING utf8mb4) as title, CONVERT('joined team' USING utf8mb4) as action, created_at, id as target_id FROM lecturers)
    ORDER BY created_at DESC
    LIMIT 10
");
$global_activities = $stmtActivity->fetchAll();
// Take first 3 for the main bento card
$recent_activities = array_slice($global_activities, 0, 3);

// 7. Upcoming Schedule (Today's classes - fallback mock)
$upcoming_schedule = [
    ['time' => '09:00 AM', 'title' => 'Web Development 101', 'lecturer' => 'Dr. Smith', 'room' => 'Lab 1'],
    ['time' => '11:30 AM', 'title' => 'Data Science Seminar', 'lecturer' => 'Prof. Xavier', 'room' => 'Hall A'],
    ['time' => '02:00 PM', 'title' => 'UX Design Workshop', 'lecturer' => 'Jane Doe', 'room' => 'Online'],
];

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<style>
  :root {
    --bento-bg: rgba(255, 255, 255, 0.85);
    --bento-border: rgba(255, 255, 255, 0.5);
    --bento-radius: 28px;
    --accent-indigo: #6366f1;
    --accent-emerald: #10b981;
    --accent-amber: #f59e0b;
    --accent-rose: #f43f5e;
  }


  /* --- Dashboard Container --- */
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: minmax(100px, auto);
    gap: 24px;
    padding: 32px;
  }

  /* --- Bento Base Card --- */
  .bento-card {
    background: var(--bento-bg);
    backdrop-filter: blur(12px);
    border: 1px solid var(--bento-border);
    border-radius: var(--bento-radius);
    padding: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
  }
  .bento-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--accent-indigo);
  }

  /* --- Grid Spans --- */
  .span-3 { grid-column: span 3; }
  .span-4 { grid-column: span 4; }
  .span-6 { grid-column: span 6; }
  .span-8 { grid-column: span 8; }
  .span-12 { grid-column: span 12; }

  @media (max-width: 1200px) {
    .span-3, .span-4, .span-6, .span-8 { grid-column: span 6; }
  }
  @media (max-width: 768px) {
    .span-3, .span-4, .span-6, .span-8 { grid-column: span 12; }
  }

  /* --- Stat Components --- */
  .stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
  .stat-icon {
    width: 44px; height: 44px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
  }
  .stat-value { font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
  .stat-label { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-trend { font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 100px; }
  .trend-up { background: #d1fae5; color: #065f46; }

  /* --- Welcome Hero --- */
  .hero-card {
    background: linear-gradient(135deg, var(--accent-indigo) 0%, #4f46e5 100%);
    color: #fff;
    grid-column: span 8;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .hero-title { font-size: 36px; font-weight: 900; margin-bottom: 8px; letter-spacing: -1px; }
  .hero-sub { font-size: 16px; opacity: 0.9; font-weight: 500; }
  .hero-decoration {
    position: absolute; right: -50px; bottom: -50px; width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    filter: blur(20px); border-radius: 50%;
  }

  /* --- Quick Actions --- */
  .action-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; height: 100%; }
  .action-btn {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #1e293b;
    font-weight: 700;
    font-size: 12px;
    transition: 0.3s;
    text-align: center;
  }
  .action-btn:hover { background: var(--accent-indigo); color: #fff; transform: scale(1.05); }
  .action-btn i { font-size: 20px; }

  /* --- Schedule List --- */
  .bento-schedule { display: flex; flex-direction: column; gap: 16px; }
  .schedule-row {
    display: flex; gap: 16px; padding: 12px; border-radius: 16px;
    background: rgba(255,255,255,0.5); transition: 0.3s;
  }
  .schedule-row:hover { background: #fff; transform: translateX(4px); }
  .s-time { font-size: 12px; font-weight: 800; color: var(--accent-indigo); width: 65px; }
  .s-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; }
  .s-info p { font-size: 11px; margin: 2px 0 0; color: #64748b; }

  /* --- Table Custom --- */
  .modern-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
  .modern-table tr { background: rgba(255,255,255,0.3); transition: 0.3s; }
  .modern-table tr:hover { background: rgba(255,255,255,0.8); }
  .modern-table th { text-align: left; padding: 10px 16px; font-size: 11px; color: #64748b; font-weight: 800; }
  .modern-table td { padding: 14px 16px; font-size: 13px; vertical-align: middle; }
  .modern-table td:first-child, .modern-table th:first-child { border-radius: 14px 0 0 14px; }
  .modern-table td:last-child, .modern-table th:last-child { border-radius: 0 14px 14px 0; }

  .card-header-bento {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
  }
  .card-header-bento h3 { font-size: 18px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px; }
</style>

<div class="dashboard-grid">

  <div class="bento-card hero-card">
    <div class="hero-decoration"></div>
    <div class="hero-title">Welcome Back, Admin</div>
    <div class="hero-sub">
      Institute operations are running smoothly today. 
      <a href="<?= BASE_URL ?>/admin/payments/index.php?status=overdue" class="text-white fw-700 text-decoration-underline">
        You have <?= $pending_payments ?> payment alerts
      </a> to review.
    </div>
  </div>

  <!-- QUICK ACTIONS -->
  <div class="bento-card span-4">
    <div class="action-grid">
      <a href="<?= BASE_URL ?>/admin/students/add.php" class="action-btn">
        <i class="fas fa-user-plus text-primary"></i> Add Student
      </a>
      <a href="<?= BASE_URL ?>/admin/payments/add.php" class="action-btn">
        <i class="fas fa-receipt text-success"></i> Payment
      </a>
      <a href="<?= BASE_URL ?>/admin/courses/add.php" class="action-btn">
        <i class="fas fa-book text-warning"></i> New Course
      </a>
      <a href="<?= BASE_URL ?>/admin/notices.php" class="action-btn">
        <i class="fas fa-bullhorn text-danger"></i> Post Notice
      </a>
    </div>
  </div>

  <!-- STATS -->
  <div class="bento-card span-3">
    <div class="stat-header">
      <div class="stat-icon" style="background:#e0e7ff; color:var(--accent-indigo);"><i class="fas fa-users"></i></div>
      <div class="stat-trend trend-up">+12.5%</div>
    </div>
    <div class="stat-value"><?= number_format($total_students) ?></div>
    <div class="stat-label">Total Students</div>
  </div>

  <div class="bento-card span-3">
    <div class="stat-header">
      <div class="stat-icon" style="background:#dcfce7; color:var(--accent-emerald);"><i class="fas fa-user-check"></i></div>
      <div class="stat-trend trend-up">+4.2%</div>
    </div>
    <div class="stat-value"><?= number_format($active_students) ?></div>
    <div class="stat-label">Active Learners</div>
  </div>

  <div class="bento-card span-3">
    <div class="stat-header">
      <div class="stat-icon" style="background:#fef3c7; color:var(--accent-amber);"><i class="fas fa-clock"></i></div>
      <div class="stat-trend text-danger" style="background:#fee2e2; padding:4px 8px; border-radius:100px;">Low</div>
    </div>
    <div class="stat-value"><?= $pending_payments ?></div>
    <div class="stat-label">Pending Payments</div>
  </div>

  <div class="bento-card span-3">
    <div class="stat-header">
      <div class="stat-icon" style="background:#fce7f3; color:var(--accent-rose);"><i class="fas fa-wallet"></i></div>
      <div class="stat-trend trend-up">+18%</div>
    </div>
    <div class="stat-value">Rs. <?= number_format($monthly_revenue/1000, 0) ?>k</div>
    <div class="stat-label">Monthly Revenue</div>
  </div>

<?php
// 6. Missing Documents Tracker (Real-time scan) - RESTORED
$stmtCheck = $pdo->query("SELECT id, full_name FROM students ORDER BY created_at DESC LIMIT 50");
$checkStudents = $stmtCheck->fetchAll();
$sIds = array_column($checkStudents, 'id');
$docStatuses = getBulkDocStatus($pdo, $sIds);
$missingStudents = [];
foreach ($checkStudents as $cs) {
    if ($docStatuses[$cs['id']] === 'missing') {
        $docRow = getOrCreateDocRecord($pdo, $cs['id']);
        $defs = getDocumentDefinitions();
        $mDocs = [];
        foreach ($defs as $k => $d) { if ($d['required'] && empty($docRow[$k.'_status'])) $mDocs[] = $d['label']; }
        $missingStudents[] = [
            'id'   => $cs['id'],
            'name' => $cs['full_name'],
            'docs' => implode(', ', array_slice($mDocs, 0, 2)) . (count($mDocs) > 2 ? '...' : '')
        ];
        if (count($missingStudents) >= 3) break;
    }
}
?>

  <!-- RECENT ACTIVITY -->
  <div class="bento-card span-8">
    <div class="card-header-bento">
      <h3><i class="fas fa-clock-rotate-left text-primary"></i> Recent Activity</h3>
      <button class="btn btn-sm btn-light rounded-pill px-3 fw-700" data-bs-toggle="modal" data-bs-target="#activityModal">View All</button>
    </div>
    <div class="table-responsive">
      <table class="modern-table">
        <thead>
          <tr class="text-muted small fw-800">
            <th>TYPE</th>
            <th>ACTIVITY</th>
            <th>DATE</th>
            <th>ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recent_activities as $a): 
            $icon = 'fa-circle-info';
            $color = '#64748b';
            $link = '#';
            if($a['type']==='student') { $icon = 'fa-user-graduate'; $color = '#6366f1'; $link = BASE_URL.'/admin/students/index.php?highlight_id='.$a['target_id']; }
            if($a['type']==='payment') { $icon = 'fa-receipt'; $color = '#10b981'; $link = BASE_URL.'/admin/payments/index.php?highlight_id='.$a['target_id']; }
            if($a['type']==='lead') { $icon = 'fa-bullseye'; $color = '#f43f5e'; $link = BASE_URL.'/admin/leads/index.php?highlight_id='.$a['target_id']; }
            if($a['type']==='lecturer') { $icon = 'fa-chalkboard-user'; $color = '#f59e0b'; $link = BASE_URL.'/admin/lecturers/index.php?highlight_id='.$a['target_id']; }
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="stat-icon" style="width:30px; height:30px; font-size:12px; background: <?= $color ?>20; color: <?= $color ?>;">
                  <i class="fas <?= $icon ?>"></i>
                </div>
                <span class="fw-800 text-uppercase small" style="color: <?= $color ?>;"><?= $a['type'] ?></span>
              </div>
            </td>
            <td>
              <div class="fw-700"><?= htmlspecialchars($a['title']) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($a['action']) ?></div>
            </td>
            <td class="text-muted small"><?= date('d M, h:i A', strtotime($a['created_at'])) ?></td>
            <td>
              <a href="<?= $link ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2" style="font-size:10px;">Open</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activity Modal -->
  <div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden; background: rgba(255,255,255,0.98); backdrop-filter: blur(15px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header border-0 p-3 pb-0">
          <h6 class="modal-title fw-800 d-flex align-items-center gap-2" style="font-size: 16px;">
            <i class="fas fa-bolt text-warning"></i> Global Activity Feed
          </h6>
          <button type="button" class="btn-close" style="font-size: 10px;" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3">
          <div class="d-flex flex-column gap-2">
            <?php foreach($global_activities as $a): 
               $icon = 'fa-circle-info';
               $color = '#64748b';
               $link = '#';
               if($a['type']==='student') { $icon = 'fa-user-graduate'; $color = '#6366f1'; $link = BASE_URL.'/admin/students/index.php?highlight_id='.$a['target_id']; }
               if($a['type']==='payment') { $icon = 'fa-receipt'; $color = '#10b981'; $link = BASE_URL.'/admin/payments/index.php?highlight_id='.$a['target_id']; }
               if($a['type']==='lead') { $icon = 'fa-bullseye'; $color = '#f43f5e'; $link = BASE_URL.'/admin/leads/index.php?highlight_id='.$a['target_id']; }
               if($a['type']==='lecturer') { $icon = 'fa-chalkboard-user'; $color = '#f59e0b'; $link = BASE_URL.'/admin/lecturers/index.php?highlight_id='.$a['target_id']; }
            ?>
            <a href="<?= $link ?>" class="activity-item text-decoration-none p-2 rounded-3 d-flex align-items-center justify-content-between transition-all" style="background: #f8fafc; border: 1px solid #f1f5f9;">
              <div class="d-flex align-items-center gap-2">
                <div class="stat-icon" style="width:32px; height:32px; font-size:11px; background: <?= $color ?>15; color: <?= $color ?>;">
                  <i class="fas <?= $icon ?>"></i>
                </div>
                <div>
                  <div class="fw-700 text-dark" style="font-size: 13px; line-height: 1.2;"><?= htmlspecialchars($a['title']) ?></div>
                  <div class="text-muted" style="font-size: 10.5px;"><?= htmlspecialchars($a['action']) ?></div>
                </div>
              </div>
              <div class="text-end" style="min-width: 70px;">
                <div class="text-dark fw-600" style="font-size: 10px;"><?= date('h:i A', strtotime($a['created_at'])) ?></div>
                <div class="text-muted" style="font-size: 9px;"><?= date('d M', strtotime($a['created_at'])) ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer border-0 p-3 pt-0">
          <button type="button" class="btn btn-light w-100 rounded-pill fw-700 py-2" style="font-size: 12px;" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .activity-item:hover {
      background: #fff !important;
      transform: scale(1.02);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border-color: var(--accent-indigo) !important;
    }
  </style>

  <!-- SECTION: MISSING DOCUMENTS TRACKER -->
  <div class="bento-card span-4">
    <div class="card-header-bento">
      <h3><i class="fas fa-file-circle-exclamation text-danger"></i> Missing Documents</h3>
      <a href="<?= BASE_URL ?>/admin/documents/index.php?doc_status=missing" class="btn btn-sm btn-light rounded-pill px-3 fw-700">View All</a>
    </div>
    <div class="d-flex flex-column gap-3">
       <?php if (empty($missingStudents)): ?>
         <div class="text-center py-4 text-muted small">No students with missing documents.</div>
       <?php else: ?>
         <?php foreach($missingStudents as $d): ?>
         <a href="<?= BASE_URL ?>/admin/documents/manage.php?student_id=<?= $d['id'] ?>" class="p-3 rounded-4 bg-danger-subtle border-0 text-decoration-none transition-all hover-scale d-block">
            <div class="fw-800 text-main small mb-1"><?= htmlspecialchars($d['name']) ?></div>
            <div class="text-danger fw-700" style="font-size:11px;"><i class="fas fa-times-circle me-1"></i> Missing: <?= htmlspecialchars($d['docs']) ?></div>
         </a>
         <?php endforeach; ?>
       <?php endif; ?>
    </div>
  </div>
  <!-- TODAY'S SCHEDULE -->
  <div class="bento-card span-4">
    <div class="card-header-bento">
      <h3><i class="fas fa-calendar-day text-success"></i> Today's Schedule</h3>
    </div>
    <div class="bento-schedule">
      <?php foreach($upcoming_schedule as $item): ?>
      <div class="schedule-row">
        <div class="s-time"><?= $item['time'] ?></div>
        <div class="s-info">
          <h4><?= $item['title'] ?></h4>
          <p><i class="fas fa-user-tie"></i> <?= $item['lecturer'] ?> | <?= $item['room'] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary w-100 mt-4 rounded-pill fw-800 py-2 shadow-sm">Calendar View</button>
  </div>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>