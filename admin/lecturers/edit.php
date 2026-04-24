<?php
// =====================================================
// LEARN Management - Admin: Edit Lecturer
// admin/lecturers/edit.php
// =====================================================
define('PAGE_TITLE', 'Edit Lecturer');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/lecturer_controller.php';

requireRole(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger','Invalid lecturer.'); header('Location: index.php'); exit; }

$lecturer = getLecturerById($pdo, $id);
if (!$lecturer) { setFlash('danger','Lecturer not found.'); header('Location: index.php'); exit; }

$errors = [];
$form = [
    'name'           => $lecturer['name'],
    'email'          => $lecturer['email'],
    'phone'          => $lecturer['phone'] ?? '',
    'username'       => $lecturer['username'],
    'new_password'   => '',
    'qualifications' => $lecturer['qualifications'] ?? '',
    'department'     => $lecturer['department'] ?? '',
    'employee_id'    => $lecturer['employee_id'] ?? '',
    'joined_date'    => $lecturer['joined_date'] ?? '',
    'status'         => $lecturer['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';
    $result = updateLecturer($pdo, $id, $form, $_FILES['photo'] ?? null);
    if ($result['success']) {
        setFlash('success', 'Lecturer <strong>' . htmlspecialchars($form['name']) . '</strong> updated successfully.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
    // Reload from DB on error to get fresh data
    $lecturer = getLecturerById($pdo, $id);
}

$photoUrl = lecturerPhotoUrl($lecturer['photo']);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Edit Lecturer</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Lecturers</a> &rsaquo;
        <span>Edit: <?= htmlspecialchars($lecturer['name']) ?></span>
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

  <form method="POST" action="edit.php?id=<?= $id ?>" enctype="multipart/form-data" id="editLecturerForm">

    <div class="row g-3">

      <!-- Left: Photo + Assigned Courses -->
      <div class="col-lg-3">

        <!-- Photo Card -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title"><i class="fas fa-image" style="color:#5b4efa;"></i> Photo</div>
          </div>
          <div class="card-lms-body" style="text-align:center;padding:24px;">
            <div class="lect-photo-preview-wrap" id="photoPreviewWrap">
              <img id="photoPreview" src="<?= htmlspecialchars($photoUrl) ?>"
                   alt="<?= htmlspecialchars($lecturer['name']) ?>"
                   class="lect-photo-preview"
                   onerror="this.src='<?= BASE_URL ?>/assets/images/avatar-default.png'">
            </div>
            <label class="lect-upload-btn" for="photoInput">
              <i class="fas fa-camera"></i> Change Photo
            </label>
            <input type="file" id="photoInput" name="photo" accept="image/*"
                   class="d-none" onchange="previewPhoto(this)">
            <p class="text-muted" style="font-size:11px;margin-top:8px;">
              JPG, PNG, WebP · Max 5 MB
            </p>
          </div>
        </div>

        <!-- Assigned Courses Card -->
        <div class="card-lms">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-book-open" style="color:#3b82f6;"></i> Assigned Courses
            </div>
            <span class="badge-lms info"><?= $lecturer['course_count'] ?></span>
          </div>
          <div class="card-lms-body" style="padding:0;max-height:320px;overflow-y:auto;">
            <?php if (empty($lecturer['courses'])): ?>
              <div class="empty-state" style="padding:24px 16px;">
                <i class="fas fa-book-open" style="font-size:24px;"></i>
                <p style="font-size:12px;">No courses assigned yet.</p>
                <a href="<?= BASE_URL ?>/admin/courses/assign_lecturer.php"
                   class="btn-lms btn-primary btn-sm mt-10" style="font-size:11px;">
                  <i class="fas fa-link"></i> Assign Course
                </a>
              </div>
            <?php else: ?>
              <?php foreach ($lecturer['courses'] as $c): ?>
              <div class="course-student-item">
                <div class="course-icon-box" style="width:30px;height:30px;font-size:12px;flex-shrink:0;">
                  <i class="fas fa-book-open"></i>
                </div>
                <div style="flex:1;min-width:0;">
                  <div class="fw-600" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($c['course_name']) ?>
                  </div>
                  <span class="course-code-badge" style="font-size:9px;"><?= htmlspecialchars($c['course_code']) ?></span>
                </div>
                <?php if ($c['status'] === 'active'): ?>
                  <span style="font-size:9px;font-weight:700;color:#059669;">●</span>
                <?php else: ?>
                  <span style="font-size:9px;font-weight:700;color:#94a3b8;">●</span>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Right: Edit Fields -->
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
                           value="<?= htmlspecialchars($form['name']) ?>" required>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group-lms">
                  <label>Email Address <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['email']) ?>" required>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Phone Number</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="phone" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['phone']) ?>" placeholder="07X XXX XXXX">
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Employee ID</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-id-badge"></i>
                    <input type="text" name="employee_id" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['employee_id']) ?>">
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group-lms">
                  <label>Department</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-building"></i>
                    <input type="text" name="department" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['department']) ?>">
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
                  <textarea name="qualifications" class="form-control-lms" rows="2"><?= htmlspecialchars($form['qualifications']) ?></textarea>
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
            <span class="section-badge" style="background:#fee2e2;color:#dc2626;">Leave password blank to keep unchanged</span>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">

              <div class="col-md-5">
                <div class="form-group-lms">
                  <label>Username <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-at"></i>
                    <input type="text" name="username" class="form-control-lms with-icon"
                           value="<?= htmlspecialchars($form['username']) ?>" required
                           oninput="this.value=this.value.toLowerCase().replace(/\s/g,'_')">
                  </div>
                </div>
              </div>

              <div class="col-md-7">
                <div class="form-group-lms">
                  <label>New Password <span style="font-size:11px;color:#94a3b8;">(optional)</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-key"></i>
                    <input type="password" id="lect_new_password" name="new_password"
                           class="form-control-lms with-icon"
                           placeholder="Leave blank to keep current password">
                    <button type="button" class="pwd-toggle" onclick="togglePwd('lect_new_password',this)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary-grad" id="btn-update-lecturer">
            <i class="fas fa-floppy-disk"></i> Update Lecturer
          </button>
          <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-xmark"></i> Cancel</a>
        </div>

      </div><!-- /col -->
    </div><!-- /row -->

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
function togglePwd(fieldId, btn) {
  const f = document.getElementById(fieldId);
  const i = btn.querySelector('i');
  if (f.type === 'password') {
    f.type = 'text';
    i.className = 'fas fa-eye-slash';
  } else {
    f.type = 'password';
    i.className = 'fas fa-eye';
  }
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
