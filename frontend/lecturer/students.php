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
$lecturerId = (isset($_SESSION['lecturer_id'])) ? $_SESSION['lecturer_id'] : $userId;

$search = trim($_GET['q'] ?? '');
$sql = "SELECT DISTINCT st.user_id as id, st.full_name as name, st.personal_email as email, st.phone_number as phone, 
               c.course_name, c.course_code,
               all_stu.status as enrollment_status, all_stu.created_at as enrolled_at,
               st.student_id as reg_number
        FROM (
            SELECT student_id, course_id, status, enrolled_at as created_at FROM enrollments
            UNION
            SELECT student_id, course_id, status, created_at FROM student_courses
        ) as all_stu
        JOIN course_assignments ca ON all_stu.course_id = ca.course_id
        JOIN students st ON st.id = all_stu.student_id
        LEFT JOIN users u ON u.id = st.user_id
        JOIN courses c ON c.id = all_stu.course_id
        WHERE ca.lecturer_id = ?";
$params = [$lecturerId];

if ($search) {
    $sql .= " AND (st.full_name LIKE ? OR st.personal_email LIKE ? OR c.course_name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " ORDER BY enrolled_at DESC";

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

  <div class="glass-card">
    <div class="glass-card-title">
      <span style="display:flex; align-items:center; gap:10px;">
        <i class="fas fa-users" style="color:var(--primary);"></i> 
        Students Enrolled (<?= count($students) ?>)
      </span>
      
      <form method="GET" class="premium-search-form d-print-none">
        <div class="search-input-group">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search student name, email or course..." value="<?= htmlspecialchars($search) ?>">
          <?php if($search): ?>
            <a href="?" class="search-clear"><i class="fas fa-times-circle"></i></a>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn-search-premium">Search</button>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0; overflow-x:auto;">
      <?php if (empty($students)): ?>
        <div class="empty-state">
          <i class="fas fa-users-slash" style="font-size:48px; color:var(--primary); opacity:0.5;"></i>
          <p style="font-size:16px; margin-top:15px;">No students are currently enrolled in your courses.</p>
        </div>
      <?php else: ?>
      <table class="table-lms searchable-table">
        <thead style="background: rgba(0,0,0,0.02);">
          <tr>
            <th style="padding:15px 20px;">STUDENT</th>
            <th>CONTACT DETAILS</th>
            <th>ASSIGNED COURSE</th>
            <th>ENROLLMENT STATUS</th>
            <th style="text-align:right; padding-right:20px;">JOINED DATE</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr class="premium-row">
            <td style="padding:15px 20px;">
              <div>
                <div class="fw-800" style="color:var(--text-main); font-size:14px;"><?= htmlspecialchars($s['name'] ?? 'Unknown Student') ?></div>
                <div class="text-muted fw-600" style="font-size:10px; text-transform:uppercase; letter-spacing:0.5px;"><?= htmlspecialchars($s['reg_number'] ?? 'N/A') ?></div>
              </div>
            </td>
            <td>
                <div class="mb-1" style="font-size:13px;"><i class="fas fa-envelope" style="color:var(--primary); width:18px;"></i> <?= htmlspecialchars($s['email']) ?></div>
                <?php if($s['phone']): ?><div style="font-size:13px;"><i class="fab fa-whatsapp" style="color:#25d366; width:18px;"></i> <?= htmlspecialchars($s['phone']) ?></div><?php endif; ?>
            </td>
            <td>
              <div class="fw-700" style="font-size:13px; color:var(--text-main);"><?= htmlspecialchars($s['course_name']) ?></div>
              <div class="text-muted fw-600" style="font-size:10px;"><?= htmlspecialchars($s['course_code']) ?></div>
            </td>
            <td>
              <span class="badge-lms <?= $s['enrollment_status']==='active'?'success':($s['enrollment_status']==='completed'?'info':'danger') ?>" style="border-radius:6px; font-weight:700; font-size:10px; padding:4px 10px;">
                <i class="fas fa-circle" style="font-size:6px; margin-right:5px; vertical-align:middle;"></i>
                <?= strtoupper($s['enrollment_status']) ?>
              </span>
            </td>
            <td style="text-align:right; padding-right:20px;">
              <div class="fw-700" style="font-size:13px; color:var(--text-main);"><?= date('M d, Y', strtotime($s['enrolled_at'])) ?></div>
              <div class="text-muted" style="font-size:11px;"><?= date('h:i A', strtotime($s['enrolled_at'])) ?></div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

