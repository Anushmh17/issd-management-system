<?php
// =====================================================
// ISSD Management - Lecturer: Add Assignment
// frontend/lecturer/assignments/add.php
// =====================================================
define('PAGE_TITLE', 'Add Assignment');
require_once dirname(__DIR__, 3) . '/backend/config.php';
require_once dirname(__DIR__, 3) . '/backend/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/backend/assignment_controller.php';

requireRole(ROLE_LECTURER);

$user = currentUser();
$lecturerId = $_SESSION['lecturer_id'] ?? $user['id'];
$courses = getLecturerCourses($pdo, $lecturerId);

$errors = [];
$form = [
    'course_id'   => '',
    'title'       => '',
    'description' => '',
    'due_date'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';

    $result = addAssignment($pdo, $lecturerId, $form, $_FILES['file'] ?? null);
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
    <div class="glass-card" style="max-width:900px; margin:0 auto;">
      <div class="glass-card-title">
        <span style="display:flex; align-items:center; gap:12px;">
          <i class="fas fa-file-signature" style="color:var(--primary);"></i> 
          New Assignment Details
        </span>
      </div>
      
      <div class="card-lms-body">
        
        <div class="row g-4">
          
          <div class="col-md-6">
            <div class="form-group-lms">
              <label class="premium-label"><i class="fas fa-book"></i> Select Course <span class="req">*</span></label>
              <select name="course_id" class="form-control-lms" required>
                <option value="">-- Choose a Course --</option>
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
              <label class="premium-label"><i class="fas fa-pen-to-square"></i> Assignment Title <span class="req">*</span></label>
              <input type="text" name="title" class="form-control-lms"
                     value="<?= htmlspecialchars($form['title']) ?>" placeholder="e.g. Midterm Report" required>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group-lms">
              <label class="premium-label"><i class="fas fa-calendar-alt"></i> Due Date (Date & Time) <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-calendar-day" id="calendar-icon-trigger" style="cursor:pointer; pointer-events:auto;"></i>
                <input type="text" name="due_date" id="due_date_picker" class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['due_date']) ?>" placeholder="Select deadline..." required>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group-lms">
              <label class="premium-label"><i class="fas fa-paperclip"></i> Attachment <span style="font-size:10px; opacity:0.5; margin-left:5px;">(OPTIONAL)</span></label>
              <div class="custom-file-upload">
                <input type="file" name="file" id="file_upload" accept=".pdf,.doc,.docx,.zip,.rar">
                <label for="file_upload"><i class="fas fa-cloud-upload-alt"></i> <span>Choose a file...</span></label>
              </div>
              <small class="form-text-hint">Allowable: PDF, DOCX, ZIP, RAR (Max: 15MB)</small>
            </div>
          </div>
          
          <div class="col-12">
            <div class="form-group-lms">
              <label class="premium-label"><i class="fas fa-align-left"></i> Description / Instructions</label>
              <textarea name="description" class="form-control-lms" rows="5" placeholder="Provide detailed instructions for the students..."><?= htmlspecialchars($form['description']) ?></textarea>
            </div>
          </div>
        </div>

      </div>
      <div class="card-lms-footer">
        <button type="submit" class="btn-primary-grad">
          <i class="fas fa-floppy-disk"></i> Publish Assignment
        </button>
        <a href="index.php" class="btn-lms btn-outline">Cancel</a>
      </div>
    </div>
  </form>
  
<?php
ob_start();
?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // File upload visual feedback
    const fileInput = document.getElementById('file_upload');
    const fileLabel = fileInput.nextElementSibling ? fileInput.nextElementSibling.querySelector('span') : null;
    
    if (fileInput && fileLabel) {
      fileInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
          fileLabel.textContent = this.files[0].name;
          fileLabel.style.color = 'var(--primary)';
        } else {
          fileLabel.textContent = 'Choose a file...';
          fileLabel.style.color = '';
        }
      });
    }

    // Initialize Flatpickr
    if (typeof flatpickr !== 'undefined') {
      const fp = flatpickr("#due_date_picker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "F j, Y - h:i K",
        minDate: "today",
        plugins: [
          new confirmDatePlugin({
            confirmText: "OK",
            showAlways: true,
            theme: "dark"
          })
        ]
      });

      // Make icon clickable
      const trigger = document.getElementById('calendar-icon-trigger');
      if (trigger) {
        trigger.addEventListener('click', function() {
          fp.open();
        });
      }
    }
  });
</script>
<?php
$extraJS = ob_get_clean();
require_once dirname(__DIR__, 3) . '/includes/footer.php';
?>

