<?php
/**
 * ISSD Management - Admin Dashboard
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

// 7. Dynamic Today's Agenda (Payments + Follow-ups)
$today = date('Y-m-d');

$agenda = [];

// 7a. Lead Follow-ups
$stmtLeads = $pdo->prepare("SELECT id, name, phone, next_followup_datetime as time, notes FROM leads WHERE DATE(next_followup_datetime) = ? AND status != 'converted' LIMIT 3");
$stmtLeads->execute([$today]);
while($row = $stmtLeads->fetch()) {
    $agenda[] = [
        'type' => 'Lead Call',
        'icon' => 'fa-phone-volume',
        'color' => '#f43f5e',
        'time' => date('h:i A', strtotime($row['time'])),
        'title' => "Call " . $row['name'],
        'desc' => $row['phone'] . ($row['notes'] ? " &bull; " . $row['notes'] : ""),
        'link' => BASE_URL . "/admin/leads/index.php?highlight_id=" . $row['id']
    ];
}

// 7b. Student Follow-ups
$stmtStudents = $pdo->prepare("SELECT id, full_name, phone_number, next_follow_up as time, follow_up_note FROM students WHERE DATE(next_follow_up) = ? LIMIT 3");
$stmtStudents->execute([$today]);
while($row = $stmtStudents->fetch()) {
    $agenda[] = [
        'type' => 'Student Follow-up',
        'icon' => 'fa-headset',
        'color' => '#6366f1',
        'time' => 'Today',
        'title' => "Follow up: " . $row['full_name'],
        'desc' => $row['phone_number'] . ($row['follow_up_note'] ? " &bull; " . $row['follow_up_note'] : ""),
        'link' => BASE_URL . "/admin/students/index.php?highlight_id=" . $row['id']
    ];
}

// 7c. Pending/Overdue Payments
$stmtPayments = $pdo->prepare("
    SELECT sp.id, s.full_name, sp.total_due, sp.next_due_date, c.course_name 
    FROM student_payments sp 
    JOIN students s ON sp.student_id = s.id 
    JOIN courses c ON sp.course_id = c.id
    WHERE sp.status = 'overdue' OR DATE(sp.next_due_date) = ? 
    LIMIT 3
");
$stmtPayments->execute([$today]);
while($row = $stmtPayments->fetch()) {
    $agenda[] = [
        'type' => 'Payment Due',
        'icon' => 'fa-hand-holding-dollar',
        'color' => '#10b981',
        'time' => 'URGENT',
        'title' => "Collection: " . $row['full_name'],
        'desc' => $row['course_name'] . " &bull; Rs. " . number_format($row['total_due'], 0),
        'link' => BASE_URL . "/admin/finance/index.php?highlight_id=" . $row['id']
    ];
}

// Sort by time (Lead calls first by time, others after)
usort($agenda, function($a, $b) {
    return strcmp($a['time'], $b['time']);
});

// Fallback if empty
if (empty($agenda)) {
    $agenda[] = [
        'type' => 'Notice',
        'icon' => 'fa-calendar-check',
        'color' => '#64748b',
        'time' => '--:--',
        'title' => 'No Urgent Tasks',
        'desc' => 'Enjoy your day! All calls and payments are up to date.',
        'link' => '#'
    ];
}

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
    padding: 10px 32px 32px 32px;
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
    background: linear-gradient(135deg, #1e4d4d 0%, #345b5b 50%, #34d399 100%);
    color: #fff;
    grid-column: span 8;
    display: flex;
    position: relative;
    overflow: hidden;
    padding: 40px !important;
    box-shadow: 0 20px 50px rgba(79, 70, 229, 0.3) !important;
  }
  .hero-content {
    position: relative;
    z-index: 5;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    max-width: 550px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 15px 30px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
  }
  .hero-title { 
    font-size: 36px; /* Slightly smaller */
    font-weight: 900; 
    margin-bottom: 8px; 
    letter-spacing: -1.2px; 
    line-height: 1.1; 
    color: #fff;
    text-shadow: 2px 2px 0px rgba(0,0,0,0.1), 
                 4px 4px 10px rgba(0,0,0,0.2),
                 0 0 30px rgba(255,255,255,0.2);
    transform: perspective(1000px) rotateX(2deg);
  }
  .hero-sub { 
    font-size: 16px; 
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500; 
    line-height: 1.6; 
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }
  .hero-illustration {
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 450px; /* Slightly larger to fill space */
    height: auto;
    z-index: 1;
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));
    animation: floatIllustration 6s ease-in-out infinite;
    mask-image: linear-gradient(to left, black 80%, transparent 100%); /* Smooth fade into text area */
    pointer-events: none; /* Prevent illustration from blocking clicks */
  }
  @keyframes floatIllustration {
    0%, 100% { transform: translateY(-50%) translateX(0); }
    50% { transform: translateY(-55%) translateX(-10px); }
  }
  .hero-clock {
    position: absolute;
    top: 25px;
    right: 30px;
    font-size: 13px;
    font-weight: 800;
    background: rgba(255,255,255,0.8); /* Brighter for black text */
    padding: 6px 15px;
    border-radius: 100px;
    backdrop-filter: blur(5px);
    letter-spacing: 1px;
    z-index: 10; /* Ensure it is above the background */
    color: #000; /* Pure Black */
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }
  .hero-tag {
    display: inline-block;
    padding: 5px 12px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    color: #fbbf24; /* Vibrant Gold Accent */
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
  .action-btn:hover { background: var(--primary); color: #fff; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(30, 77, 77, 0.1); }
  .action-btn:hover i { color: #fff !important; }
  .action-btn i { font-size: 20px; transition: 0.3s; }

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


<div id="page-content">
<div class="dashboard-grid">

  <?php 
    $hour = date('H');
    $greeting = "Good Evening";
    if ($hour < 12) $greeting = "Good Morning";
    elseif ($hour < 17) $greeting = "Good Afternoon";
  ?>
  <div class="bento-card hero-card">
    <div class="hero-clock" id="live-clock"><?= date('H:i:s') ?></div>
    <img src="<?= BASE_URL ?>/assets/images/dashboard/welcome.png" class="hero-illustration" alt="Welcome">
    
    <div class="hero-content">
      <div class="hero-tag">System Administrator</div>
      <div class="hero-title"><?= $greeting ?>, Admin</div>
      <div class="hero-sub">
        Institute operations are running smoothly today. <br>
        <a href="<?= BASE_URL ?>/admin/finance/index.php" class="fw-800 text-decoration-none" style="background: rgba(255,255,255,0.15); padding: 4px 12px; border-radius: 10px; color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); display: inline-block; margin-top: 8px;">
          <i class="fas fa-bell me-1"></i> You have <span style="font-size: 18px;"><?= $pending_payments ?></span> payment alerts
        </a> <span style="font-size: 13px; opacity: 0.8; margin-left: 5px;">to review.</span>
      </div>
      
      <div class="mt-4 d-flex gap-3">
        <a href="reports.php" class="btn btn-light rounded-pill px-4 fw-800 shadow-sm transition-all hover-scale" style="font-size: 13px; position: relative; z-index: 10;">
          <i class="fas fa-chart-line me-2"></i> View Analytics
        </a>
      </div>
    </div>
  </div>

  <script>
    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('live-clock');
        if (clock) {
            clock.innerText = now.toLocaleTimeString('en-US', { hour12: false });
        }
    }
    setInterval(updateClock, 1000);
  </script>

  <!-- QUICK ACTIONS -->
  <div class="bento-card span-4" style="overflow: visible; z-index: 100;">
    <div class="action-grid">
      <a href="<?= BASE_URL ?>/admin/students/add.php" class="action-btn">
        <i class="fas fa-user-plus" style="color: var(--info);"></i> Add Student
      </a>
      <div class="dropdown" style="height:100%;">
        <button class="action-btn w-100 h-100 dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:18px;">
            <i class="fas fa-receipt" style="color: var(--accent);"></i> Payment
        </button>
        <ul class="dropdown-menu p-2 animate__animated animate__fadeIn" style="border-radius:18px; width:240px; z-index: 9999; transform:translateY(10px) !important; box-shadow: 0 30px 90px -15px rgba(15, 23, 42, 0.45) !important; border: 1px solid var(--accent-rose) !important;">
            <li>
                <a class="dropdown-item p-3 rounded-4 d-flex align-items-center gap-3" href="<?= BASE_URL ?>/admin/payments/add.php">
                    <div class="stat-icon" style="width:32px; height:32px; background:#e0e7ff; color:var(--accent-indigo); font-size:12px;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <div class="fw-800" style="font-size:13px;">Student Payment</div>
                        <div class="text-muted" style="font-size:10px;">Receive collections</div>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider opacity-50"></li>
            <li>
                <a class="dropdown-item p-3 rounded-4 d-flex align-items-center gap-3" href="<?= BASE_URL ?>/admin/lecturer_payments/index.php">
                    <div class="stat-icon" style="width:32px; height:32px; background:#dcfce7; color:var(--accent-emerald); font-size:12px;">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <div class="fw-800" style="font-size:13px;">Lecturer Pay</div>
                        <div class="text-muted" style="font-size:10px;">Process payouts</div>
                    </div>
                </a>
            </li>
        </ul>
      </div>
      <a href="<?= BASE_URL ?>/admin/courses/add.php" class="action-btn">
        <i class="fas fa-book" style="color: var(--warning);"></i> New Course
      </a>
      <a href="<?= BASE_URL ?>/admin/notices.php" class="action-btn">
        <i class="fas fa-bullhorn" style="color: var(--danger);"></i> Post Notice
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

  <a href="<?= BASE_URL ?>/admin/finance/index.php" class="bento-card span-3 text-decoration-none">
    <div class="stat-header">
      <div class="stat-icon" style="background:#fef3c7; color:var(--accent-amber);"><i class="fas fa-clock"></i></div>
      <div class="stat-trend text-danger" style="background:#fee2e2; padding:4px 8px; border-radius:100px;">Low</div>
    </div>
    <div class="stat-value"><?= $pending_payments ?></div>
    <div class="stat-label">Pending Payments</div>
  </a>

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
  <!-- TODAY'S AGENDA -->
  <div class="bento-card span-4">
    <div class="card-header-bento">
      <h3><i class="fas fa-list-check text-success"></i> Today's Agenda</h3>
      <div class="badge bg-success-subtle text-success border-0 rounded-pill px-3" style="font-size:11px;">
        <?= count($agenda) ?> Tasks
      </div>
    </div>
    <div class="bento-schedule">
      <?php foreach($agenda as $item): ?>
      <a href="<?= $item['link'] ?>" class="schedule-row text-decoration-none d-block">
        <div class="d-flex gap-16">
            <div class="s-time d-flex flex-column align-items-center">
                <div style="font-size:11px;"><?= $item['time'] ?></div>
                <div class="stat-icon mt-2" style="width:32px; height:32px; font-size:14px; background: <?= $item['color'] ?>15; color: <?= $item['color'] ?>;">
                    <i class="fas <?= $item['icon'] ?>"></i>
                </div>
            </div>
            <div class="s-info flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-uppercase fw-800" style="font-size:9px; color: <?= $item['color'] ?>; letter-spacing:0.5px;"><?= $item['type'] ?></span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:10px;"></i>
                </div>
                <h4 class="mt-1"><?= htmlspecialchars($item['title']) ?></h4>
                <p><?= htmlspecialchars($item['desc']) ?></p>
                
                <div class="d-flex gap-2 mt-2">
                    <?php if(strpos($item['type'], 'Call') !== false): ?>
                        <span class="badge bg-primary rounded-pill" style="font-size:9px;"><i class="fas fa-phone"></i> Call Now</span>
                    <?php elseif(strpos($item['type'], 'Payment') !== false): ?>
                        <span class="badge bg-success rounded-pill" style="font-size:9px;"><i class="fas fa-receipt"></i> Process</span>
                    <?php endif; ?>
                    <span class="badge bg-light text-dark rounded-pill" style="font-size:9px;"><i class="fas fa-clock"></i> Snooze</span>
                </div>
            </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <a href="<?= BASE_URL ?>/admin/calendar.php" class="btn btn-primary w-100 mt-4 rounded-pill fw-800 py-2 shadow-sm">
        <i class="fas fa-calendar-alt me-2"></i> Open Full Calendar
    </a>
  </div>

</div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
