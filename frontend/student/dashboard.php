<?php
// =====================================================
// LEARN Management - Student Dashboard
// =====================================================
define('PAGE_TITLE', 'Student Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);
require_once dirname(__DIR__, 2) . '/backend/alert_system.php';

$userId = currentUserId();

// Find the specific student_id since alerts use student_id from 'students' table, not 'users' table
$studentStmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$studentStmt->execute([$userId]);
$studentId = (int)$studentStmt->fetchColumn();

// Fetch Alerts
$paymentAlerts = [];
if ($studentId) {
    $paymentAlerts = getStudentPaymentAlerts($pdo, $studentId);
}

// Stats
$myCourses = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id=? AND status='active'");
$myCourses->execute([$userId]); $myCourses = $myCourses->fetchColumn();

$myAssignments = $pdo->prepare("
    SELECT COUNT(*) FROM assignments a
    JOIN enrollments e ON e.course_id=a.course_id
    WHERE e.student_id=?
");
$myAssignments->execute([$userId]); $myAssignments = $myAssignments->fetchColumn();

$submitted = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_id=?");
$submitted->execute([$userId]); $submitted = $submitted->fetchColumn();

$totalPaid = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM student_payments WHERE student_id=? AND status='paid'");
$totalPaid->execute([$studentId]); $totalPaid = $totalPaid->fetchColumn();

// My enrolled courses
$courses = $pdo->prepare("
    SELECT c.course_name as title, c.course_code as code, c.duration, c.monthly_fee as fee, e.status, e.enrolled_at,
           u.name AS lecturer
    FROM enrollments e
    JOIN courses c ON c.id=e.course_id
    LEFT JOIN users u ON u.id=e.lecturer_id
    WHERE e.student_id=?
    ORDER BY e.enrolled_at DESC
");
$courses->execute([$userId]);
$courses = $courses->fetchAll();

// Pending assignments
$pendingAssignments = $pdo->prepare("
    SELECT a.title, a.due_date, a.max_marks, c.course_name AS course,
           (SELECT id FROM assignment_submissions WHERE assignment_id=a.id AND student_id=?) AS is_submitted
    FROM assignments a
    JOIN enrollments e ON e.course_id=a.course_id AND e.student_id=?
    JOIN courses c ON c.id=a.course_id
    ORDER BY a.due_date ASC LIMIT 5
");
$pendingAssignments->execute([$userId, $userId]);
$pendingAssignments = $pendingAssignments->fetchAll();

// Payment history
$payments = $pdo->prepare("
    SELECT p.payment_date as paid_date, c.course_name AS course, p.amount_paid as amount, 'gateway' as method, p.status
    FROM student_payments p JOIN courses c ON c.id=p.course_id
    WHERE p.student_id=?
    ORDER BY p.created_at DESC LIMIT 5
");
$payments->execute([$studentId]);
$payments = $payments->fetchAll();

// Notices for student
$notices = $pdo->query("
    SELECT n.id, n.title, n.content, n.created_at, u.name as posted_by_name
    FROM notices n
    JOIN users u ON n.posted_by = u.id
    WHERE n.target_role IN ('all','student')
    ORDER BY n.created_at DESC LIMIT 4
")->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Student Dashboard</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Home &rsaquo; <span>Dashboard</span></div>
    </div>
    <a href="<?= BASE_URL ?>/frontend/student/courses.php" class="btn-primary-grad">
      <i class="fas fa-book-open"></i> My Courses
    </a>
  </div>

  <!-- Welcome Banner -->
  <div style="background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:var(--radius-lg);padding:28px 32px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
      <div style="font-size:20px;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;">
        Welcome back, <?= htmlspecialchars(currentUser()['name'] ?? 'Student') ?>! 👋
      </div>
      <div style="color:rgba(255,255,255,0.8);font-size:13px;margin-top:6px;">
        You are enrolled in <strong><?= $myCourses ?></strong> course<?= $myCourses!=1?'s':'' ?>. Keep learning!
      </div>
    </div>
    <div style="display:flex;gap:16px;">
      <div style="text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#fff;"><?= $submitted ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.7);">Submitted</div>
      </div>
      <div style="width:1px;background:rgba(255,255,255,0.2);"></div>
      <div style="text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#fff;"><?= $myAssignments ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.7);">Assignments</div>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  <?php if (!empty($paymentAlerts)): ?>
    <div style="margin-bottom:28px;">
      <?php foreach ($paymentAlerts as $alt): ?>
        <div class="alert-lms <?= $alt['type'] ?> mb-10" style="display:flex;align-items:center;">
          <i class="fas <?= $alt['icon'] ?> fw-700" style="font-size:16px;"></i>
          <div>
            <strong><?= htmlspecialchars($alt['title']) ?></strong>
            <div style="margin-top:2px;font-size:13px;"><?= $alt['message'] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fas fa-book-open"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $myCourses ?>"><?= $myCourses ?></div>
        <div class="stat-label">Enrolled Courses</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fas fa-file-alt"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $myAssignments ?>"><?= $myAssignments ?></div>
        <div class="stat-label">Assignments</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-check-square"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $submitted ?>"><?= $submitted ?></div>
        <div class="stat-label">Submitted</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-money-bill-wave"></i></div>
      <div>
        <div class="stat-value">Rs.<?= number_format($totalPaid,0) ?></div>
        <div class="stat-label">Total Paid</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- My Courses -->
    <div class="col-lg-7">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-book-open"></i> My Courses</div>
          <a href="<?= BASE_URL ?>/frontend/student/courses.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($courses)): ?>
            <div class="empty-state"><i class="fas fa-book"></i><p>You are not enrolled in any courses yet.</p></div>
          <?php else: ?>
          <table class="table-lms">
            <thead><tr><th>Course</th><th>Lecturer</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($courses as $c): ?>
              <tr>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($c['title']) ?></div>
                  <span class="badge-lms secondary" style="margin-top:3px;"><?= htmlspecialchars($c['code']) ?></span>
                </td>
                <td><?= htmlspecialchars($c['lecturer'] ?? 'TBA') ?></td>
                <td><?= htmlspecialchars($c['duration'] ?? '—') ?></td>
                <td><span class="badge-lms <?= $c['status']==='active'?'success':($c['status']==='completed'?'info':'danger') ?>"><?= ucfirst($c['status']) ?></span></td>
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

      <!-- Assignments -->
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-file-alt"></i> Assignments</div>
          <a href="<?= BASE_URL ?>/frontend/student/assignments.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($pendingAssignments)): ?>
            <div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No assignments.</p></div>
          <?php else: ?>
          <table class="table-lms">
            <thead><tr><th>Assignment</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($pendingAssignments as $a): ?>
              <tr>
                <td>
                  <div class="fw-600" style="font-size:12.5px;"><?= htmlspecialchars($a['title']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($a['course']) ?></small>
                </td>
                <td><?= $a['due_date'] ? date('M d',strtotime($a['due_date'])) : '—' ?></td>
                <td>
                  <?php if ($a['is_submitted']): ?>
                    <span class="badge-lms success">Submitted</span>
                  <?php else: ?>
                    <span class="badge-lms warning">Pending</span>
                  <?php endif; ?>
                </td>
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
          <div class="card-lms-title"><i class="fas fa-bell"></i> Notices</div>
        </div>
        <div class="card-lms-body">
          <?php if (empty($notices)): ?>
            <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notices.</p></div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($notices as $n): ?>
            <div class="d-flex justify-content-between align-items-center p-2 px-3 mb-2" style="background:var(--bg-page);border-radius:8px;border:1px solid var(--border-color);transition:all 0.2s;">
              <a href="<?= BASE_URL ?>/frontend/student/notices.php" style="flex-grow:1; text-decoration:none; color:inherit;">
                <div class="fw-600" style="font-size:13.3px;"><?= htmlspecialchars($n['title']) ?></div>
                <div class="text-muted" style="font-size:11px;"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
              </a>
              <div class="notice-card-clickable" 
                   data-real-id="<?= $n['id'] ?>"
                   data-title="<?= htmlspecialchars($n['title']) ?>"
                   data-content="<?= htmlspecialchars($n['content']) ?>"
                   data-author="<?= htmlspecialchars($n['posted_by_name']) ?>"
                   data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>"
                   style="width:28px; height:28px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:#fff; cursor:pointer;">
                <i class="fas fa-chevron-right text-primary" style="font-size:11px;"></i>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
