<?php
// =====================================================
// LEARN Management - Admin: Assign Student to Course
// admin/courses/assign_student.php
// =====================================================
define('PAGE_TITLE', 'Enroll Student');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

$errors = [];
$preselectedCourseId   = (int)($_GET['course_id']   ?? 0);
$preselectedStudentId  = (int)($_GET['student_id']  ?? 0);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'update_status') {
    $scId   = (int)($_POST['sc_id'] ?? 0);
    $newSt  = $_POST['new_status'] ?? '';
    if ($scId && updateStudentCourseStatus($pdo, $scId, $newSt)) {
        setFlash('success', 'Enrollment status updated.');
    } else {
        setFlash('danger', 'Failed to update status.');
    }
    header('Location: assign_student.php'); exit;
}

// Handle new enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'enroll') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $courseId  = (int)($_POST['course_id']  ?? 0);
    $data = [
        'start_date' => $_POST['start_date'] ?? '',
        'end_date'   => $_POST['end_date']   ?? '',
    ];
    $result = assignStudentToCourse($pdo, $studentId, $courseId, $data);
    if ($result['success']) {
        setFlash('success', 'Student enrolled successfully.');
        header('Location: assign_student.php'); exit;
    }
    $errors = $result['errors'];
}

$students  = getActiveStudentsForCourse($pdo);
$courses   = getActiveCourses($pdo);

// Get recent enrollments
$recentStmt = $pdo->query("
    SELECT sc.*, s.full_name, s.student_id AS sid, s.batch_number,
           c.course_name, c.course_code, c.monthly_fee
    FROM student_courses sc
    JOIN students s ON s.id = sc.student_id
    JOIN courses  c ON c.id = sc.course_id
    ORDER BY sc.created_at DESC
    LIMIT 50
");
$enrollments = $recentStmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Enroll Student to Course</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Courses</a> &rsaquo;
        <span>Enroll Student</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back to Courses</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    </div>
  <?php endif; ?>

  <div class="row g-3">

    <!-- Enrollment Form -->
    <div class="col-lg-5">
      <div class="card-lms mb-20">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-user-graduate" style="color:#3b82f6;"></i> New Enrollment
          </div>
        </div>
        <div class="card-lms-body">
          <form method="POST" action="assign_student.php" id="enrollForm">
            <input type="hidden" name="act" value="enroll">

            <div class="form-group-lms">
              <label for="student_id">Select Student <span class="req">*</span></label>
              <?php if (empty($students)): ?>
                <div class="alert-lms warning" style="padding:12px;font-size:13px;">
                  <i class="fas fa-exclamation-triangle"></i>
                  No students found. <a href="<?= BASE_URL ?>/admin/students/add.php">Add a student first.</a>
                </div>
              <?php else: ?>
              <select id="student_id" name="student_id" class="form-control-lms" required
                      onchange="updateFeePreview()">
                <option value="">— Choose a student —</option>
                <?php foreach ($students as $st): ?>
                  <option value="<?= $st['id'] ?>"
                    <?= (int)$st['id'] === $preselectedStudentId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['student_id']) ?> — <?= htmlspecialchars($st['full_name']) ?>
                    (<?= htmlspecialchars($st['batch_number']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </div>

            <div class="form-group-lms">
              <label for="course_id">Select Course <span class="req">*</span></label>
              <?php if (empty($courses)): ?>
                <div class="alert-lms warning" style="padding:12px;font-size:13px;">
                  <i class="fas fa-exclamation-triangle"></i>
                  No active courses. <a href="add.php">Add a course first.</a>
                </div>
              <?php else: ?>
              <select id="course_id" name="course_id" class="form-control-lms" required
                      onchange="updateFeePreview()">
                <option value="">— Choose a course —</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= $c['id'] ?>"
                          data-fee="<?= $c['monthly_fee'] ?>"
                    <?= (int)$c['id'] === $preselectedCourseId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['course_code']) ?> — <?= htmlspecialchars($c['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </div>

            <!-- Fee Preview -->
            <div id="feePreview" style="display:none;background:#f0fdf4;border:1.5px solid #a7f3d0;
                                        border-radius:8px;padding:12px 14px;margin-bottom:16px;">
              <div style="font-size:11px;color:#6b7280;text-transform:uppercase;font-weight:700;margin-bottom:4px;">
                Monthly Fee
              </div>
              <div id="feeAmount" style="font-size:22px;font-weight:800;color:#059669;"></div>
              <div style="font-size:11px;color:#6b7280;">per month — used for payment tracking</div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="start_date">Start Date</label>
                  <input type="date" id="start_date" name="start_date"
                         class="form-control-lms" value="<?= date('Y-m-d') ?>">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="end_date">Expected End Date</label>
                  <input type="date" id="end_date" name="end_date" class="form-control-lms">
                </div>
              </div>
            </div>

            <div class="form-actions" style="margin-bottom:0;">
              <button type="submit" class="btn-primary-grad" id="btn-enroll-student"
                      <?= (empty($students) || empty($courses)) ? 'disabled' : '' ?>>
                <i class="fas fa-user-plus"></i> Enroll Student
              </button>
              <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-xmark"></i> Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Enrollments Table -->
    <div class="col-lg-7">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-list-check" style="color:#3b82f6;"></i> Recent Enrollments
          </div>
          <span class="badge-lms info"><?= count($enrollments) ?></span>
        </div>
        <div class="card-lms-body" style="padding:0;overflow-x:auto;max-height:550px;overflow-y:auto;">
          <?php if (empty($enrollments)): ?>
            <div class="empty-state" style="padding:40px 20px;">
              <i class="fas fa-user-slash"></i>
              <p>No enrollments yet.</p>
            </div>
          <?php else: ?>
          <table class="table-lms">
            <thead>
              <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Start Date</th>
                <th>Status</th>
                <th>Update</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($enrollments as $e): ?>
              <tr>
                <td>
                  <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($e['full_name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($e['sid']) ?></div>
                </td>
                <td>
                  <div class="fw-600" style="font-size:12px;"><?= htmlspecialchars($e['course_name']) ?></div>
                  <div style="font-size:10px;color:#94a3b8;">Rs. <?= number_format((float)$e['monthly_fee'],0) ?>/mo</div>
                </td>
                <td style="font-size:12px;color:#64748b;">
                  <?= $e['start_date'] ? date('d M Y', strtotime($e['start_date'])) : '—' ?>
                </td>
                <td>
                  <?php
                    $sc = match($e['status']) {
                        'ongoing'   => '#3b82f6',
                        'completed' => '#059669',
                        'dropped'   => '#dc2626',
                    };
                  ?>
                  <span style="font-size:11px;font-weight:700;color:<?= $sc ?>;text-transform:uppercase;">
                    <?= $e['status'] ?>
                  </span>
                </td>
                <td>
                  <form method="POST" action="assign_student.php" style="display:flex;gap:4px;">
                    <input type="hidden" name="act"    value="update_status">
                    <input type="hidden" name="sc_id"  value="<?= $e['id'] ?>">
                    <select name="new_status" class="form-control-lms"
                            style="padding:4px 8px;font-size:11px;height:auto;min-width:100px;">
                      <option value="ongoing"   <?= $e['status']==='ongoing'   ? 'selected' : '' ?>>Ongoing</option>
                      <option value="completed" <?= $e['status']==='completed' ? 'selected' : '' ?>>Completed</option>
                      <option value="dropped"   <?= $e['status']==='dropped'   ? 'selected' : '' ?>>Dropped</option>
                    </select>
                    <button type="submit" class="btn-lms btn-primary btn-sm" title="Update Status">
                      <i class="fas fa-check"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
$extraJS = <<<'JS'
<script>
function updateFeePreview() {
  const sel    = document.getElementById('course_id');
  const prev   = document.getElementById('feePreview');
  const amount = document.getElementById('feeAmount');
  if (!sel) return;
  const opt = sel.options[sel.selectedIndex];
  const fee = opt ? parseFloat(opt.getAttribute('data-fee')) : 0;
  if (fee > 0) {
    prev.style.display = 'block';
    amount.textContent = 'Rs. ' + fee.toLocaleString('en-LK', {minimumFractionDigits:2});
  } else {
    prev.style.display = 'none';
  }
}
document.addEventListener('DOMContentLoaded', updateFeePreview);
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
