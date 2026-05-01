<?php
// =====================================================
// ISSD Management - Admin: Assign Student to Course
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

// Handle enrollment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'update_enrollment') {
    $scId = (int)($_POST['sc_id'] ?? 0);
    $data = [
        'start_date' => $_POST['start_date'] ?? '',
        'end_date'   => $_POST['end_date']   ?? '',
        'status'     => $_POST['status']     ?? 'ongoing'
    ];
    $result = updateStudentCourse($pdo, $scId, $data);
    if ($result['success']) {
        setFlash('success', 'Enrollment updated successfully.');
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
            <input type="hidden" name="act" value="enroll" id="form-act">
            <input type="hidden" name="sc_id" value="" id="form-sc-id">

            <div class="form-group-lms">
              <label for="student_id"><i class="fas fa-user-circle me-1"></i> Select Student <span class="req">*</span></label>
              <?php if (empty($students)): ?>
                <div class="alert-lms warning" style="padding:12px;font-size:13px;">
                  <i class="fas fa-exclamation-triangle"></i>
                  No students found. <a href="<?= BASE_URL ?>/admin/students/add.php">Add a student first.</a>
                </div>
              <?php else: ?>
              <select id="student_id" name="student_id" class="form-control-lms" required
                      onchange="updateFeePreview()">
                <option value="">-- Choose a student --</option>
                <?php foreach ($students as $st): ?>
                  <option value="<?= $st['id'] ?>"
                    <?= (int)$st['id'] === $preselectedStudentId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['student_id']) ?> "" <?= htmlspecialchars($st['full_name']) ?>
                    (<?= htmlspecialchars($st['batch_number']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </div>

            <div class="form-group-lms">
              <label for="course_id"><i class="fas fa-book me-1"></i> Select Course <span class="req">*</span></label>
              <?php if (empty($courses)): ?>
                <div class="alert-lms warning" style="padding:12px;font-size:13px;">
                  <i class="fas fa-exclamation-triangle"></i>
                  No active courses. <a href="add.php">Add a course first.</a>
                </div>
              <?php else: ?>
              <select id="course_id" name="course_id" class="form-control-lms" required
                      onchange="updateFeePreview()">
                <option value="">"" Choose a course ""</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= $c['id'] ?>"
                          data-fee="<?= $c['monthly_fee'] ?>"
                    <?= (int)$c['id'] === $preselectedCourseId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['course_code']) ?> "" <?= htmlspecialchars($c['course_name']) ?>
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
              <div style="font-size:11px;color:#6b7280;">per month "" used for payment tracking</div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="start_date"><i class="fas fa-calendar-day me-1"></i> Start Date</label>
                  <input type="date" id="start_date" name="start_date"
                         class="form-control-lms" value="<?= date('Y-m-d') ?>">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="end_date"><i class="fas fa-calendar-check me-1"></i> Expected End Date</label>
                  <input type="date" id="end_date" name="end_date" class="form-control-lms">
                </div>
              </div>
            </div>

            <div class="form-group-lms" id="status-group" style="display:none;">
              <label for="status"><i class="fas fa-signal me-1"></i> Status</label>
              <select name="status" id="status" class="form-control-lms">
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
                <option value="dropped">Dropped</option>
              </select>
            </div>

            <div class="form-actions" style="margin-bottom:0;">
              <button type="submit" class="btn-primary-grad" id="btn-enroll-student"
                      <?= (empty($students) || empty($courses)) ? 'disabled' : '' ?>>
                <i class="fas fa-user-plus"></i> <span id="submit-text">Enroll Student</span>
              </button>
              <button type="button" class="btn-lms btn-outline" id="btn-cancel-edit" style="display:none;" onclick="resetEnrollForm()">
                <i class="fas fa-xmark"></i> Cancel Edit
              </button>
              <a href="index.php" class="btn-lms btn-outline" id="btn-back-courses"><i class="fas fa-arrow-left"></i> Back to Courses</a>
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
        <div class="card-lms-body" style="padding:0;">
          <?php if (empty($enrollments)): ?>
            <div class="empty-state" style="padding:40px 20px;">
              <i class="fas fa-user-slash"></i>
              <p>No enrollments yet.</p>
            </div>
          <?php else: ?>
          <table class="table-lms table-hover-edit">
            <thead>
              <tr>
                <th style="width: 25%;">Student Information</th>
                <th style="width: 55%;">Enrolled Course</th>
                <th style="width: 20%; text-align:right;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($enrollments as $e): ?>
              <tr onclick='editEnrollment(<?= json_encode($e) ?>)' style="cursor:pointer;" title="Click to Edit Enrollment">
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar-initials" style="width:36px; height:36px; font-size:12px; background: var(--primary-light); color: var(--primary);">
                      <?= strtoupper(substr($e['full_name'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-700" style="font-size:13.5px; color: var(--text-main);"><?= htmlspecialchars($e['full_name']) ?></div>
                      <div class="text-muted" style="font-size:11px; font-weight:600;"><?= htmlspecialchars($e['sid']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="fw-700" style="font-size:13px; color: var(--primary);"><?= htmlspecialchars($e['course_name']) ?></div>
                  <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="course-code-badge" style="font-size:9px;"><?= htmlspecialchars($e['course_code']) ?></span>
                    <span style="font-size:11px; color: var(--text-muted); font-weight:500;">
                      <i class="far fa-calendar-alt me-1"></i> <?= $e['start_date'] ? date('d M Y', strtotime($e['start_date'])) : 'N/A' ?>
                    </span>
                  </div>
                </td>
                <td style="text-align:right;">
                  <div class="d-flex align-items-center justify-content-end gap-2">
                    <?php
                      $statusClass = match($e['status']) {
                          'ongoing'   => 'info',
                          'completed' => 'success',
                          'dropped'   => 'danger',
                      };
                    ?>
                    <span class="badge-lms <?= $statusClass ?>" 
                          style="padding:6.5px 14px; font-size:10.5px; min-width:90px; justify-content:center; font-weight:800; letter-spacing:0.3px;">
                      <i class="fas fa-edit me-1" style="font-size:9px; opacity:0.8;"></i>
                      <?= strtoupper($e['status']) ?>
                    </span>
                  </div>
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

$(document).ready(function() {
    updateFeePreview();
    
    // Initialize Select2 for Students and Courses
    $('#student_id, #course_id').select2({
        width: '100%',
        placeholder: 'Select an option'
    });

    // Handle course selection for fee preview
    $('#course_id').on('change', function() {
        updateFeePreview();
    });

    // Initialize Flatpickr for Dates
    flatpickr("#start_date", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        altInput: true,
        altFormat: "F j, Y"
    });

    flatpickr("#end_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        altInput: true,
        altFormat: "F j, Y"
    });
});

function editEnrollment(data) {
    // Populate form
    document.getElementById('form-act').value = 'update_enrollment';
    document.getElementById('form-sc-id').value = data.id;
    
    // Select2 values
    $('#student_id').val(data.student_id).trigger('change');
    $('#course_id').val(data.course_id).trigger('change');
    
    // Disable student/course selection for edits to prevent accidental major changes
    $('#student_id, #course_id').prop('disabled', true);
    
    // Dates (using flatpickr instance if available)
    document.getElementById('start_date')._flatpickr.setDate(data.start_date);
    if (data.end_date) {
        document.getElementById('end_date')._flatpickr.setDate(data.end_date);
    } else {
        document.getElementById('end_date')._flatpickr.clear();
    }

    // Status
    document.getElementById('status-group').style.display = 'block';
    document.getElementById('status').value = data.status;
    
    // UI changes
    document.getElementById('submit-text').innerText = 'Update Enrollment';
    document.getElementById('btn-enroll-student').innerHTML = '<i class="fas fa-save"></i> <span id="submit-text">Update Enrollment</span>';
    document.getElementById('btn-cancel-edit').style.display = 'inline-block';
    document.getElementById('btn-back-courses').style.display = 'none';

    // Scroll to form
    document.getElementById('enrollForm').scrollIntoView({ behavior: 'smooth' });
}

function resetEnrollForm() {
    document.getElementById('form-act').value = 'enroll';
    document.getElementById('form-sc-id').value = '';
    
    $('#student_id, #course_id').prop('disabled', false);
    $('#student_id').val('').trigger('change');
    $('#course_id').val('').trigger('change');
    
    document.getElementById('start_date')._flatpickr.setDate(new Date());
    document.getElementById('end_date')._flatpickr.clear();
    
    document.getElementById('status-group').style.display = 'none';
    document.getElementById('submit-text').innerText = 'Enroll Student';
    document.getElementById('btn-enroll-student').innerHTML = '<i class="fas fa-user-plus"></i> <span id="submit-text">Enroll Student</span>';
    document.getElementById('btn-cancel-edit').style.display = 'none';
    document.getElementById('btn-back-courses').style.display = 'inline-block';
}

// Ensure disabled fields are enabled before submit so POST values are sent
$('#enrollForm').on('submit', function() {
    $('#student_id, #course_id').prop('disabled', false);
});
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>

