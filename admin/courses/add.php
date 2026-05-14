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
    verifyCsrf();
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

<style>
  /* --- PREMIUM BORDER REINFORCEMENT (FORCE APPLIED) --- */
  body.lms-dark-mode .card-lms,
  body.lms-dark-mode .stat-card,
  body.lms-dark-mode .bento-card {
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
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="card-lms mb-20 shadow-sm" style="border-radius: 20px; overflow: hidden;">
      <div class="card-lms-header" style="padding: 25px 30px;">
        <div class="card-lms-title">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon" style="color: var(--primary); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
              <i class="fas fa-book-open"></i>
            </div>
            <div>
              <div style="font-size: 18px; font-weight: 800; color: var(--text-main);">Course Details</div>
              <div style="font-size: 12px; color: var(--text-muted); font-weight: 500;">Fill in the essential information for the new academic program</div>
            </div>
          </div>
        </div>
        <span class="badge-lms primary" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; display: none;">Required fields marked *</span>
      </div>
      <div class="card-lms-body" style="padding: 30px;">
        <div class="row g-4">

          <div class="col-md-8">
            <div class="form-group-lms">
              <label for="course_name" style="text-transform:uppercase; font-size:12px; letter-spacing:0.5px; font-weight:800;">
                Course Name <span class="req">*</span>
              </label>
              <div class="input-icon-wrap">
                <i class="fas fa-heading"></i>
                <input type="text" id="course_name" name="course_name" class="form-control-lms with-icon"
                       placeholder="e.g. Web Development Fundamentals"
                       value="<?= htmlspecialchars($form['course_name']) ?>" required>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="course_code" style="text-transform:uppercase; font-size:12px; letter-spacing:0.5px; font-weight:800;">
                Course Code <span class="req">*</span>
              </label>
              <div class="input-icon-wrap">
                <i class="fas fa-hashtag"></i>
                <input type="text" id="course_code" name="course_code" class="form-control-lms with-icon"
                       placeholder="e.g. WD101"
                       value="<?= htmlspecialchars($form['course_code']) ?>"
                       oninput="this.value=this.value.toUpperCase()" required>
              </div>
              <small class="form-text-hint">Must be unique for the system</small>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="duration" style="text-transform:uppercase; font-size:12px; letter-spacing:0.5px; font-weight:800;">
                Duration (Months) <span class="req">*</span>
              </label>
              <div class="input-icon-wrap">
                <i class="fas fa-calendar-day"></i>
                <input type="number" id="duration" name="duration" class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['duration']) ?>" placeholder="e.g. 3"
                       oninput="if(this.value.length > 2) this.value = this.value.slice(0, 2);"
                       required>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="monthly_fee" style="text-transform:uppercase; font-size:12px; letter-spacing:0.5px; font-weight:800;">
                Monthly Fee (Rs.) <span class="req">*</span>
              </label>
              <div class="input-icon-wrap">
                <i class="fas fa-money-bill-wave"></i>
                <input type="number" id="monthly_fee" name="monthly_fee"
                       class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['monthly_fee']) ?>"
                       placeholder="0.00" step="0.01" min="0" required>
              </div>
              <small class="form-text-hint">Used for automated invoice generation</small>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="status" style="text-transform:uppercase; font-size:12px; letter-spacing:0.5px; font-weight:800;">
                Status
              </label>
              <div class="input-icon-wrap">
                <i class="fas fa-shield-halved"></i>
                <select id="status" name="status" class="form-control-lms with-icon select2-search">
                  <option value="active"   <?= $form['status']==='active'   ? 'selected' : '' ?>>Active</option>
                  <option value="inactive" <?= $form['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group-lms">
              <label for="description" style="text-transform:uppercase; font-size:12px; letter-spacing:0.5px; font-weight:800;">
                Course Description
              </label>
              <textarea id="description" name="description" class="form-control-lms" rows="4"
                        placeholder="Briefly describe what students will learn in this course..."><?= htmlspecialchars($form['description']) ?></textarea>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="form-actions" style="display: flex; gap: 15px; margin-top: 30px; padding: 0 5px;">
      <button type="submit" class="btn-primary-grad" id="btn-save-course" style="padding: 14px 35px; border-radius: 14px; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-shadow);">
        <i class="fas fa-floppy-disk me-2"></i> Save Course
      </button>
      <a href="index.php" class="btn-lms btn-outline" style="padding: 14px 25px; border-radius: 14px; font-weight: 700;">
        <i class="fas fa-xmark me-2"></i> Cancel
      </a>
    </div>
  </form>
</div>

<script>
$(document).ready(function() {
    $('.select2-search').select2({ width: '100%' });
});
</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>