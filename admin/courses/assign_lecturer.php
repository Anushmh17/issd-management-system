<?php
// =====================================================
// ISSD Management - Admin: Assign Lecturer to Course
// admin/courses/assign_lecturer.php
// =====================================================
define('PAGE_TITLE', 'Assign Lecturer');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN); // STRICTLY ADMIN ONLY

$errors  = [];
$success = false;

// Pre-select course or lecturer from query string
$preselectedCourseId   = (int)($_GET['course_id'] ?? 0);
$preselectedLecturerId = (int)($_GET['lecturer_id'] ?? 0);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'assign') {
        $courseId   = (int)($_POST['course_id']   ?? 0);
        $lecturerId = (int)($_POST['lecturer_id'] ?? 0);
        $date       = !empty($_POST['assigned_date']) ? $_POST['assigned_date'] : null;

        $result = assignLecturer($pdo, $courseId, $lecturerId, $date);
        if ($result['success']) {
            setFlash('success', 'Lecturer assigned successfully.');
            header('Location: index.php'); exit;
        }
        $errors = $result['errors'];
    }

    if ($act === 'remove') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        removeLecturerAssignment($pdo, $courseId);
        setFlash('success', 'Lecturer assignment removed.');
        header('Location: assign_lecturer.php'); exit;
    }
}

$courses   = getCoursesList($pdo, ['status' => 'active'], 1, 200)['courses'];
$lecturers = getActiveLecturers($pdo);

// Get existing assignments for display
$assignedStmt = $pdo->query("
    SELECT ca.*, c.course_name, c.course_code, l.name AS lecturer_name, l.id AS lid,
           l.department
    FROM course_assignments ca
    JOIN courses c ON c.id = ca.course_id
    JOIN lecturers l ON l.id = ca.lecturer_id
    ORDER BY ca.created_at DESC
");
$assignments = $assignedStmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<style>
  /* --- PREMIUM BORDER REINFORCEMENT (FORCE APPLIED) --- */
  body.lms-dark-mode .card-lms,
  body.lms-dark-mode .stat-card,
  body.lms-dark-mode .bento-card,
  body.lms-dark-mode .assignment-item {
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4), 0 0 15px rgba(255, 255, 255, 0.03) !important;
    border-radius: 24px !important;
    background: rgba(30, 41, 59, 0.5) !important;
    backdrop-filter: blur(20px) !important;
  }
</style>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Assign Lecturer to Course</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Courses</a> &rsaquo;
        <span>Assign Lecturer</span>
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

    <!-- Assignment Form -->
    <div class="col-lg-5">
      <div class="card-lms mb-20">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-link" style="color:#5b4efa;"></i> New Assignment
          </div>
        </div>
        <div class="card-lms-body">
          <form method="POST" action="assign_lecturer.php" id="assignLecturerForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="act" value="assign">

            <div class="form-group-lms">
              <label for="course_id">Select Course <span class="req">*</span></label>
              <select id="course_id" name="course_id" class="form-control-lms" required>
                <option value="">"" Choose a course ""</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= $c['id'] ?>"
                    <?= (int)$c['id'] === $preselectedCourseId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['course_code']) ?> "" <?= htmlspecialchars($c['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group-lms">
              <label for="lecturer_id">Select Lecturer <span class="req">*</span></label>
              <?php if (empty($lecturers)): ?>
                <div class="alert-lms warning" style="padding:12px;font-size:13px;">
                  <i class="fas fa-exclamation-triangle"></i>
                  No active lecturers found. <a href="<?= BASE_URL ?>/frontend/admin/lecturers.php?action=add">Add a lecturer first.</a>
                </div>
              <?php else: ?>
              <select id="lecturer_id" name="lecturer_id" class="form-control-lms" required>
                <option value="">"" Choose a lecturer ""</option>
                <?php foreach ($lecturers as $l): ?>
                  <option value="<?= $l['id'] ?>"
                    <?= (int)$l['id'] === $preselectedLecturerId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                    <?= !empty($l['department']) ? ' (' . htmlspecialchars($l['department']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
            </div>

            <div class="form-group-lms">
              <label for="assigned_date">Assignment Date</label>
              <input type="date" id="assigned_date" name="assigned_date"
                     class="form-control-lms" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-actions" style="margin-bottom:0;">
              <button type="submit" class="btn-primary-grad" id="btn-assign-lecturer"
                      <?= empty($lecturers) ? 'disabled' : '' ?>>
                <i class="fas fa-link"></i> <span id="submit-text">Assign Lecturer</span>
              </button>
              <button type="button" class="btn-lms btn-outline" onclick="resetAssignForm()" id="btn-cancel-edit" style="display:none;">
                <i class="fas fa-xmark"></i> Cancel Edit
              </button>
              <a href="index.php" class="btn-lms btn-outline" id="btn-back-courses"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Current Assignments -->
    <div class="col-lg-7">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-list-check" style="color:#5b4efa;"></i> Current Assignments
          </div>
          <span class="badge-lms info"><?= count($assignments) ?></span>
        </div>
        <div class="card-lms-body" style="padding:0;overflow-x:auto;">
          <?php if (empty($assignments)): ?>
            <div class="empty-state" style="padding:40px 20px;">
              <i class="fas fa-link-slash"></i>
              <p>No lecturer assignments yet.</p>
            </div>
          <?php else: ?>
          <div class="assignment-list">
            <?php foreach ($assignments as $a): ?>
            <div class="assignment-item">
              
              <!-- Course Info -->
              <div class="assignment-course-info">
                <div class="fw-700" style="font-size:14px; color:var(--text-main); margin-bottom:4px;">
                  <?= htmlspecialchars($a['course_name']) ?>
                </div>
                <div class="d-flex align-center gap-2">
                  <span class="course-code-badge" style="font-size:10px; padding:2px 8px;"><?= htmlspecialchars($a['course_code']) ?></span>
                  <div class="assignment-date-tag">
                    <i class="far fa-calendar-check"></i>
                    <?= $a['assigned_date'] ? date('d M Y', strtotime($a['assigned_date'])) : 'No date' ?>
                  </div>
                </div>
              </div>

              <!-- Lecturer Info -->
              <div class="assignment-lecturer-info">
                <div class="avatar-initials" style="width:36px; height:36px; font-size:14px; background:linear-gradient(135deg, #5b4efa, #8b5cf6); flex-shrink:0;">
                  <?= strtoupper(substr($a['lecturer_name'], 0, 1)) ?>
                </div>
                <div style="min-width:0;">
                  <div class="fw-700" style="font-size:14px; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($a['lecturer_name']) ?>
                  </div>
                  <?php if ($a['department']): ?>
                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($a['department']) ?></div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Actions -->
              <div class="assignment-actions">
                <button type="button" class="btn-lms btn-outline btn-sm"
                        style="width:36px; height:36px; padding:0; border-radius:10px; display:flex; align-items:center; justify-content:center;"
                        title="Edit Assignment"
                        onclick="editAssignment(<?= $a['course_id'] ?>, <?= $a['lecturer_id'] ?>, '<?= $a['assigned_date'] ?>')">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="POST" action="assign_lecturer.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="act" value="remove">
                  <input type="hidden" name="course_id" value="<?= $a['course_id'] ?>">
                  <button type="submit"
                          class="btn-lms btn-danger btn-sm"
                          style="width:36px; height:36px; padding:0; border-radius:10px; display:flex; align-items:center; justify-content:center;"
                          title="Remove Assignment"
                          data-confirm="Remove lecturer from '<?= htmlspecialchars($a['course_name']) ?>'?"
                          data-confirm-type="danger">
                    <i class="fas fa-unlink"></i>
                  </button>
                </form>
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

<?php
$extraJS = <<<'JS'
<script>
function editAssignment(courseId, lecturerId, date) {
    document.getElementById('course_id').value = courseId;
    document.getElementById('lecturer_id').value = lecturerId;
    document.getElementById('assigned_date').value = date;
    
    // Update UI
    document.getElementById('submit-text').innerText = 'Update Assignment';
    document.getElementById('btn-cancel-edit').style.display = 'inline-block';
    document.getElementById('btn-back-courses').style.display = 'none';
    
    // Scroll to form
    document.getElementById('assignLecturerForm').scrollIntoView({ behavior: 'smooth' });

    // Sync Select2 if initialized
    if (typeof jQuery !== 'undefined') {
        $('#course_id').trigger('change');
        $('#lecturer_id').trigger('change');
    }
}

function resetAssignForm() {
    document.getElementById('assignLecturerForm').reset();
    document.getElementById('submit-text').innerText = 'Assign Lecturer';
    document.getElementById('btn-cancel-edit').style.display = 'none';
    document.getElementById('btn-back-courses').style.display = 'inline-block';
    
    if (typeof jQuery !== 'undefined') {
        $('#course_id').trigger('change');
        $('#lecturer_id').trigger('change');
    }
}

$(document).ready(function() {
    $('#course_id, #lecturer_id').select2({
        width: '100%',
        placeholder: 'Select an option'
    });

    // Initialize Flatpickr for Assignment Date
    flatpickr("#assigned_date", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        allowInput: true,
        altInput: true,
        altFormat: "F j, Y"
    });
});
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php'; 
?>

