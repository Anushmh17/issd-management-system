<?php
// =====================================================
// ISSD Management - Admin: Add Course
// admin/courses/add.php
// =====================================================
define('PAGE_TITLE', 'Add Course');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

$errors = [];
$form = [
    'course_name'  => '',
    'course_code'  => '',
    'duration'     => '',
    'monthly_fee'  => '',
    'description'  => '',
    'status'       => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';
    $result = addCourse($pdo, $form);
    if ($result['success']) {
        setFlash('success', 'Course <strong>' . htmlspecialchars($form['course_name']) . '</strong> created successfully.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Add New Course</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Courses</a> &rsaquo;
        <span>Add Course</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div><strong>Please fix the following:</strong>
        <ul style="margin:6px 0 0;padding-left:18px;">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="add.php" id="addCourseForm">
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title"><i class="fas fa-book-open" style="color:#5b4efa;"></i> Course Details</div>
        <span class="section-badge">Required fields marked *</span>
      </div>
      <div class="card-lms-body">
        <div class="row g-3">

          <div class="col-md-6">
            <div class="form-group-lms">
              <label for="course_name">Course Name <span class="req">*</span></label>
              <input type="text" id="course_name" name="course_name" class="form-control-lms"
                     value="<?= htmlspecialchars($form['course_name']) ?>"
                     placeholder="e.g. Web Development Fundamentals" required>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group-lms">
              <label for="course_code">Course Code <span class="req">*</span></label>
              <input type="text" id="course_code" name="course_code" class="form-control-lms"
                     value="<?= htmlspecialchars($form['course_code']) ?>"
                     placeholder="e.g. WD101"
                     oninput="this.value=this.value.toUpperCase()" required>
              <small style="font-size:11px;color:#94a3b8;">Must be unique</small>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group-lms">
              <label for="duration">Duration</label>
              <input type="text" id="duration" name="duration" class="form-control-lms"
                     value="<?= htmlspecialchars($form['duration']) ?>"
                     placeholder="e.g. 3 Months">
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
                       placeholder="0.00" step="0.01" min="0" required>
              </div>
              <small style="font-size:11px;color:#94a3b8;">Used for payment calculations</small>
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
              <textarea id="description" name="description" class="form-control-lms" rows="3"
                        placeholder="Brief course description..."><?= htmlspecialchars($form['description']) ?></textarea>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-primary-grad" id="btn-save-course">
        <i class="fas fa-floppy-disk"></i> Save Course
      </button>
      <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

