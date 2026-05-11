<?php
// =====================================================
// ISSD Management - Admin: Add Lecturer
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
    'name'            => '',
    'email'           => '',
    'phone'           => '',
    'username'        => '',
    'password'        => '',
    'qualifications'  => '',
    'department'      => '',
    'employee_id'     => '',
    'joined_date'     => date('Y-m-d'),
    'status'          => 'active',
    'course_id'       => '',
    'payment_mode'    => 'flat_monthly',
    'per_student_rate'=> '',
];

// Load active courses for assignment dropdown
$availableCourses = $pdo->query("SELECT id, course_name, course_code, monthly_fee FROM courses WHERE status='active' ORDER BY course_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
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

<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
/* Cropping Modal */
.crop-modal {
  display: none; position: fixed; z-index: 10000;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(15,23,42,0.9); backdrop-filter: blur(5px);
  align-items: center; justify-content: center;
}
.crop-modal-content {
  background: #fff; width: 90%; max-width: 500px;
  border-radius: 20px; padding: 24px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}
.crop-container { width:100%; height:400px; background:#f1f5f9; margin:15px 0; border-radius:12px; overflow:hidden; }
.crop-actions { display:flex; gap:12px; justify-content:flex-end; }

/* Eye Icon */
.pwd-toggle-icon { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; transition:0.2s; z-index:10; }
.pwd-toggle-icon:hover { color:var(--primary); }

/* Profile Banner inside card */
.profile-banner { text-align:center; padding-bottom:20px; }
.profile-banner-bg {
  height:120px;
  background: linear-gradient(135deg, var(--primary) 0%, #2d8f6f 100%);
  border-radius:20px 20px 0 0; position:relative; overflow:hidden;
}
.profile-banner-bg::after {
  content:''; position:absolute; inset:0;
  background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Ccircle cx='20' cy='20' r='10'/%3E%3C/g%3E%3C/svg%3E");
}
.profile-photo-center { margin-top:-55px; position:relative; z-index:2; }
.photo-upload-ring {
  width:110px; height:110px; border-radius:50%;
  border:4px solid #fff; overflow:hidden;
  margin:0 auto 8px; position:relative; cursor:pointer;
  box-shadow:0 8px 25px rgba(30,77,77,0.25); transition:transform 0.2s;
}
.photo-upload-ring:hover { transform:scale(1.04); }
.photo-upload-ring:hover .photo-upload-overlay { opacity:1; }
.photo-ring-img { width:100%; height:100%; object-fit:cover; display:block; }
.photo-upload-overlay {
  position:absolute; inset:0;
  background:rgba(20,60,50,0.72);
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  color:#fff; font-size:11px; font-weight:700; opacity:0; transition:opacity 0.2s; gap:3px;
}
.photo-upload-overlay i { font-size:18px; }
.photo-hint { font-size:11px; color:#94a3b8; margin:4px 0 0; }

/* Payment Mode Cards */
.payment-mode-option { display:flex; align-items:flex-start; gap:12px; cursor:pointer; margin:0; }
.payment-mode-option input[type="radio"] { margin-top:4px; accent-color:var(--primary); width:16px; height:16px; flex-shrink:0; }
.payment-mode-card { flex:1; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:12px; background:#f8fafc; transition:all 0.2s; }
.payment-mode-option:has(input:checked) .payment-mode-card { border-color:var(--primary); background:#f0fdf9; box-shadow:0 2px 8px rgba(30,77,77,0.08); }
</style>

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

  <form method="POST" action="add.php" id="addLecturerForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <!-- Card 1: Personal Information with integrated photo banner -->
    <div class="card-lms mb-20">

      <!-- Profile Banner -->
      <div class="profile-banner">
        <div class="profile-banner-bg"></div>
        <div class="profile-photo-center">
          <div class="photo-upload-ring" onclick="document.getElementById('photoInput').click()" title="Click to upload photo">
            <img id="photoPreview" src="<?= BASE_URL ?>/assets/images/avatar-default.png" alt="Preview" class="photo-ring-img">
            <div class="photo-upload-overlay">
              <i class="fas fa-camera"></i>
              <span>Change</span>
            </div>
          </div>
          <input type="file" id="photoInput" name="photo" accept="image/*" class="d-none" onchange="previewPhoto(this)">
          <p class="photo-hint">JPG, PNG, WebP · Max 5 MB</p>
        </div>
      </div>

      <div class="card-lms-header" style="border-top:1px solid #f1f5f9;">
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
                       placeholder="e.g. nimal@issd.com" required>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Phone Number</label>
              <div class="phone-input-group">
                <span class="phone-prefix">+94</span>
                <input type="tel" name="phone" value="<?= htmlspecialchars(stripSriLankanCountryCode($form['phone'])) ?>"
                       placeholder="7XXXXXXXX" maxlength="9" pattern="[0-9]{9}"
                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.startsWith('0')) this.value = this.value.substring(1);">
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
              <select name="status" class="form-control-lms select2-search">
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

    <!-- Card 2: Login Credentials -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-lock" style="color:#ef4444;"></i> Login Credentials
        </div>
        <span class="section-badge badge-secure">Secure Access</span>
      </div>
      <div class="card-lms-body">
        <div class="alert-lms info">
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
                <i class="fas fa-eye pwd-toggle-icon" onclick="togglePwd('lect_password', this)"></i>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Course Assignment -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-graduation-cap" style="color:#059669;"></i> Course Assignment
        </div>
        <span class="section-badge badge-optional">Optional</span>
      </div>
      <div class="card-lms-body">
        <div class="row g-3">
          <div class="col-md-8">
            <div class="form-group-lms">
              <label>Assign to Course</label>
              <select name="course_id" class="form-control-lms select2-search" id="courseSelect">
                <option value="">— No course assignment yet —</option>
                <?php foreach ($availableCourses as $c): ?>
                  <option value="<?= $c['id'] ?>" data-fee="<?= $c['monthly_fee'] ?>"
                    <?= $form['course_id'] == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['course_name']) ?> (<?= htmlspecialchars($c['course_code']) ?>) — Rs. <?= number_format($c['monthly_fee'], 2) ?>/mo
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted" style="font-size:11px;">Lecturer will be assigned as the primary instructor for this course.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Settings -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-hand-holding-dollar" style="color:#f59e0b;"></i> Payment Settings
        </div>
        <span class="section-badge badge-payroll">Payroll Config</span>
      </div>
      <div class="card-lms-body">
        <div class="row g-3 align-items-start">

          <div class="col-md-5">
            <label class="form-label fw-700" style="font-size:13px;color:#374151;margin-bottom:12px;">Payment Mode</label>
            <div class="d-flex flex-column gap-3">

              <label class="payment-mode-option" id="lbl-flat">
                <input type="radio" name="payment_mode" value="flat_monthly"
                  <?= $form['payment_mode'] === 'flat_monthly' ? 'checked' : '' ?>
                  onchange="togglePaymentMode()">
                <div class="payment-mode-card">
                  <div style="font-weight:700;font-size:14px;"><i class="fas fa-calendar-check me-2" style="color:#5b4efa;"></i>Flat Monthly</div>
                  <div style="font-size:12px;color:#64748b;margin-top:3px;">Fixed amount regardless of student count</div>
                </div>
              </label>

              <label class="payment-mode-option" id="lbl-per">
                <input type="radio" name="payment_mode" value="per_student"
                  <?= $form['payment_mode'] === 'per_student' ? 'checked' : '' ?>
                  onchange="togglePaymentMode()">
                <div class="payment-mode-card">
                  <div style="font-weight:700;font-size:14px;"><i class="fas fa-users me-2" style="color:#059669;"></i>Per Student</div>
                  <div style="font-size:12px;color:#64748b;margin-top:3px;">Amount × number of enrolled students</div>
                </div>
              </label>

            </div>
          </div>

          <div class="col-md-4" id="perStudentRateWrap" style="display:<?= $form['payment_mode']==='per_student' ? 'block' : 'none' ?>">
            <div class="form-group-lms">
              <label>Rate per Student (Rs.) <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-rupee-sign"></i>
                <input type="number" name="per_student_rate" id="perStudentRate"
                       class="form-control-lms with-icon" min="0" step="0.01"
                       placeholder="e.g. 500.00"
                       value="<?= htmlspecialchars($form['per_student_rate']) ?>">
              </div>
              <small class="text-muted" style="font-size:11px;">Amount paid per enrolled student per month</small>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-primary-grad" id="btn-save-lecturer">
        <i class="fas fa-floppy-disk"></i> Save Lecturer
      </button>
      <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-xmark"></i> Cancel</a>
    </div>

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
    // Deprecated in favor of inline oninput validation
}

document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() { restrictPhone(this); });
    }
    $('.select2-search').select2({ width: '100%' });
});

let cropper = null;
let originalFileName = "";

function previewPhoto(input) {
  if (input.files && input.files[0]) {
    originalFileName = input.files[0].name;
    const reader = new FileReader();
    reader.onload = e => {
      const modal = document.getElementById('cropModal');
      const cropImg = document.getElementById('cropImage');
      cropImg.src = e.target.result;
      modal.style.display = 'flex';
      
      if (cropper) cropper.destroy();
      cropper = new Cropper(cropImg, {
        aspectRatio: 1,
        viewMode: 2,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
      });
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function closeCropModal() {
  document.getElementById('cropModal').style.display = 'none';
  document.getElementById('photoInput').value = ''; // Reset input
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

function togglePaymentMode() {
  const mode = document.querySelector('input[name="payment_mode"]:checked')?.value;
  const wrap = document.getElementById('perStudentRateWrap');
  const input = document.getElementById('perStudentRate');
  if (mode === 'per_student') {
    wrap.style.display = 'block';
    input.required = true;
  } else {
    wrap.style.display = 'none';
    input.required = false;
    input.value = '';
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


