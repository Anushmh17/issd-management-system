<?php
// =====================================================
// LEARN Management - Admin: Edit Course
// admin/courses/edit.php
// =====================================================
define('PAGE_TITLE', 'Edit Course');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','Invalid course.'); header('Location: index.php'); exit; }

$course = getCourseById($pdo, $id);
if (!$course) { setFlash('danger','Course not found.'); header('Location: index.php'); exit; }

$errors = [];
$form = [
    'course_name'  => $course['course_name'],
    'course_code'  => $course['course_code'],
    'duration'     => $course['duration'] ?? '',
    'monthly_fee'  => $course['monthly_fee'],
    'description'  => $course['description'] ?? '',
    'status'       => $course['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';
    $result = updateCourse($pdo, $id, $form);
    if ($result['success']) {
        setFlash('success', 'Course <strong>' . htmlspecialchars($form['course_name']) . '</strong> updated successfully.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
}

// Get course students for display
$courseStudents = getCourseStudents($pdo, $id);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Edit Course</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Courses</a> &rsaquo;
        <span>Edit: <?= htmlspecialchars($course['course_name']) ?></span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <!-- Course Banner -->
  <div class="course-edit-banner mb-20">
    <div class="ceb-icon"><i class="fas fa-book-open"></i></div>
    <div class="ceb-info">
      <div class="ceb-name"><?= htmlspecialchars($course['course_name']) ?></div>
      <div class="ceb-meta">
        <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars($course['course_code']) ?></span>
        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($course['duration'] ?: 'N/A') ?></span>
        <span><i class="fas fa-money-bill"></i> Rs. <?= number_format((float)$course['monthly_fee'], 0) ?>/mo</span>
        <?php if ($course['lecturer_name']): ?>
          <span><i class="fas fa-chalkboard-user"></i> <?= htmlspecialchars($course['lecturer_name']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="ceb-actions">
      <a href="assign_lecturer.php?course_id=<?= $id ?>" class="btn-lms btn-sm"
         style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">
        <i class="fas fa-chalkboard-user"></i> Assign Lecturer
      </a>
      <a href="assign_student.php?course_id=<?= $id ?>" class="btn-lms btn-sm"
         style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;">
        <i class="fas fa-user-graduate"></i> Enroll Student
      </a>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div>
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Edit Form -->
    <div class="col-lg-8">
      <form method="POST" action="edit.php?id=<?= $id ?>" id="editCourseForm">
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title"><i class="fas fa-pen-to-square" style="color:#5b4efa;"></i> Course Details</div>
            <span class="section-badge">Required fields marked *</span>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">

              <div class="col-md-8">
                <div class="form-group-lms">
                  <label for="course_name">Course Name <span class="req">*</span></label>
                  <input type="text" id="course_name" name="course_name" class="form-control-lms"
                         value="<?= htmlspecialchars($form['course_name']) ?>" required>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="course_code">Course Code <span class="req">*</span></label>
                  <input type="text" id="course_code" name="course_code" class="form-control-lms"
                         value="<?= htmlspecialchars($form['course_code']) ?>"
                         oninput="this.value=this.value.toUpperCase()" required>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="duration">Duration</label>
                  <input type="text" id="duration" name="duration" class="form-control-lms"
                         value="<?= htmlspecialchars($form['duration']) ?>" placeholder="e.g. 3 Months">
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="monthly_fee">Monthly Fee (Rs.) <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-money-bill-wave" style="color:#10b981;"></i>
                    <input type="number" id="monthly_fee" name="monthly_fee"
                           class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['monthly_fee']) ?>"
                           step="0.01" min="0" required>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="status">Status</label>
                  <select id="status" name="status" class="form-control-lms">
                    <option value="active"   <?= $form['status']==='active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $form['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                  </select>
                </div>
              </div>

              <div class="col-12">
                <div class="form-group-lms">
                  <label for="description">Description</label>
                  <textarea id="description" name="description" class="form-control-lms" rows="3"><?= htmlspecialchars($form['description']) ?></textarea>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary-grad" id="btn-update-course">
            <i class="fas fa-floppy-disk"></i> Update Course
          </button>
          <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-xmark"></i> Cancel</a>
        </div>
      </form>
    </div>

    <!-- Course Students Panel -->
    <div class="col-lg-4">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-users" style="color:#3b82f6;"></i> Enrolled Students
          </div>
          <span class="badge-lms info"><?= count($courseStudents) ?></span>
        </div>
        <div class="card-lms-body" style="padding:0;max-height:400px;overflow-y:auto;">
          <?php if (empty($courseStudents)): ?>
            <div class="empty-state" style="padding:30px 20px;">
              <i class="fas fa-user-slash" style="font-size:28px;"></i>
              <p style="font-size:13px;">No students enrolled yet.</p>
              <a href="assign_student.php?course_id=<?= $id ?>" class="btn-lms btn-primary btn-sm mt-10">
                <i class="fas fa-user-plus"></i> Enroll Student
              </a>
            </div>
          <?php else: ?>
            <?php foreach ($courseStudents as $st): ?>
            <div class="course-student-item">
              <div class="avatar-initials" style="width:28px;height:28px;font-size:11px;background:#5b4efa;flex-shrink:0;">
                <?= strtoupper(substr($st['full_name'], 0, 1)) ?>
              </div>
              <div style="flex:1;min-width:0;">
                <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($st['full_name']) ?></div>
                <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($st['sid']) ?></div>
              </div>
              <?php
                $sc = match($st['status']) {
                    'completed' => '#059669',
                    'dropped'   => '#dc2626',
                    default     => '#3b82f6',
                };
              ?>
              <span style="font-size:10px;font-weight:700;color:<?= $sc ?>;text-transform:uppercase;">
                <?= $st['status'] ?>
              </span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
