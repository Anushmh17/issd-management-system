<?php
// =====================================================
// LEARN Management - Lecturer: Add Assignment
// frontend/lecturer/assignments/add.php
// =====================================================
define('PAGE_TITLE', 'Add Assignment');
require_once dirname(__DIR__, 3) . '/backend/config.php';
require_once dirname(__DIR__, 3) . '/backend/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/backend/assignment_controller.php';

requireRole(ROLE_LECTURER);

$user = currentUser();
$courses = getLecturerCourses($pdo, $user['id']);

$errors = [];
$form = [
    'course_id'   => '',
    'title'       => '',
    'description' => '',
    'deadline'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';

    $result = addAssignment($pdo, $user['id'], $form, $_FILES['file'] ?? null);
    if ($result['success']) {
        setFlash('success', 'Assignment added successfully.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Add Assignment</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Lecturer &rsaquo;
        <a href="index.php" style="color:inherit;">Assignments</a> &rsaquo;
        <span>Add</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div>
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="add.php" enctype="multipart/form-data">

    <div class="card-lms" style="max-width:800px;margin:0 auto;">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-file-signature" style="color:#3b82f6;"></i> Assignment Details
        </div>
        <span class="section-badge">Required fields marked *</span>
      </div>
      <div class="card-lms-body">
        
        <div class="row g-3">
          
          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Select Course <span class="req">*</span></label>
              <select name="course_id" class="form-control-lms" required>
                <option value="">— Choose a Course —</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $form['course_id']==$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Assignment Title <span class="req">*</span></label>
              <input type="text" name="title" class="form-control-lms"
                     value="<?= htmlspecialchars($form['title']) ?>" placeholder="e.g. Midterm Report" required>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Deadline (Date & Time) <span class="req">*</span></label>
              <input type="datetime-local" name="deadline" class="form-control-lms"
                     value="<?= htmlspecialchars($form['deadline']) ?>" required>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Attachment <span style="font-size:11px;color:#94a3b8;">(Optional)</span></label>
              <input type="file" name="file" class="form-control-lms" accept=".pdf,.doc,.docx,.zip,.rar">
              <small style="font-size:11px;color:#94a3b8;">Allowable: PDF, DOCX, ZIP, RAR (Max: 15MB)</small>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group-lms">
              <label>Description / Instructions</label>
              <textarea name="description" class="form-control-lms" rows="4"><?= htmlspecialchars($form['description']) ?></textarea>
            </div>
          </div>

        </div>

      </div>
      <div class="card-lms-body" style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 20px;">
        <button type="submit" class="btn-primary-grad">
          <i class="fas fa-floppy-disk"></i> Save Assignment
        </button>
      </div>
    </div>

  </form>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
