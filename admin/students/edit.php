<?php
// =====================================================
// LEARN Management - Admin: Edit Student
// admin/students/edit.php
// =====================================================
define('PAGE_TITLE', 'Edit Student');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/student_controller.php';
require_once dirname(__DIR__, 2) . '/backend/document_controller.php';

requireRole(ROLE_ADMIN);

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('danger', 'Invalid student ID.');
    header('Location: index.php');
    exit;
}

$student = getStudentById($pdo, $id);
if (!$student) {
    setFlash('danger', 'Student not found.');
    header('Location: index.php');
    exit;
}

$errors = [];

// Pre-fill form from DB record
$form = [
    'full_name'             => $student['full_name'],
    'nic_number'            => $student['nic_number'],
    'batch_number'          => $student['batch_number'],
    'join_date'             => $student['join_date'] ?? '',
    'office_email'          => $student['office_email'] ?? '',
    'office_email_password' => $student['office_email_password'] ?? '',
    'personal_email'        => $student['personal_email'] ?? '',
    'phone_number'          => $student['phone_number'],
    'whatsapp_number'       => $student['whatsapp_number'] ?? '',
    'guardian_name'         => $student['guardian_name'] ?? '',
    'guardian_phone'        => $student['guardian_phone'] ?? '',
    'guardian_verified'     => $student['guardian_verified'] ?? '0',
    'house_address'         => $student['house_address'] ?? '',
    'boarding_address'      => $student['boarding_address'] ?? '',
    'next_follow_up'        => $student['next_follow_up'] ?? '',
    'follow_up_note'        => $student['follow_up_note'] ?? '',
    'status'                => $student['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Merge posted data
    foreach ($form as $key => $_) {
        $form[$key] = $_POST[$key] ?? '';
    }

    // --- Handle Profile Picture Update ---
    if (!empty($_FILES['profile_picture']['name'])) {
        $upload = uploadDocumentFile($_FILES['profile_picture'], 'profile', $id);
        if ($upload['success']) {
            $form['profile_picture'] = $upload['path'];
        } else {
            $errors[] = "Profile Picture Error: " . $upload['error'];
        }
    } else {
        // Keep existing if no new one uploaded
        $form['profile_picture'] = $student['profile_picture'];
    }

    if (!$errors) {
        $result = updateStudent($pdo, $id, $form);

        if ($result['success']) {
            setFlash('success', 'Student <strong>' . htmlspecialchars($student['full_name']) . '</strong> updated successfully.');
            header('Location: index.php');
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

function hasError(array $errors, string $keyword): bool {
    foreach ($errors as $e) {
        if (stripos($e, $keyword) !== false) return true;
    }
    return false;
}

function studentAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]??'A') % count($colors)];
}

function renderStatusBadge(string $status): string {
    $class = 'status-' . $status;
    $label = str_replace('_', ' ', ucfirst($status));
    return '<span class="badge-lms ' . $class . '" style="font-size:11px; padding:4px 10px;"><i class="fas fa-circle-dot" style="margin-right:5px; font-size:8px;"></i>' . $label . '</span>';
}

$extraCSS = <<<'CSS'
<style>
.edit-student-banner {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1.5px solid var(--border-color);
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
    margin-bottom: 25px;
}
.edit-student-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 6px; height: 100%;
    background: linear-gradient(to bottom, var(--primary), var(--accent));
}
.esb-avatar {
    width: 80px; height: 80px; border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 800; color: #fff;
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    flex-shrink: 0;
}
.esb-info { flex-grow: 1; }
.esb-name { font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 4px; }
.esb-meta { display: flex; gap: 20px; flex-wrap: wrap; }
.esb-meta-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); font-weight: 500; }
.esb-meta-item i { color: var(--primary); font-size: 14px; }

.id-tag {
    background: var(--primary-light);
    color: var(--primary);
    padding: 4px 12px;
    border-radius: 8px;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
    font-size: 14px;
    border: 1px solid rgba(91, 78, 250, 0.1);
}

.profile-upload-zone {
    border: 2px dashed #e2e8f0;
    border-radius: var(--radius-md);
    padding: 20px;
    text-align: center;
    transition: var(--transition);
    cursor: pointer;
    background: #f8fafc;
}
.profile-upload-zone:hover {
    border-color: var(--primary);
    background: var(--primary-light);
}
.profile-preview-img {
    width: 100px; height: 100px; border-radius: 15px;
    object-fit: cover; margin-bottom: 12px;
    border: 3px solid #fff; box-shadow: var(--shadow-sm);
}

.form-actions-sticky {
    background: #fff;
    padding: 20px 30px;
    border-top: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 30px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}
.form-actions-sticky.is-sticky {
    position: fixed;
    bottom: 20px;
    left: 280px; /* Adjust based on sidebar width */
    right: 30px;
    z-index: 100;
    box-shadow: 0 -10px 25px rgba(0,0,0,0.05), 0 10px 20px rgba(0,0,0,0.1);
    border: 1px solid var(--primary-light);
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@media (max-width: 991px) {
    .form-actions-sticky.is-sticky { left: 20px; right: 20px; }
}
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
</style>

<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
CSS;

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Edit Student Profile</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Students</a> &rsaquo;
        <span>Update Profile</span>
      </div>
    </div>
    <div class="page-header-right">
        <a href="index.php" class="btn-lms btn-outline">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
  </div>

  <!-- Premium Student Banner -->
  <div class="edit-student-banner">
    <?php if (!empty($student['profile_picture'])): ?>
      <div class="esb-avatar" style="overflow:hidden; padding:0; background:none;">
        <img src="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($student['profile_picture']) ?>" 
             style="width:100%; height:100%; object-fit:cover;" alt="Profile">
      </div>
    <?php else: ?>
      <div class="esb-avatar" style="background:<?= studentAvatarColor($student['full_name']) ?>">
        <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
      </div>
    <?php endif; ?>
    
    <div class="esb-info">
      <div class="esb-name"><?= htmlspecialchars($student['full_name']) ?></div>
      <div class="esb-meta">
        <div class="esb-meta-item">
          <i class="fas fa-fingerprint"></i> 
          <span class="id-tag"><?= htmlspecialchars($student['student_id']) ?></span>
        </div>
        <div class="esb-meta-item">
          <i class="fas fa-calendar-alt"></i> Joined: <?= date('M d, Y', strtotime($student['join_date'])) ?>
        </div>
        <div class="esb-meta-item">
          <i class="fas fa-layer-group"></i> <?= htmlspecialchars($student['batch_number']) ?>
        </div>
        <div class="esb-meta-item">
            <?= renderStatusBadge($student['status']) ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($errors): ?>
  <div class="alert-lms danger auto-dismiss mb-20" id="validation-errors">
    <i class="fas fa-triangle-exclamation"></i>
    <div>
      <strong>Submission Errors</strong>
      <ul style="margin:4px 0 0;padding-left:18px; font-size:13px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST" action="edit.php?id=<?= $id ?>" id="editStudentForm" enctype="multipart/form-data" novalidate>

    <div class="row">
        <!-- LEFT COLUMN -->
        <div class="col-lg-8">
            <!-- SECTION 1: Personal Info -->
            <div class="card-lms mb-20">
              <div class="card-lms-header">
                <div class="card-lms-title">
                  <i class="fas fa-user-gear" style="color:var(--primary);"></i> Primary Information
                </div>
              </div>
              <div class="card-lms-body">
                <div class="row g-3">
                  <div class="col-md-7">
                    <div class="form-group-lms">
                      <label for="full_name">Full Legal Name <span class="req">*</span></label>
                      <input type="text" id="full_name" name="full_name"
                             class="form-control-lms <?= hasError($errors, 'Full name') ? 'is-invalid-lms' : '' ?>"
                             value="<?= htmlspecialchars($form['full_name']) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <div class="form-group-lms">
                      <label for="nic_number">NIC Number <span class="req">*</span></label>
                      <input type="text" id="nic_number" name="nic_number"
                             class="form-control-lms <?= hasError($errors, 'NIC') ? 'is-invalid-lms' : '' ?>"
                             value="<?= htmlspecialchars($form['nic_number']) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group-lms">
                      <label for="batch_number">Assigned Batch <span class="req">*</span></label>
                      <input type="text" id="batch_number" name="batch_number"
                             class="form-control-lms"
                             value="<?= htmlspecialchars($form['batch_number']) ?>" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group-lms">
                      <label for="join_date">Registration Date</label>
                      <input type="date" id="join_date" name="join_date"
                             class="form-control-lms"
                             value="<?= htmlspecialchars($form['join_date']) ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group-lms">
                      <label for="status">Current Status</label>
                      <select id="status" name="status" class="form-control-lms">
                        <?php foreach (['new_joined' => 'New Joined', 'dropout' => 'Dropout', 'completed' => 'Completed'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= $form['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 2: Contact Info -->
            <div class="card-lms mb-20">
              <div class="card-lms-header">
                <div class="card-lms-title">
                  <i class="fas fa-address-book" style="color:#3b82f6;"></i> Contact Details
                </div>
              </div>
              <div class="card-lms-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group-lms">
                      <label for="phone_number">Primary Mobile <span class="req">*</span></label>
                      <div class="input-icon-wrap">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone_number" name="phone_number"
                               class="form-control-lms with-icon"
                               value="<?= htmlspecialchars($form['phone_number']) ?>" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group-lms">
                      <label for="whatsapp_number">WhatsApp Number</label>
                      <div class="input-icon-wrap">
                        <i class="fab fa-whatsapp" style="color:#25d366;"></i>
                        <input type="tel" id="whatsapp_number" name="whatsapp_number"
                               class="form-control-lms with-icon"
                               value="<?= htmlspecialchars($form['whatsapp_number']) ?>">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group-lms">
                      <label for="personal_email">Personal Email</label>
                      <div class="input-icon-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="personal_email" name="personal_email"
                               class="form-control-lms with-icon"
                               value="<?= htmlspecialchars($form['personal_email']) ?>">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group-lms">
                      <label for="office_email">Assigned LMS Email</label>
                      <div class="input-icon-wrap">
                        <i class="fas fa-envelope-circle-check" style="color:var(--primary);"></i>
                        <input type="email" id="office_email" name="office_email"
                               class="form-control-lms with-icon"
                               value="<?= htmlspecialchars($form['office_email']) ?>">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 4: Address Info -->
            <div class="card-lms mb-20">
              <div class="card-lms-header">
                <div class="card-lms-title">
                  <i class="fas fa-map-location-dot" style="color:#10b981;"></i> Residential Information
                </div>
              </div>
              <div class="card-lms-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group-lms">
                      <label for="house_address">Permanent Home Address</label>
                      <textarea id="house_address" name="house_address"
                                class="form-control-lms" rows="3"><?= htmlspecialchars($form['house_address']) ?></textarea>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group-lms">
                      <label for="boarding_address">Current Boarding / Temporary</label>
                      <textarea id="boarding_address" name="boarding_address"
                                class="form-control-lms" rows="3"><?= htmlspecialchars($form['boarding_address']) ?></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">
            <!-- Profile Photo Update -->
            <div class="card-lms mb-20">
              <div class="card-lms-header">
                <div class="card-lms-title">
                  <i class="fas fa-camera" style="color:var(--accent);"></i> Profile Photo
                </div>
              </div>
              <div class="card-lms-body text-center">
                <div class="profile-upload-zone" onclick="document.getElementById('profile_picture').click()">
                    <?php if (!empty($student['profile_picture'])): ?>
                        <img src="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($student['profile_picture']) ?>" class="profile-preview-img" id="img-preview">
                    <?php else: ?>
                        <div class="esb-avatar mx-auto mb-15" style="background:<?= studentAvatarColor($student['full_name']) ?>; width:100px; height:100px; border-radius:25px;">
                            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="fw-700 color-primary" style="font-size:14px;">Change Photo</div>
                    <div class="text-muted" style="font-size:12px;">Click to upload JPG/PNG</div>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;" onchange="previewProfileImage(this)">
                </div>
              </div>
            </div>

            <!-- Guardian Info -->
            <div class="card-lms mb-20">
              <div class="card-lms-header">
                <div class="card-lms-title">
                  <i class="fas fa-shield-heart" style="color:#f59e0b;"></i> Guardian Info
                </div>
              </div>
              <div class="card-lms-body">
                <div class="form-group-lms">
                  <label for="guardian_name">Guardian Name</label>
                  <input type="text" id="guardian_name" name="guardian_name"
                         class="form-control-lms" value="<?= htmlspecialchars($form['guardian_name']) ?>">
                </div>
                <div class="form-group-lms mt-15">
                  <label for="guardian_phone">Guardian Phone</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="guardian_phone" name="guardian_phone"
                           class="form-control-lms with-icon" value="<?= htmlspecialchars($form['guardian_phone']) ?>">
                  </div>
                </div>
                <div class="mt-15">
                  <label class="d-flex align-items-center gap-2 cursor-pointer" style="font-weight: 600; color: #475569;">
                    <input type="checkbox" name="guardian_verified" value="1" <?= $form['guardian_verified'] == '1' ? 'checked' : '' ?> style="width:18px; height:18px;">
                    Verified by Admin
                  </label>
                </div>
              </div>
            </div>

            <!-- Follow-up -->
            <div class="card-lms mb-20">
              <div class="card-lms-header">
                <div class="card-lms-title">
                  <i class="fas fa-bell" style="color:#a855f7;"></i> Next Follow-up
                </div>
              </div>
              <div class="card-lms-body">
                <div class="form-group-lms">
                  <label for="next_follow_up">Alert Date</label>
                  <input type="date" id="next_follow_up" name="next_follow_up" class="form-control-lms" value="<?= htmlspecialchars($form['next_follow_up']) ?>">
                </div>
                <div class="form-group-lms mt-15">
                  <label for="follow_up_note">Instructions / Note</label>
                  <textarea id="follow_up_note" name="follow_up_note" class="form-control-lms" rows="2"><?= htmlspecialchars($form['follow_up_note']) ?></textarea>
                </div>
              </div>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Bar -->
    <div class="form-actions-sticky mt-20">
      <div class="d-flex align-items-center justify-content-between w-100">
          <div class="text-muted d-none d-md-block" style="font-size:13px;">
              <i class="fas fa-info-circle"></i> Carefully review all changes before updating.
          </div>
          <div class="d-flex gap-12">
              <a href="index.php" class="btn-lms btn-outline">Cancel Changes</a>
              <button type="submit" class="btn-primary-grad px-40">
                <i class="fas fa-check-circle"></i> Save Profile Changes
              </button>
          </div>
      </div>
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

<?php
$extraJS = <<<JS
<script>
let cropper = null;
let originalFileName = "";

function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        originalFileName = input.files[0].name;
        const reader = new FileReader();
        reader.onload = function(e) {
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
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function closeCropModal() {
    document.getElementById('cropModal').style.display = 'none';
    document.getElementById('profile_picture').value = ''; // Reset input
    if (cropper) cropper.destroy();
}

function applyCrop() {
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        width: 400,
        height: 400
    });
    
    canvas.toBlob(blob => {
        const file = new File([blob], originalFileName, { type: 'image/jpeg' });
        const container = new DataTransfer();
        container.items.add(file);
        document.getElementById('profile_picture').files = container.files;
        
        let preview = document.getElementById('img-preview');
        if (!preview) {
            const zone = document.querySelector('.profile-upload-zone');
            zone.innerHTML = '<img src="' + canvas.toDataURL('image/jpeg') + '" class="profile-preview-img" id="img-preview">' + zone.innerHTML;
            const avatar = zone.querySelector('.esb-avatar');
            if (avatar) avatar.remove();
        } else {
            preview.src = canvas.toDataURL('image/jpeg');
        }
        
        document.getElementById('cropModal').style.display = 'none';
        if (cropper) cropper.destroy();
    }, 'image/jpeg');
}

function restrictNumbers(e) {
    e.value = e.value.replace(/[^0-9]/g, '');
}
function restrictPhone(e) {
    let val = e.value;
    if (val.length > 0) {
        if (val[0] === '+') {
            e.value = '+' + val.slice(1).replace(/[^0-9+]/g, '').replace(/\+/g, '');
        } else {
            e.value = val.replace(/[^0-9]/g, '');
        }
    }
}

// Sticky bar logic
window.addEventListener('scroll', function() {
    const bar = document.querySelector('.form-actions-sticky');
    if (!bar) return;
    const scrollPos = window.innerHeight + window.scrollY;
    const bodyHeight = document.body.offsetHeight;
    if (scrollPos < bodyHeight - 50) {
        bar.classList.add('is-sticky');
    } else {
        bar.classList.remove('is-sticky');
    }
});

// Apply restrictions
document.addEventListener('DOMContentLoaded', function() {
    const phoneFields = ['phone_number', 'whatsapp_number', 'guardian_phone'];
    phoneFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() { restrictPhone(this); });
    });

    const numFields = ['nic_number'];
    numFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() { restrictNumbers(this); });
    });
});
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
