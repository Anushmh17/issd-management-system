<?php
// =====================================================
// LEARN Management - Admin: Add Lecturer
// admin/lecturers/add.php
// =====================================================
define('PAGE_TITLE', 'Add Lecturer');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/lecturer_controller.php';

requireRole(ROLE_ADMIN);

$errors  = [];
$warning = '';
$form = [
    'name'           => '',
    'email'          => '',
    'phone'          => '',
    'username'       => '',
    'password'       => '',
    'qualifications' => '',
    'department'     => '',
    'employee_id'    => '',
    'joined_date'    => date('Y-m-d'),
    'status'         => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';
    $result = addLecturer($pdo, $form, $_FILES['photo'] ?? null);
    if ($result['success']) {
        $warning = $result['warning'] ?? '';
        if ($warning) {
            setFlash('warning', 'Lecturer added but photo upload failed: ' . $warning);
        } else {
            setFlash('success', 'Lecturer <strong>' . htmlspecialchars($form['name']) . '</strong> added successfully.');
        }
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
      <h1>Add New Lecturer</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Lecturers</a> &rsaquo;
        <span>Add Lecturer</span>
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

  <form method="POST" action="add.php" enctype="multipart/form-data" id="addLecturerForm">

    <div class="row g-3">

      <!-- Left: Photo Upload -->
      <div class="col-lg-3">
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title"><i class="fas fa-image" style="color:#5b4efa;"></i> Photo</div>
          </div>
          <div class="card-lms-body" style="text-align:center;padding:24px;">
            <div class="lect-photo-preview-wrap" id="photoPreviewWrap">
              <img id="photoPreview" src="<?= BASE_URL ?>/assets/images/avatar-default.png"
                   alt="Preview" class="lect-photo-preview">
            </div>
            <label class="lect-upload-btn" for="photoInput">
              <i class="fas fa-cloud-arrow-up"></i> Upload Photo
            </label>
            <input type="file" id="photoInput" name="photo" accept="image/*"
                   class="d-none" onchange="previewPhoto(this)">
            <p class="text-muted" style="font-size:11px;margin-top:8px;">
              JPG, PNG, WebP · Max 5 MB
            </p>
          </div>
        </div>
      </div>

      <!-- Right: Form Fields -->
      <div class="col-lg-9">

        <!-- Personal Info -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-user" style="color:#5b4efa;"></i> Personal Information
            </div>
            <span class="section-badge">Required fields marked *</span>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">

              <div class="col-md-6">
                <div class="form-group-lms">
                  <label>Full Name <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['name']) ?>"
                           placeholder="e.g. Dr. Nimal Silva" required>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group-lms">
                  <label>Email Address <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['email']) ?>"
                           placeholder="e.g. nimal@learn.com" required>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Phone Number</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="phone" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['phone']) ?>"
                           placeholder="07X XXX XXXX">
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Employee ID</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-id-badge"></i>
                    <input type="text" name="employee_id" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['employee_id']) ?>"
                           placeholder="e.g. LEC-001">
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Department</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-building"></i>
                    <input type="text" name="department" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['department']) ?>"
                           placeholder="e.g. Computer Science">
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Joined Date</label>
                  <input type="date" name="joined_date" class="form-control-lms"
                         value="<?= htmlspecialchars($form['joined_date']) ?>">
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Status</label>
                  <select name="status" class="form-control-lms">
                    <option value="active"   <?= $form['status']==='active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $form['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                  </select>
                </div>
              </div>

              <div class="col-12">
                <div class="form-group-lms">
                  <label>Qualifications</label>
                  <textarea name="qualifications" class="form-control-lms" rows="2"
                            placeholder="e.g. B.Sc.(Hons), M.Sc. Computer Science, PGCE"><?= htmlspecialchars($form['qualifications']) ?></textarea>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Login Credentials -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-lock" style="color:#ef4444;"></i> Login Credentials
            </div>
            <span class="section-badge" style="background:#fee2e2;color:#dc2626;">Secure Access</span>
          </div>
          <div class="card-lms-body">
            <div class="alert-lms info" style="margin-bottom:16px;padding:10px 14px;font-size:13px;">
              <i class="fas fa-info-circle"></i>
              Lecturer can log in using their <strong>email</strong> or <strong>username</strong>.
            </div>
            <div class="row g-3">

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Username <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-at"></i>
                    <input type="text" name="username" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['username']) ?>"
                           placeholder="e.g. nimal_silva" required
                           oninput="this.value=this.value.toLowerCase().replace(/\s/g,'_')">
                  </div>
                  <small class="text-muted" style="font-size:11px;">Must be unique</small>
                </div>
              </div>

              <div class="col-md-8">
                <div class="form-group-lms">
                  <label>Password <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-key"></i>
                    <input type="password" id="lect_password" name="password"
                           class="form-control-lms with-icon"
                           placeholder="Min 6 characters" required>
                    <i class="fas fa-eye toggle-password" data-target="#lect_password" 
                       style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;"></i>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div><!-- /col -->
    </div><!-- /row -->

    <div class="form-actions">
      <button type="submit" class="btn-primary-grad" id="btn-save-lecturer">
        <i class="fas fa-floppy-disk"></i> Save Lecturer
      </button>
      <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-xmark"></i> Cancel</a>
    </div>

  </form>
</div>

<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('photoPreview').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
