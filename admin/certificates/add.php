<?php
// =====================================================
// ISSD Management - Admin: Process Completion
// admin/certificates/add.php
// =====================================================
define('PAGE_TITLE', 'Process Completion');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/certificate_controller.php';

requireRole(ROLE_ADMIN);

$errors = [];
$form = [
    'student_id'         => '',
    'certificate_number' => '',
    'issue_date'         => date('Y-m-d'),
    'is_provided'        => 'no'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';

    $result = addCertificate($pdo, $form, $_FILES['intern_document'] ?? null);
    if ($result['success']) {
        setFlash('success', 'Student successfully marked as completed and certificate recorded.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
}

$students = getEligibleStudentsForCertificate($pdo);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Process Student Completion</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Certificates</a> &rsaquo;
        <span>Process Completion</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div>
        <strong>Please fix the following issues:</strong>
        <ul style="margin:5px 0 0;padding-left:18px;">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="add.php" enctype="multipart/form-data">
    <div class="card-lms" style="max-width:800px;margin:0 auto;">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-user-graduate" style="color:#059669;"></i> Graduate & Certificate Details
        </div>
      </div>
      <div class="card-lms-body">
        
        <div class="alert-lms info mb-20">
          <i class="fas fa-info-circle"></i>
          <div>Submitting this form will permanently mark the student's status and all active enrollments as <strong>Completed</strong>.</div>
        </div>

        <div class="row g-3">
          
          <div class="col-12">
            <div class="form-group-lms">
              <label>Select Student <span class="req">*</span></label>
              <select name="student_id" class="form-control-lms" required>
                <option value="">"" Choose an Eligible Student ""</option>
                <?php foreach ($students as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= $form['student_id']==$s['id']?'selected':'' ?>>
                    <?= htmlspecialchars($s['student_reg'] . ' - ' . $s['full_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Certificate Number <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-hashtag"></i>
                <input type="text" name="certificate_number" class="form-control-lms with-icon" 
                       value="<?= htmlspecialchars($form['certificate_number']) ?>" placeholder="e.g. CERT-2023-001" required>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Issue Date <span class="req">*</span></label>
              <input type="date" name="issue_date" class="form-control-lms" 
                     value="<?= htmlspecialchars($form['issue_date']) ?>" required>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Internship Document <span style="font-size:11px;color:#94a3b8;">(Optional)</span></label>
              <input type="file" name="intern_document" class="form-control-lms" accept=".pdf,.doc,.docx,.jpg,.png">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Is Certificate Provided Initially?</label>
              <select name="is_provided" class="form-control-lms">
                <option value="no"  <?= $form['is_provided']==='no'?'selected':'' ?>>No (Pending Delivery)</option>
                <option value="yes" <?= $form['is_provided']==='yes'?'selected':'' ?>>Yes (Handed over)</option>
              </select>
            </div>
          </div>

        </div>

      </div>
      <div class="card-lms-body" style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 20px;">
        <button type="submit" class="btn-primary-grad">
          <i class="fas fa-check-double"></i> Mark as Completed & Save
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

