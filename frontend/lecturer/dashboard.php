<?php
// =====================================================
// LEARN Management - Lecturer Dashboard
// =====================================================
define('PAGE_TITLE', 'Lecturer Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);

$userId = currentUserId();

// Stats
$myCourses  = $pdo->prepare("SELECT COUNT(DISTINCT course_id) FROM enrollments WHERE lecturer_id=?");
$myCourses->execute([$userId]); $myCourses = $myCourses->fetchColumn();

$myStudents = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE lecturer_id=?");
$myStudents->execute([$userId]); $myStudents = $myStudents->fetchColumn();

$myAssignments = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE lecturer_id=?");
$myAssignments->execute([$userId]); $myAssignments = $myAssignments->fetchColumn();

$mySubmissions = $pdo->prepare("
    SELECT COUNT(*) FROM submissions s
    JOIN assignments a ON a.id=s.assignment_id
    WHERE a.lecturer_id=?
");
$mySubmissions->execute([$userId]); $mySubmissions = $mySubmissions->fetchColumn();

// My enrolled students
$students = $pdo->prepare("
    SELECT DISTINCT u.name, u.email, c.course_name AS course, e.status, e.enrolled_at
    FROM enrollments e
    JOIN users u ON u.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    WHERE e.lecturer_id = ?
    ORDER BY e.enrolled_at DESC LIMIT 8
");
$students->execute([$userId]);
$students = $students->fetchAll();

// My assignments
$assignments = $pdo->prepare("
    SELECT a.title, a.due_date, a.max_marks, c.course_name AS course,
           (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id=a.id) AS submissions
    FROM assignments a
    JOIN courses c ON c.id=a.course_id
    WHERE a.lecturer_id=?
    ORDER BY a.created_at DESC LIMIT 5
");
$assignments->execute([$userId]);
$assignments = $assignments->fetchAll();

// Notices for lecturer
$notices = $pdo->query("
    SELECT title, created_at FROM notices
    WHERE target_role IN ('all','lecturer')
    ORDER BY created_at DESC LIMIT 4
")->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Lecturer Dashboard</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Home &rsaquo; <span>Dashboard</span></div>
    </div>
    <a href="<?= BASE_URL ?>/frontend/lecturer/assignments.php?action=add" class="btn-primary-grad">
      <i class="fas fa-plus"></i> New Assignment
    </a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fas fa-book-open"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $myCourses ?>"><?= $myCourses ?></div>
        <div class="stat-label">My Courses</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-users"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $myStudents ?>"><?= $myStudents ?></div>
        <div class="stat-label">My Students</div>
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
        <div class="stat-value" data-count="<?= $mySubmissions ?>"><?= $mySubmissions ?></div>
        <div class="stat-label">Submissions</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- My Students -->
    <div class="col-lg-7">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-users"></i> My Students</div>
          <a href="<?= BASE_URL ?>/frontend/lecturer/students.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($students)): ?>
            <div class="empty-state"><i class="fas fa-users"></i><p>No students enrolled yet.</p></div>
          <?php else: ?>
          <table class="table-lms">
            <thead><tr><th>Student</th><th>Course</th><th>Status</th><th>Enrolled</th></tr></thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <tr>
                <td>
                  <div class="d-flex align-center gap-10">
                    <div class="avatar-initials"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                    <div>
                      <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                      <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($s['course']) ?></td>
                <td><span class="badge-lms <?= $s['status']==='active'?'success':($s['status']==='completed'?'info':'danger') ?>"><?= ucfirst($s['status']) ?></span></td>
                <td><?= date('M d, Y', strtotime($s['enrolled_at'])) ?></td>
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
          <div class="card-lms-title"><i class="fas fa-file-alt"></i> My Assignments</div>
          <a href="<?= BASE_URL ?>/frontend/lecturer/assignments.php" class="btn-lms btn-outline btn-sm">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($assignments)): ?>
            <div class="empty-state"><i class="fas fa-file-alt"></i><p>No assignments created yet.</p></div>
          <?php else: ?>
          <table class="table-lms">
            <thead><tr><th>Title</th><th>Due</th><th>Submissions</th></tr></thead>
            <tbody>
              <?php foreach ($assignments as $a): ?>
              <tr>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($a['title']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($a['course']) ?></small>
                </td>
                <td><?= $a['due_date'] ? date('M d',strtotime($a['due_date'])) : '—' ?></td>
                <td><span class="badge-lms info"><?= $a['submissions'] ?></span></td>
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
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--bg-page);border-radius:8px;border:1px solid var(--border-color);">
              <div>
                <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($n['title']) ?></div>
                <div class="text-muted" style="font-size:11px;"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
              </div>
              <i class="fas fa-chevron-right text-muted" style="font-size:11px;"></i>
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
