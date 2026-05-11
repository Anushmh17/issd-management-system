<?php
// =====================================================
// ISSD Management - Student Dashboard (Premium Dark Mode)
// =====================================================
define('PAGE_TITLE', 'Student Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);
require_once dirname(__DIR__, 2) . '/backend/alert_system.php';

$userId = currentUserId();
$studentStmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$studentStmt->execute([$userId]);
$studentId = (int)$studentStmt->fetchColumn();

$paymentAlerts = $studentId ? getStudentPaymentAlerts($pdo, $studentId) : [];

// Use $studentId (students.id) for all student_courses / payment queries
$myCourses = $pdo->prepare("SELECT COUNT(*) FROM student_courses WHERE student_id=? AND status='ongoing'");
$myCourses->execute([$studentId]); $myCourses = $myCourses->fetchColumn();

$myAssignments = $pdo->prepare("SELECT COUNT(*) FROM assignments a JOIN student_courses sc ON sc.course_id=a.course_id WHERE sc.student_id=?");
$myAssignments->execute([$studentId]); $myAssignments = $myAssignments->fetchColumn();

$submitted = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE student_id=?");
$submitted->execute([$studentId]); $submitted = $submitted->fetchColumn();

$totalPaid = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM student_payments WHERE student_id=?");
$totalPaid->execute([$studentId]); $totalPaid = $totalPaid->fetchColumn();

$totalBalance = $pdo->prepare("SELECT COALESCE(SUM(balance),0) FROM student_payments WHERE student_id=? AND status != 'paid'");
$totalBalance->execute([$studentId]); $totalBalance = $totalBalance->fetchColumn();

$courses = $pdo->prepare("SELECT c.course_name as title, c.course_code as code, c.duration, c.monthly_fee as fee, sc.status, sc.start_date as enrolled_at FROM student_courses sc JOIN courses c ON c.id=sc.course_id WHERE sc.student_id=? ORDER BY sc.start_date DESC LIMIT 4");
$courses->execute([$studentId]); $courses = $courses->fetchAll();

$pendingAssignments = $pdo->prepare("SELECT a.id, a.title, a.due_date, a.max_marks, c.course_name AS course, (SELECT id FROM assignment_submissions WHERE assignment_id=a.id AND student_id=?) AS is_submitted FROM assignments a JOIN student_courses sc ON sc.course_id=a.course_id AND sc.student_id=? JOIN courses c ON c.id=a.course_id ORDER BY a.due_date ASC LIMIT 5");
$pendingAssignments->execute([$studentId, $studentId]); $pendingAssignments = $pendingAssignments->fetchAll();

$payments = $pdo->prepare("SELECT p.payment_date as paid_date, c.course_name AS course, p.amount_paid as amount, p.method, p.status FROM student_payments p JOIN courses c ON c.id=p.course_id WHERE p.student_id=? ORDER BY p.created_at DESC LIMIT 5");
$payments->execute([$studentId]); $payments = $payments->fetchAll();

$notices = $pdo->prepare("
  SELECT n.id, n.title, n.content, n.created_at, u.name as posted_by_name
  FROM notices n 
  JOIN users u ON n.posted_by = u.id 
  WHERE n.target_role IN ('all','student') 
    AND n.id NOT IN (SELECT notice_id FROM read_notices WHERE user_id = ?)
  ORDER BY n.created_at DESC LIMIT 1
");
$notices->execute([$userId]);
$notices = $notices->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>


<div id="page-content">
  <div class="dark-layout-wrapper">
    
    <div class="welcome-header">
      <h1>Welcome back, <span style="color:#22d3ee;"><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span> 👋</h1>
      <p>Your academic progress is on track. Keep it up!</p>
    </div>

    <!-- Top Stats -->
    <div class="dark-grid-4">
      
      <div class="glass-card">
        <div class="stat-icon" style="background:rgba(59, 130, 246, 0.1); color:#60a5fa;"><i class="fas fa-book-open"></i></div>
        <div class="stat-val"><?= $myCourses ?></div>
        <div class="stat-lbl">Active Courses</div>
      </div>
      
      <div class="glass-card">
        <div class="stat-icon" style="background:rgba(34, 197, 94, 0.1); color:#4ade80;"><i class="fas fa-check-circle"></i></div>
        <div class="stat-val"><?= $submitted ?></div>
        <div class="stat-lbl">Tasks Completed</div>
      </div>
      
      <div class="glass-card" style="grid-column: span 2;">
        <!-- Neon decorative blob -->
        <div style="position:absolute; right:-30px; top:-30px; width:120px; height:120px; border-radius:50%; background:radial-gradient(circle, rgba(34,211,238,0.2) 0%, rgba(192,132,252,0.1) 100%); filter:blur(20px); pointer-events:none;"></div>
        
        <div style="display:flex; justify-content:space-between; align-items:center; height:100%;">
          <div>
            <div class="stat-icon" style="background:rgba(239, 68, 68, 0.1); color:#f87171; margin-bottom:8px;"><i class="fas fa-wallet"></i></div>
            <div class="stat-lbl" style="margin-bottom:4px;">Outstanding Balance</div>
            <div class="stat-val">Rs. <?= number_format($totalBalance, 0) ?></div>
          </div>
          <div style="text-align:right; border-left:1px solid rgba(255,255,255,0.05); padding-left:24px;">
            <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; margin-bottom:4px;">Paid to date</div>
            <div style="font-size:18px; font-weight:800; color:#4ade80;">Rs. <?= number_format($totalPaid, 0) ?></div>
            <?php if($totalBalance > 0): ?>
              <a href="payments.php" style="display:inline-block; margin-top:12px; padding:6px 16px; background:#1e293b; color:#fff; border-radius:8px; font-size:12px; font-weight:600; border:1px solid #334155; text-decoration:none;">Make Payment</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>

    <!-- Main Content -->
    <div class="dark-grid-2">
      
      <!-- Left Column -->
      <div style="display:flex; flex-direction:column; gap:24px;">
        
        <!-- Courses -->
        <div class="glass-card">
          <div class="glass-card-title">
            <span style="display:flex;align-items:center;gap:8px;"><i class="fas fa-layer-group" style="color:#22d3ee;"></i> My Courses</span>
            <a href="courses.php" style="font-size:12px; color:#22d3ee; text-decoration:none;">View All</a>
          </div>
          <ul class="dark-list">
            <?php if(empty($courses)): ?>
              <li class="empty-box-lms">
                <i class="fas fa-inbox" style="font-size:24px; color:#475569; margin-bottom:12px;"></i>
                <div class="title" style="font-size:14px; font-weight:600;">No active courses.</div>
                <div style="font-size:12px; opacity:0.7; margin-top:4px;">You haven't been enrolled yet.</div>
              </li>
            <?php else: ?>
              <?php foreach($courses as $c): ?>
              <li class="dark-list-item">
                <div class="item-left">
                  <div class="item-icon" style="background:rgba(59,130,246,0.1); color:#60a5fa;"><i class="fas fa-graduation-cap"></i></div>
                  <div>
                    <div class="item-title"><?= htmlspecialchars($c['title']) ?></div>
                    <div class="item-sub"><?= htmlspecialchars($c['code']) ?> &bull; <?= htmlspecialchars($c['lecturer'] ?? 'TBA') ?></div>
                  </div>
                </div>
                <div class="item-right">
                  <span class="dark-badge <?= $c['status']==='active'?'db-green':($c['status']==='completed'?'db-blue':'db-red') ?>">
                    <?= ucfirst($c['status']) ?>
                  </span>
                </div>
              </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Recent Transactions & Announcements Tab Mock -->
        <div class="glass-card">
          <div class="glass-card-title" style="margin-bottom:16px;">
            <span>Recent Activity</span>
            <div class="segmented-control">
              <button class="segment-btn active">History</button>
              <button class="segment-btn">Announcements</button>
            </div>
          </div>
          <ul class="dark-list">
            <?php if(empty($payments)): ?>
              <li style="padding:20px; text-align:center; color:#64748b; font-size:13px;">No payment history found.</li>
            <?php else: ?>
              <?php foreach($payments as $p): ?>
              <li class="dark-list-item">
                <div class="item-left">
                  <div class="item-icon" style="background:rgba(34,197,94,0.1); color:#4ade80;"><i class="fas fa-arrow-down"></i></div>
                  <div>
                    <div class="item-title">Payment Received</div>
                    <div class="item-sub"><?= htmlspecialchars($p['course']) ?></div>
                  </div>
                </div>
                <div class="item-right">
                  <div class="item-val">Rs. <?= number_format($p['amount'], 0) ?></div>
                  <div class="item-sub" style="margin-top:2px;"><?= date('M d, Y', strtotime($p['paid_date'])) ?></div>
                </div>
              </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
          <div style="text-align:center; margin-top:20px;">
            <a href="payments.php" style="font-size:12px; color:#c084fc; font-weight:600; text-decoration:none;">View Full Ledger &rarr;</a>
          </div>
        </div>

      </div>

      <!-- Right Column -->
      <div style="display:flex; flex-direction:column; gap:24px;">
        
        <!-- Assignments -->
        <div class="glass-card">
          <div class="glass-card-title" style="margin-bottom:16px;">
            <span style="display:flex;align-items:center;gap:8px;"><i class="fas fa-tasks" style="color:#c084fc;"></i> Task Tracker</span>
          </div>
          
          <div class="segmented-control" style="margin-bottom:20px; width:100%; display:flex;">
            <button class="segment-btn active" style="flex:1;">Upcoming Tasks</button>
            <button class="segment-btn" style="flex:1;">All Tasks</button>
          </div>

          <ul class="dark-list">
            <?php if(empty($pendingAssignments)): ?>
              <li style="padding:20px; text-align:center; color:#64748b; font-size:13px;">No pending tasks.</li>
            <?php else: ?>
              <?php foreach($pendingAssignments as $a): 
                $due = strtotime($a['due_date']);
                $isNear = ($due - time()) < (86400 * 2);
              ?>
              <li class="dark-list-item" style="padding:12px 16px;">
                <div style="flex:1;">
                  <a href="submit.php?id=<?= $a['id'] ?>" style="color:#fff; text-decoration:none; font-weight:600; font-size:14px; display:block; margin-bottom:4px;"><?= htmlspecialchars($a['title']) ?></a>
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div class="item-sub"><?= htmlspecialchars($a['course']) ?></div>
                    <span class="dark-badge <?= $a['is_submitted'] ? 'db-green' : ($isNear ? 'db-red' : 'db-gray') ?>" style="font-size:9px;">
                      <?= $a['is_submitted'] ? 'Done' : 'Due ' . date('M d', $due) ?>
                    </span>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Activity Graph Mock -->
        <div class="glass-card">
          <div class="glass-card-title" style="font-size:13px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">Activity Graph</div>
          <div class="chart-mock">
            <div class="chart-bar" style="height:40%;"></div>
            <div class="chart-bar" style="height:70%;"></div>
            <div class="chart-bar" style="height:30%;"></div>
            <div class="chart-bar active" style="height:90%;"></div>
            <div class="chart-bar" style="height:50%;"></div>
            <div class="chart-bar" style="height:60%;"></div>
            <div class="chart-bar" style="height:20%;"></div>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:10px; font-weight:700; color:#475569; margin-top:8px; text-transform:uppercase; padding:0 4px;">
            <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
          </div>
        </div>

        <!-- Notices -->
        <div class="notice-highlight-card">
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:36px; height:36px; border-radius:8px; background:rgba(34,211,238,0.1); color:#22d3ee; display:flex; align-items:center; justify-content:center;"><i class="fas fa-bullhorn"></i></div>
            <h3 style="font-size:16px; font-weight:700; margin:0;">Latest Notice</h3>
            <?php if(!empty($notices)): ?>
              <span style="background:#ef4444; color:#fff; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; margin-left:auto;">NEW</span>
            <?php endif; ?>
          </div>
          
          <?php if(empty($notices)): ?>
            <p style="opacity:0.7; font-size:13px; margin:0;">No new announcements.</p>
          <?php else: ?>
            <div style="font-size:15px; font-weight:700; margin-bottom:8px; line-height:1.4;"><?= htmlspecialchars($notices[0]['title']) ?></div>
            <div style="font-size:13px; opacity:0.7; line-height:1.5; margin-bottom:16px; display:-webkit-box; -webkit-line-clamp:3; line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
              <?= htmlspecialchars($notices[0]['content']) ?>
            </div>
            <a href="notices.php" style="display:block; text-align:center; width:100%; padding:10px; background:rgba(255,255,255,0.05); color:inherit; font-size:13px; font-weight:600; border-radius:8px; text-decoration:none; transition:0.2s; border:1px solid rgba(255,255,255,0.1);">Read Full Announcement</a>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
  // for segmented controls
  document.querySelectorAll('.segmented-control').forEach(ctrl => {
    const btns = ctrl.querySelectorAll('.segment-btn');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  });
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
