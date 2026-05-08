<?php
// =====================================================
// ISSD Management - Lecturer Dashboard
// =====================================================
define('PAGE_TITLE', 'Lecturer Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);

$userId = currentUserId();
// Distinguish between users.id and lecturers.id (session stores L + id for lecturers)
$lecturerId = (isset($_SESSION['lecturer_id'])) ? $_SESSION['lecturer_id'] : $userId;

// Stats
// 1. My assigned courses (Source: course_assignments)
$myCourses = $pdo->prepare("SELECT COUNT(*) FROM course_assignments WHERE lecturer_id = ?");
$myCourses->execute([$lecturerId]);
$myCourses = $myCourses->fetchColumn();

// 2. My Students (Unique students from both enrollments and student_courses)
$myStudents = $pdo->prepare("
    SELECT COUNT(DISTINCT student_id) FROM (
        SELECT student_id, course_id FROM enrollments
        UNION
        SELECT student_id, course_id FROM student_courses
    ) as all_stu
    WHERE course_id IN (SELECT course_id FROM course_assignments WHERE lecturer_id = ?)
");
$myStudents->execute([$lecturerId]);
$myStudents = $myStudents->fetchColumn();

// 3. Assignments created by me
$myAssignments = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE lecturer_id = ?");
$myAssignments->execute([$lecturerId]);
$myAssignments = $myAssignments->fetchColumn();

// 4. Submissions for my assignments
$mySubmissions = $pdo->prepare("
    SELECT COUNT(*) FROM assignment_submissions s
    JOIN assignments a ON a.id = s.assignment_id
    WHERE a.lecturer_id = ?
");
$mySubmissions->execute([$lecturerId]);
$mySubmissions = $mySubmissions->fetchColumn();

// My enrolled students (Union of unique students for accuracy)
$students = $pdo->prepare("
    SELECT DISTINCT st.full_name as name, u.email, c.course_name AS course, all_stu.status, all_stu.created_at as enrolled_at
    FROM (
        SELECT student_id, course_id, status, enrolled_at as created_at FROM enrollments
        UNION
        SELECT student_id, course_id, status, created_at FROM student_courses
    ) as all_stu
    JOIN course_assignments ca ON all_stu.course_id = ca.course_id
    JOIN students st ON st.id = all_stu.student_id
    LEFT JOIN users u ON u.id = st.user_id
    JOIN courses c ON c.id = all_stu.course_id
    WHERE ca.lecturer_id = ?
    ORDER BY enrolled_at DESC LIMIT 8
");
$students->execute([$lecturerId]);
$students = $students->fetchAll();

// My assignments (latest 5)
$assignments = $pdo->prepare("
    SELECT a.id, a.title, a.due_date, a.max_marks, c.course_name AS course,
           (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) AS submissions
    FROM assignments a
    JOIN courses c ON c.id = a.course_id
    WHERE a.lecturer_id = ?
    ORDER BY a.created_at DESC LIMIT 5
");
$assignments->execute([$lecturerId]);
$assignments = $assignments->fetchAll();

// Notices for lecturer
$stmt = $pdo->prepare("
    SELECT n.*, 0 as is_read
    FROM notices n 
    WHERE target_role IN ('all', 'lecturer') 
    AND n.id NOT IN (SELECT notice_id FROM read_notices WHERE user_id = ?)
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$notices = $stmt->fetchAll();

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

  <!-- Stats Grid -->
  <div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="--sc-color: var(--primary);">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $myCourses ?></div>
          <div class="stat-label">Active Courses</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="--sc-color: var(--accent);">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $myStudents ?></div>
          <div class="stat-label">Total Students</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="--sc-color: #f59e0b;">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $myAssignments ?></div>
          <div class="stat-label">Assignments</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="stat-card" style="--sc-color: #6366f1;">
        <div class="stat-icon"><i class="fas fa-check-double"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $mySubmissions ?></div>
          <div class="stat-label">Submissions</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- My Students -->
    <div class="col-lg-7">
      <div class="glass-card h-100">
        <div class="glass-card-title">
          <span style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-users-viewfinder" style="color:var(--primary);"></i> 
            Recent Enrollments
          </span>
          <a href="<?= BASE_URL ?>/frontend/lecturer/students.php" class="btn-lms btn-outline btn-sm" style="font-size:10px; padding:4px 12px; border-radius:8px;">View All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($students)): ?>
            <div class="empty-state"><i class="fas fa-users"></i><p>No students enrolled yet.</p></div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table-lms no-sticky" style="margin-bottom:0;">
              <thead>
                <tr>
                  <th style="padding-left:30px; width: 40%;">Student</th>
                  <th style="width: 30%;">Course</th>
                  <th class="text-center" style="width: 15%; white-space: nowrap;">Status</th>
                  <th class="text-end" style="width: 15%; padding-right:30px;">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $s): 
                   $statusClass = $s['status'] === 'active' ? 'success' : ($s['status'] === 'completed' ? 'info' : 'danger');
                   $initials = strtoupper(substr($s['name'] ?? 'S', 0, 1));
                ?>
                <tr style="transition:0.2s;">
                  <td style="padding-left:30px;">
                    <div>
                      <div class="fw-700" style="font-size:13.5px; color:var(--text-main); line-height:1.2;"><?= htmlspecialchars($s['name']) ?></div>
                      <div class="text-muted" style="font-size:10.5px;"><?= htmlspecialchars($s['email']) ?></div>
                    </div>
                  </td>
                  <td style="max-width:180px;">
                    <div class="text-truncate fw-600" style="font-size:13px; color:var(--text-main);" title="<?= htmlspecialchars($s['course']) ?>">
                      <?= htmlspecialchars($s['course']) ?>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge-lms <?= $statusClass ?>" style="font-size:10px; padding:3px 10px; border-radius:6px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                      <?= $s['status'] ?>
                    </span>
                  </td>
                  <td class="text-end fw-600" style="padding-right:30px; font-size:12.5px; color:var(--text-muted);">
                    <?= date('M d, Y', strtotime($s['enrolled_at'])) ?>
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

    <!-- Right Column -->
    <div class="col-lg-5 d-flex flex-column gap-4">

      <!-- Assignments -->
      <div class="glass-card">
        <div class="glass-card-title">
          <span style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-file-signature" style="color:#f59e0b;"></i> 
            Latest Assignments
          </span>
          <a href="<?= BASE_URL ?>/frontend/lecturer/assignments/index.php" class="btn-lms btn-outline btn-sm" style="font-size:10px; padding:4px 12px; border-radius:8px;">All</a>
        </div>
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($assignments)): ?>
            <div class="empty-state"><i class="fas fa-file-alt"></i><p>No assignments created yet.</p></div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table-lms no-sticky" style="margin-bottom:0;">
              <thead>
                <tr>
                  <th style="padding-left:25px; width: 50%;">Assignment</th>
                  <th class="text-center" style="width: 25%;">Submissions</th>
                  <th class="text-end" style="width: 25%; padding-right:25px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assignments as $a): 
                   $subCount = (int)$a['submissions'];
                   $subColor = $subCount > 0 ? 'var(--primary)' : 'var(--text-muted)';
                ?>
                <tr>
                  <td style="padding-left:25px; padding-top:15px; padding-bottom:15px;">
                    <div>
                      <div class="fw-700" style="font-size:13.5px; color:var(--text-main); line-height:1.2;"><?= htmlspecialchars($a['title']) ?></div>
                      <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($a['course']) ?></div>
                      <div style="font-size:10px; margin-top:4px; font-weight:700; color:var(--accent);">
                        <i class="far fa-calendar-check" style="margin-right:4px;"></i>
                        Due: <?= $a['due_date'] ? date('M d, Y', strtotime($a['due_date'])) : 'No deadline' ?>
                      </div>
                    </div>
                  </td>
                  <td class="text-center">
                    <div style="display:inline-flex; flex-direction:column; align-items:center;">
                        <span class="fw-800" style="font-size:18px; color:<?= $subColor ?>; line-height:1;"><?= $subCount ?></span>
                        <span class="text-muted fw-700" style="font-size:9px; text-transform:uppercase; letter-spacing:0.5px;">Received</span>
                    </div>
                  </td>
                  <td class="text-end" style="padding-right:25px;">
                    <a href="<?= BASE_URL ?>/frontend/lecturer/submissions.php?assignment_id=<?= $a['id'] ?>" class="btn-lms btn-outline btn-sm" style="font-size:10px; padding:6px 12px; border-radius:10px; font-weight:700; border-color:rgba(var(--primary-rgb), 0.2);">
                        <i class="fas fa-eye" style="margin-right:5px;"></i> View
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Notices -->
      <div class="glass-card">
        <div class="glass-card-title">
          <span style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-bullhorn" style="color:var(--primary);"></i> 
            Institutional Notices
          </span>
        </div>
        <div class="card-lms-body">
          <?php if (empty($notices)): ?>
            <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notices.</p></div>
          <?php else: ?>
          <div class="notice-list-lms">
            <?php foreach ($notices as $n): ?>
            <div class="notice-item-lms <?= $n['is_read']?'is-read':'is-unread' ?>" 
                 data-id="<?= $n['id'] ?>"
                 data-is-read="<?= $n['is_read'] ?>"
                 data-title="<?= htmlspecialchars($n['title']) ?>"
                 data-content="<?= htmlspecialchars($n['content']) ?>"
                 data-author="Admin"
                 data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>">
              <div class="d-flex align-items-center gap-2">
                <?php if($n['is_read']): ?>
                    <span class="badge-lms success outline" style="font-size:8px; padding:1px 6px; border-radius:100px;">READ</span>
                <?php else: ?>
                    <span class="unread-indicator"></span>
                <?php endif; ?>
                <div>
                  <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($n['title']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                </div>
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

