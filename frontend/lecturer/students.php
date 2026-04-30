<?php
// =====================================================
// ISSD Management - Lecturer: My Students
// =====================================================
define('PAGE_TITLE', 'My Students');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);
$userId = currentUserId();

$search = trim($_GET['q'] ?? '');
$sql = "SELECT DISTINCT u.id, u.name, u.email, u.phone, 
               c.course_name AS course_name, c.course_code AS course_code,
               e.status AS enrollment_status, e.enrolled_at,
               sp.student_id
        FROM enrollments e
        JOIN users u ON u.id = e.student_id
        JOIN courses c ON c.id = e.course_id
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        WHERE e.lecturer_id = ?";
$params = [$userId];

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR c.course_name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " ORDER BY e.enrolled_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Students</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Lecturer &rsaquo; <span>Students</span></div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-users"></i> Students Enrolled in My Courses (<?= count($students) ?>)</div>
      <form method="GET" class="header-filter-form">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-lms btn-primary btn-sm">Search</button>
      </form>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($students)): ?>
        <div class="empty-state"><i class="fas fa-users"></i><p>No students are currently enrolled in your courses.</p></div>
      <?php else: ?>
      <table class="table-lms searchable-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Contact</th>
            <th>Course</th>
            <th>Status</th>
            <th>Enrolled Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="avatar-initials" style="width:32px;height:32px;font-size:12px;"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['student_id'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td>
                <div><i class="fas fa-envelope text-muted"></i> <?= htmlspecialchars($s['email']) ?></div>
                <?php if($s['phone']): ?><div><i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($s['phone']) ?></div><?php endif; ?>
            </td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($s['course_name']) ?></div>
              <small class="text-muted"><?= htmlspecialchars($s['course_code']) ?></small>
            </td>
            <td>
              <span class="badge-lms <?= $s['enrollment_status']==='active'?'success':($s['enrollment_status']==='completed'?'info':'danger') ?>">
                <?= ucfirst($s['enrollment_status']) ?>
              </span>
            </td>
            <td><?= date('M d, Y', strtotime($s['enrolled_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

