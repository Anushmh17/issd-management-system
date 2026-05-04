<?php
// =====================================================
// ISSD Management - Admin: Edit Lecturer
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

<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
/* Cropping Modal */
.crop-modal {
  display: none;
  position: fixed;
  z-index: 10000;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.9);
  backdrop-filter: blur(5px);
  align-items: center;
  justify-content: center;
}
.crop-modal-content {
  background: #fff;
  width: 90%;
  max-width: 500px;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}
.crop-container {
  width: 100%;
  height: 400px;
  background: #f1f5f9;
  margin: 15px 0;
  border-radius: 12px;
  overflow: hidden;
}
.crop-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Eye Icon Fix */
.pwd-toggle-icon {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #94a3b8;
  transition: 0.2s;
  z-index: 10;
}
.pwd-toggle-icon:hover { color: var(--primary); }
</style>

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
              JPG, PNG, WebP Â· Max 5 MB
            </p>
          </div>
        </div>

        <!-- Assigned Courses Card -->
        <div class="card-lms premium-border">
          <div class="card-lms-header" style="background: var(--primary-light);">
            <div class="card-lms-title">
              <i class="fas fa-book-open" style="color: var(--primary);"></i> Assigned Courses
            </div>
            <div class="premium-badge-count"><?= $lecturer['course_count'] ?></div>
          </div>
          <div class="card-lms-body" style="padding:0;max-height:380px;overflow-y:auto;">
            <?php if (empty($lecturer['courses'])): ?>
              <div class="empty-state" style="padding:40px 20px;">
                <div class="avatar-initials mb-3" style="background: var(--border-light); color: var(--text-muted); width:60px; height:60px;">
                  <i class="fas fa-book-slash" style="font-size:24px;"></i>
                </div>
                <h4 class="fw-700" style="font-size:15px; color: var(--text-main);">No Courses Yet</h4>
                <p style="font-size:12px; color: var(--text-muted); margin-bottom:15px;">This lecturer hasn't been assigned to any courses.</p>
                <a href="<?= BASE_URL ?>/admin/courses/assign_lecturer.php?lecturer_id=<?= $id ?>"
                   class="btn-primary-grad btn-sm" style="font-size:11px;">
                  <i class="fas fa-link"></i> Assign First Course
                </a>
              </div>
            <?php else: ?>
              <div class="course-list-wrap">
                <?php foreach ($lecturer['courses'] as $c): ?>
                <div class="course-student-item">
                  <div class="course-icon-box" style="width:34px; height:34px; font-size:13px;">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div class="fw-700" style="font-size:13px; color: var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                      <?= htmlspecialchars($c['course_name']) ?>
                    </div>
                    <div class="d-flex align-center gap-2 mt-1">
                        <span class="course-code-badge" style="font-size:9px; padding:2px 6px;"><?= htmlspecialchars($c['course_code']) ?></span>
                        <?php if ($c['status'] === 'active'): ?>
                          <span class="badge-lms success" style="font-size:8px; padding:2px 6px; border-radius:4px;">Active</span>
                        <?php endif; ?>
                    </div>
                  </div>
                  <a href="<?= BASE_URL ?>/admin/courses/edit.php?id=<?= $c['id'] ?>" class="text-muted hover-primary" style="font-size:14px;" title="View Course">
                    <i class="fas fa-chevron-right"></i>
                  </a>
                </div>
                <?php endforeach; ?>
              </div>
              <div style="padding:16px; background: #fff;">
                <a href="<?= BASE_URL ?>/admin/courses/assign_lecturer.php?lecturer_id=<?= $id ?>"
                   class="btn-primary-grad btn-sm w-100" style="font-size:11px; justify-content:center; border-radius:10px; padding:12px;">
                  <i class="fas fa-plus-circle"></i> Assign Another Course
                </a>
              </div>
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
                    <i class="fas fa-eye pwd-toggle-icon" onclick="togglePwd('lect_new_password', this)"></i>
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

<!-- Cropping Modal -->
<div class="crop-modal" id="cropModal">
  <div class="crop-modal-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
      <h3 style="margin:0; font-size:18px; font-weight:700;">Crop Profile Picture</h3>
      <button type="button" onclick="closeCropModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:#94a3b8;"><i class="fas fa-times"></i></button>
    </div>
    <div class="crop-container">
      <img id="cropImage" src="" style="max-width: 100%;">
    </div>
    <div class="crop-actions">
      <button type="button" class="btn-lms btn-outline" onclick="closeCropModal()">Cancel</button>
      <button type="button" class="btn-lms btn-primary" onclick="applyCrop()">Apply Crop</button>
    </div>
  </div>
</div>

<script>
function restrictPhone(e) {
    let val = e.value;
    val = val.replace(/[^0-9+]/g, '');
    if (val.indexOf('+') > 0) {
        val = val.substring(0, 1) + val.substring(1).replace(/\+/g, '');
    }
    if (val.startsWith('0')) {
        if (val.length > 10) val = val.substring(0, 10);
    } else if (val.startsWith('+94')) {
        if (val.length > 12) val = val.substring(0, 12);
    } else if (val.startsWith('94')) {
        if (val.length > 11) val = val.substring(0, 11);
    }
    e.value = val;
}

document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() { restrictPhone(this); });
    }
});

let cropper = null;
let originalFileName = "";

function handlePhotoSelect(input) {
  if (input.files && input.files[0]) {
    originalFileName = input.files[0].name;
    const reader = new FileReader();
    reader.onload = function(e) {
      const modal = document.getElementById('cropModal');
      const cropImg = document.getElementById('cropImage');
      if (cropper) cropper.destroy();
      cropImg.src = e.target.result;
      modal.style.display = 'flex';
      cropImg.onload = function() {
        cropper = new Cropper(cropImg, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 0.8,
        });
      };
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function closeCropModal() {
  document.getElementById('cropModal').style.display = 'none';
  document.getElementById('photoInput').value = '';
  if (cropper) cropper.destroy();
}

function applyCrop() {
  if (!cropper) return;
  
  const canvas = cropper.getCroppedCanvas({
    width: 400,
    height: 400
  });
  
  canvas.toBlob(blob => {
    // Create a new File object from the blob
    const file = new File([blob], originalFileName, { type: 'image/jpeg' });
    
    // Create a DataTransfer to set the input files
    const container = new DataTransfer();
    container.items.add(file);
    document.getElementById('photoInput').files = container.files;
    
    // Update preview
    document.getElementById('photoPreview').src = canvas.toDataURL('image/jpeg');
    
    // Close modal
    document.getElementById('cropModal').style.display = 'none';
    if (cropper) cropper.destroy();
  }, 'image/jpeg');
}

function togglePwd(fieldId, icon) {
  const f = document.getElementById(fieldId);
  if (f.type === 'password') {
    f.type = 'text';
    icon.className = 'fas fa-eye-slash pwd-toggle-icon';
  } else {
    f.type = 'password';
    icon.className = 'fas fa-eye pwd-toggle-icon';
  }
}
</script>

<?php
$extraJS = <<<'JS'
<script>
$(document).ready(function() {
    flatpickr("input[name='joined_date']", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        altInput: true,
        altFormat: "F j, Y"
    });
});
</script>
JS;
require_once dirname(__DIR__, 2) . '/includes/footer.php'; 
?>

