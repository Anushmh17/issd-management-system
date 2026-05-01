<?php
// =====================================================
// ISSD Management - Admin: Add Student (Unified Form)
// admin/students/add.php
// =====================================================
define('PAGE_TITLE', 'Add Student');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/student_controller.php';
require_once dirname(__DIR__, 2) . '/backend/document_controller.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

$errors  = [];
$success = false;
$newId   = '';
$courses = getActiveCourses($pdo);

// Pre-fill form on validation failure or from Lead conversion
$form = [
    'full_name'             => $_GET['name'] ?? '',
    'nic_number'            => '',
    'batch_number'          => '',
    'join_date'             => date('Y-m-d'),
    'course_id'             => '',
    'office_email'          => '',
    'office_email_password' => '',
    'personal_email'        => '',
    'phone_number'          => $_GET['phone'] ?? '',
    'whatsapp_number'       => '',
    'guardian_name'         => '',
    'guardian_phone'        => '',
    'guardian_verified'     => '0',
    'house_address'         => '',
    'boarding_address'      => '',
    'next_follow_up'        => '',
    'follow_up_note'        => '',
    'status'                => 'new_joined',
];

$docDefs = getDocumentDefinitions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Merge posted data
    foreach ($form as $key => $_) {
        $form[$key] = $_POST[$key] ?? '';
    }

    // --- Validate Emails ---
    if (!empty($form['personal_email']) && $form['personal_email'] === $form['office_email']) {
        $errors[] = "Personal email and Office email cannot be the same.";
    }

    // --- Handle Profile Picture Upload ---
    if (!empty($_FILES['profile_picture']['name'])) {
        $upload = uploadDocumentFile($_FILES['profile_picture'], 'profile', 0); // Using 0 as student ID temporarily
        if ($upload['success']) {
            $form['profile_picture'] = $upload['path'];
        } else {
            $errors[] = "Profile Picture Error: " . $upload['error'];
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        $result = addStudent($pdo, $form);

        if ($result['success']) {
            $studentInternalId = $result['id'];
            $newId = $result['student_id'];
            
            // Assign course if selected
            $courseId = (int)($_POST['course_id'] ?? 0);
            if ($courseId > 0) {
                assignStudentToCourse($pdo, $studentInternalId, $courseId);
            }
            
            // Rename profile picture file if uploaded (since we now have the real internal ID)
            if (!empty($form['profile_picture'])) {
                $oldPath = BASE_PATH . '/assets/documents/' . $form['profile_picture'];
                $ext = pathinfo($form['profile_picture'], PATHINFO_EXTENSION);
                $newFileName = "profile_{$studentInternalId}." . $ext;
                $newPath = BASE_PATH . '/assets/documents/' . $newFileName;
                
                if (rename($oldPath, $newPath)) {
                    $pdo->prepare("UPDATE students SET profile_picture = ? WHERE id = ?")->execute([$newFileName, $studentInternalId]);
                }
            }
            
            // --- Process Documents ---
            $docRow = getOrCreateDocRecord($pdo, $studentInternalId);
            
            foreach ($docDefs as $docKey => $def) {
                $trackData = [
                    'status'       => isset($_POST["doc_status_$docKey"]) ? 1 : 0,
                    'collected_by' => $_POST["doc_office_$docKey"] ?? null,
                    'date'         => !empty($_POST["doc_date_$docKey"]) ? $_POST["doc_date_$docKey"] : null,
                ];

                // Handle file upload if any
                if (!empty($_FILES['doc_files']['name'][$docKey])) {
                    $fileArray = [
                        'name'     => $_FILES['doc_files']['name'][$docKey],
                        'type'     => $_FILES['doc_files']['type'][$docKey],
                        'tmp_name' => $_FILES['doc_files']['tmp_name'][$docKey],
                        'error'    => $_FILES['doc_files']['error'][$docKey],
                        'size'     => $_FILES['doc_files']['size'][$docKey],
                    ];
                    
                    $uploadResult = uploadDocumentFile($fileArray, $docKey, $studentInternalId);
                    if ($uploadResult['success']) {
                        $trackData['file_path'] = $uploadResult['path'];
                        $trackData['status'] = 1; // Auto-set status if file uploaded
                    } else {
                        error_log("Upload error for $docKey: " . $uploadResult['error']);
                    }
                }

                saveDocTracking($pdo, $studentInternalId, $docKey, $trackData);
            }

            // If coming from lead conversion, update lead status
            $leadId = (int)($_POST['lead_id'] ?? $_GET['lead_id'] ?? 0);
            if ($leadId > 0) {
                $pdo->prepare("UPDATE leads SET status='converted' WHERE id=?")->execute([$leadId]);
            }
            
            $pdo->commit();

            // --- Handle Follow-up Email Alert ---
            if (!empty($_POST['send_email_alert']) && !empty($_POST['follow_up_note'])) {
                $studentData = [
                    'full_name'    => $form['full_name'],
                    'student_id'   => $newId,
                    'phone_number' => $form['phone_number']
                ];
                sendAdminFollowUpEmail($studentData, $_POST['follow_up_note'], $_POST['next_follow_up'] ?: null);
            }

            setFlash('success', "Student added successfully! Student ID: <strong>{$newId}</strong>");
            header('Location: index.php');
            exit;
        } else {
            $pdo->rollBack();
            $errors = $result['errors'];
        }
    }
}

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
  max-width: 550px;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}
.crop-container {
  width: 100%;
  height: 450px;
  background: #0f172a;
  margin: 15px 0;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
.crop-container img {
  display: block;
  max-width: 100%;
}
.crop-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Add New Student</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Students</a> &rsaquo;
        <span>Add Student</span>
      </div>
    </div>
    <div class="page-header-right">
      <div id="overall-progress-container" class="progress-display">
         <div class="progress-label">DOCUMENTS COMPLETION</div>
         <div class="progress-bar-container">
            <div id="overall-progress-bar" class="progress-bar-fill"></div>
         </div>
         <div id="overall-progress-text" class="progress-text">0 / 0 Documents Collected</div>
      </div>
      <a href="index.php" class="btn-lms btn-outline" id="btn-back-list">
        <i class="fas fa-arrow-left"></i> Back to List
      </a>
    </div>
  </div>

  <?php if ($errors): ?>
  <div class="alert-lms danger auto-dismiss" id="validation-errors">
    <i class="fas fa-triangle-exclamation"></i>
    <div>
      <strong>Please fix the following errors:</strong>
      <ul style="margin:6px 0 0;padding-left:18px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST" action="add.php<?= isset($_GET['lead_id']) ? '?lead_id='.(int)$_GET['lead_id'] : '' ?>" id="addStudentForm" enctype="multipart/form-data" novalidate>
    <?php if (isset($_GET['lead_id'])): ?>
      <input type="hidden" name="lead_id" value="<?= (int)$_GET['lead_id'] ?>">
    <?php endif; ?>

    <div class="row">
      <!-- Left Column: Student Details -->
      <div class="col-lg-12">
        
        <!-- SECTION 1: BASIC INFORMATION -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-user-tag" style="color:#5b4efa;"></i> Section 1 "" Basic Information
            </div>
            <span class="section-badge">Required *</span>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="full_name">Full Name <span class="req">*</span></label>
                  <input type="text" id="full_name" name="full_name" class="form-control-lms" value="<?= htmlspecialchars($form['full_name']) ?>" required placeholder="Enter full name">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms">
                  <label for="nic_number">NIC Number <span class="req">*</span></label>
                  <input type="text" id="nic_number" name="nic_number" class="form-control-lms" value="<?= htmlspecialchars($form['nic_number']) ?>" required placeholder="e.g. 991234567V">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group-lms">
                  <label>Student ID</label>
                  <input type="text" class="form-control-lms" value="[Auto-generated]" readonly style="background:#f1f5f9; color:#64748b; font-weight: 600;">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms">
                  <label for="batch_number">Batch Number <span class="req">*</span></label>
                  <input type="text" id="batch_number" name="batch_number" class="form-control-lms" value="<?= htmlspecialchars($form['batch_number']) ?>" required placeholder="e.g. BATCH-2026-01">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms">
                  <label for="join_date">Join Date <span class="req">*</span></label>
                  <input type="date" id="join_date" name="join_date" class="form-control-lms" value="<?= htmlspecialchars($form['join_date']) ?>" required>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms">
                  <label for="status">Registration Status</label>
                  <select id="status" name="status" class="form-control-lms">
                    <option value="new_joined" <?= $form['status'] === 'new_joined' ? 'selected' : '' ?>>New Joined</option>
                    <option value="dropout" <?= $form['status'] === 'dropout' ? 'selected' : '' ?>>Dropout</option>
                    <option value="completed" <?= $form['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms">
                  <label for="course_id">Course <span class="req">*</span></label>
                  <select id="course_id" name="course_id" class="form-control-lms select2-search" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $c): ?>
                      <option value="<?= $c['id'] ?>" <?= $form['course_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_name']) ?> (<?= htmlspecialchars($c['course_code']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms">
                  <label for="profile_picture">Profile Picture (JPG/PNG)</label>
                  <input type="file" id="profile_picture" name="profile_picture" class="form-control-lms" accept="image/*" onchange="handleProfileSelect(this)">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 2: INSTITUTE DETAILS -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-building-columns" style="color:#ef4444;"></i> Section 2 "" Institute Details
            </div>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="office_email">Office Email <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="office_email" name="office_email" class="form-control-lms with-icon" value="<?= htmlspecialchars($form['office_email']) ?>" required placeholder="student@institute.com">
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="office_email_password">Office Email Password <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="office_email_password" name="office_email_password" class="form-control-lms with-icon" value="<?= htmlspecialchars($form['office_email_password']) ?>" required placeholder="••••••••">
                    <i class="fas fa-eye toggle-password" data-target="#office_email_password" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8;"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 3: CONTACT INFORMATION -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-address-book" style="color:#3b82f6;"></i> Section 3 "" Contact Information
            </div>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="personal_email">Personal Email (Optional)</label>
                  <input type="email" id="personal_email" name="personal_email" class="form-control-lms" value="<?= htmlspecialchars($form['personal_email']) ?>" placeholder="student@gmail.com">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="phone_number">Telephone Number <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone_number" name="phone_number" class="form-control-lms with-icon" value="<?= htmlspecialchars($form['phone_number']) ?>" required placeholder="07XXXXXXXX">
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="whatsapp_number">WhatsApp Number <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fab fa-whatsapp" style="color:#25d366;"></i>
                    <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-control-lms with-icon" value="<?= htmlspecialchars($form['whatsapp_number']) ?>" required placeholder="07XXXXXXXX">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 4: GUARDIAN DETAILS -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-users-viewfinder" style="color:#f59e0b;"></i> Section 4 "" Guardian Details
            </div>
          </div>
          <div class="card-lms-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-5">
                <div class="form-group-lms">
                  <label for="guardian_name">Guardian Name <span class="req">*</span></label>
                  <input type="text" id="guardian_name" name="guardian_name" class="form-control-lms" value="<?= htmlspecialchars($form['guardian_name']) ?>" required placeholder="Full name of guardian">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="guardian_phone">Guardian Phone Number <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="guardian_phone" name="guardian_phone" class="form-control-lms with-icon" value="<?= htmlspecialchars($form['guardian_phone']) ?>" required placeholder="07XXXXXXXX">
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-lms" style="margin-bottom: 12px;">
                  <label class="d-flex align-items-center gap-2 cursor-pointer" style="font-weight: 600; color: #475569;">
                    <input type="checkbox" name="guardian_verified" value="1" <?= $form['guardian_verified'] == '1' ? 'checked' : '' ?> style="width:18px; height:18px;">
                    Verified by Admin
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 5: ADDRESS DETAILS -->
        <div class="card-lms mb-20">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-map-location-dot" style="color:#10b981;"></i> Section 5 "" Address Details
            </div>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="house_address">House Address <span class="req">*</span></label>
                  <textarea id="house_address" name="house_address" class="form-control-lms" rows="3" required placeholder="Permanent home address"><?= htmlspecialchars($form['house_address']) ?></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-lms">
                  <label for="boarding_address">Boarding Address (Optional)</label>
                  <textarea id="boarding_address" name="boarding_address" class="form-control-lms" rows="3" placeholder="Current boarding or temporary address"><?= htmlspecialchars($form['boarding_address']) ?></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 6: FOLLOW-UP & ALERTS -->
        <div class="card-lms mb-20 premium-border">
          <div class="card-lms-header">
            <div class="card-lms-title">
              <i class="fas fa-calendar-check" style="color:#a855f7;"></i> Section 6 "" Follow-up & Alerts
            </div>
            <span class="badge-lms info-premium">New</span>
          </div>
          <div class="card-lms-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="form-group-lms">
                  <label for="next_follow_up">Next Follow-up Date</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-clock"></i>
                    <input type="date" id="next_follow_up" name="next_follow_up" class="form-control-lms with-icon" value="<?= htmlspecialchars($form['next_follow_up'] ?? '') ?>">
                  </div>
                  <small class="text-muted">System will alert the admin on this date.</small>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group-lms">
                  <label for="follow_up_note">Follow-up Instructions / Note</label>
                  <input type="text" id="follow_up_note" name="follow_up_note" class="form-control-lms" placeholder="e.g. Call student to collect missing O/L result sheet" value="<?= htmlspecialchars($form['follow_up_note'] ?? '') ?>">
                </div>
              </div>
            </div>
            <div class="mt-15">
              <label class="d-flex align-items-center gap-2 cursor-pointer" style="font-weight: 600; color: #475569;">
                <input type="checkbox" name="send_email_alert" value="1" checked style="width:18px; height:18px;">
                Send instant email alert to Admin
              </label>
            </div>
          </div>
        </div>

        <!-- SECTION 7: DOCUMENT CHECKLIST -->
        <div class="card-lms mb-30 document-checklist-card">
          <div class="card-lms-header d-flex justify-content-between align-items-center">
            <div class="card-lms-title">
              <i class="fas fa-file-shield" style="color:#6366f1;"></i> Section 7 "" Document Checklist
            </div>
            <div class="header-badges">
              <span class="badge-lms info-premium" id="doc-count-badge">0 / 0 Collected</span>
            </div>
          </div>
          <div class="card-lms-body p-0">
            <div class="table-responsive">
              <table class="table-lms premium-table mb-0">
                <thead>
                  <tr>
                    <th style="width: 280px;">DOCUMENT NAME</th>
                    <th style="width: 100px; text-align:center;">REQUIRED</th>
                    <th style="min-width: 220px;">UPLOAD FILE</th>
                    <th style="width: 110px; text-align:center;">COLLECTED</th>
                    <th style="width: 140px;">OFFICE</th>
                    <th style="width: 150px;">DATE</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($docDefs as $docKey => $def): ?>
                  <tr class="doc-row" data-key="<?= $docKey ?>" data-required="<?= $def['required'] ? '1' : '0' ?>">
                    <td>
                      <div class="doc-info">
                        <div class="doc-icon-box">
                          <i class="fas <?= $def['icon'] ?>"></i>
                        </div>
                        <div class="doc-text">
                          <span class="doc-label"><?= htmlspecialchars($def['label']) ?></span>
                          <span class="doc-sub">Supporting document</span>
                        </div>
                      </div>
                    </td>
                    <td style="text-align:center;">
                      <?php if ($def['required']): ?>
                        <span class="status-pill status-required">YES</span>
                      <?php else: ?>
                        <span class="status-pill status-optional">NO</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="premium-upload-control">
                        <input type="file" name="doc_files[<?= $docKey ?>]" id="file_<?= $docKey ?>" class="hidden-file-input" onchange="handleFileSelect('<?= $docKey ?>')">
                        <label for="file_<?= $docKey ?>" class="upload-btn">
                          <i class="fas fa-cloud-arrow-up"></i>
                          <span>Choose File</span>
                        </label>
                        <div class="file-status-text" id="file_name_<?= $docKey ?>">No file chosen</div>
                      </div>
                    </td>
                    <td style="text-align:center;">
                      <div class="pretty-checkbox">
                        <input type="checkbox" name="doc_status_<?= $docKey ?>" id="cb_<?= $docKey ?>" value="1" class="doc-checkbox" onchange="toggleDocFields('<?= $docKey ?>')">
                        <label for="cb_<?= $docKey ?>"></label>
                      </div>
                    </td>
                    <td>
                      <select name="doc_office_<?= $docKey ?>" class="form-control-lms form-control-sm doc-office-select" disabled>
                        <option value="">""</option>
                        <option value="H1">H1</option>
                        <option value="H2">H2</option>
                        <option value="W1">W1</option>
                        <option value="W2">W2</option>
                      </select>
                    </td>
                    <td>
                      <input type="date" name="doc_date_<?= $docKey ?>" class="form-control-lms form-control-sm doc-date-input">
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions mt-20">
      <button type="submit" class="btn-primary-grad px-4 py-2" style="font-weight: 700; font-size: 16px;">
        <i class="fas fa-save"></i> Save Student Record
      </button>
      <a href="index.php" class="btn-lms btn-outline px-4 py-2">
        <i class="fas fa-times"></i> Cancel
      </a>
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

<style>
  :root {
    --premium-primary: #5b4efa;
    --premium-success: #10b981;
    --premium-danger: #ef4444;
    --premium-warning: #f59e0b;
    --premium-info: #3b82f6;
    --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  }

  .req { color: var(--premium-danger); font-weight: bold; }
  .cursor-pointer { cursor: pointer; }
  
  /* Progress Display */
  .progress-display { text-align: right; margin-right: 20px; min-width: 220px; }
  .progress-label { font-size: 11px; color: #64748b; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.5px; }
  .progress-bar-container { width: 100%; height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }
  .progress-bar-fill { width: 0%; height: 100%; background: linear-gradient(90deg, var(--premium-primary), #8b5cf6); transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
  .progress-text { font-size: 11px; font-weight: 700; color: var(--premium-primary); margin-top: 5px; }

  /* Card Enhancements */
  .card-lms { border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--card-shadow); overflow: hidden; transition: transform 0.2s; }
  .card-lms-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; }
  .card-lms-title { font-family: 'Inter', sans-serif; font-weight: 700; color: #1e293b; font-size: 15px; }
  .section-badge { font-size: 10px; background: #fee2e2; color: #ef4444; padding: 4px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase; }

  /* Premium Table */
  .premium-table thead th { background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 15px 20px; border: none; }
  .doc-row { border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
  .doc-row:hover { background: #f8fafc; }
  .doc-row.completed { background: #f0fdf4 !important; }
  .doc-row.missing-req { border-left: 4px solid var(--premium-danger); }
  
  .doc-info { display: flex; align-items: center; gap: 15px; padding: 12px 0; }
  .doc-icon-box { width: 40px; height: 40px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #64748b; }
  .doc-row.completed .doc-icon-box { background: #dcfce7; color: #10b981; }
  .doc-text { display: flex; flex-direction: column; }
  .doc-label { font-weight: 700; color: #334155; font-size: 14px; }
  .doc-sub { font-size: 11px; color: #94a3b8; font-weight: 500; }

  /* Status Pills */
  .status-pill { font-size: 10px; font-weight: 800; padding: 4px 12px; border-radius: 20px; }
  .status-required { background: #fee2e2; color: #ef4444; }
  .status-optional { background: #f1f5f9; color: #64748b; }

  /* Premium Upload Control */
  .premium-upload-control { position: relative; display: flex; flex-direction: column; gap: 5px; }
  .hidden-file-input { position: absolute; opacity: 0; width: 1px; height: 1px; }
  .upload-btn { 
    display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1.5px solid #e2e8f0; 
    color: #475569; padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 600; 
    cursor: pointer; transition: all 0.2s; width: fit-content;
  }
  .upload-btn:hover { border-color: var(--premium-primary); color: var(--premium-primary); background: #f5f3ff; }
  .doc-row.completed .upload-btn { border-color: #10b981; color: #10b981; background: #f0fdf4; }
  .file-status-text { font-size: 10px; color: #94a3b8; font-weight: 500; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

  /* Pretty Checkbox */
  .pretty-checkbox { position: relative; width: 24px; height: 24px; margin: 0 auto; }
  .pretty-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
  .pretty-checkbox label { 
    position: absolute; top: 0; left: 0; height: 24px; width: 24px; background-color: #fff; 
    border: 2px solid #cbd5e1; border-radius: 6px; cursor: pointer; transition: all 0.2s;
  }
  .pretty-checkbox:hover label { border-color: var(--premium-primary); }
  .pretty-checkbox input:checked ~ label { background-color: var(--premium-primary); border-color: var(--premium-primary); }
  .pretty-checkbox label:after { 
    content: ""; position: absolute; display: none; left: 8px; top: 3px; width: 6px; height: 12px; 
    border: solid white; border-width: 0 2.5px 2.5px 0; transform: rotate(45deg); 
  }
  .pretty-checkbox input:checked ~ label:after { display: block; }
  .pretty-checkbox input:checked ~ label { background-color: var(--premium-success); border-color: var(--premium-success); }

  .badge-lms.info-premium { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 800; padding: 6px 14px; border-radius: 30px; }

  /* Form Group Hover */
  .form-group-lms:focus-within label { color: var(--premium-primary); }
  .form-control-lms:focus { border-color: var(--premium-primary); box-shadow: 0 0 0 4px rgba(91, 78, 250, 0.1); }
</style>

<?php
$extraJS = <<<'JS'
<script>
let cropper = null;
let originalFileName = "";

function handleProfileSelect(input) {
    if (input.files && input.files[0]) {
        originalFileName = input.files[0].name;
        const reader = new FileReader();
        reader.onload = function(e) {
            const modal = document.getElementById('cropModal');
            const cropImg = document.getElementById('cropImage');
            
            // Destroy existing cropper
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            cropImg.src = e.target.result;
            modal.style.display = 'flex';
            
            // Wait for image to be fully loaded in DOM before initializing Cropper
            cropImg.onload = function() {
                cropper = new Cropper(cropImg, {
                    aspectRatio: 1,
                    viewMode: 1, // Restrict the crop box not to exceed the size of the canvas
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
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
        
        // No preview element in students/add.php, just show modal closed
        document.getElementById('cropModal').style.display = 'none';
        if (cropper) cropper.destroy();
    }, 'image/jpeg');
}

// Restriction logics
function restrictNumbers(e) {
    e.value = e.value.replace(/[^0-9]/g, '');
}
function restrictPhone(e) {
    let val = e.value;
    // Remove all characters except numbers and +
    val = val.replace(/[^0-9+]/g, '');
    
    // Only allow + at index 0
    if (val.indexOf('+') > 0) {
        val = val.substring(0, 1) + val.substring(1).replace(/\+/g, '');
    }

    if (val.startsWith('0')) {
        // Sri Lankan local format: 10 digits max
        if (val.length > 10) val = val.substring(0, 10);
    } else if (val.startsWith('+94')) {
        // Sri Lankan international format: 12 characters max (+94XXXXXXXXX)
        if (val.length > 12) val = val.substring(0, 12);
    } else if (val.startsWith('94')) {
        // Without +, but still 11 digits
        if (val.length > 11) val = val.substring(0, 11);
    }
    
    e.value = val;
}

document.addEventListener('DOMContentLoaded', function() {
    updateOverallProgress();
    
    // Apply restrictions
    const phoneFields = ['phone_number', 'whatsapp_number', 'guardian_phone'];
    phoneFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() { restrictPhone(this); });
    });

    const numFields = ['nic_number']; // User said no letters in number fields
    numFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() { restrictNumbers(this); });
    });

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = document.querySelector(this.dataset.target);
            if (target.type === 'password') {
                target.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                target.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Email uniqueness validation
    const addStudentForm = document.getElementById('addStudentForm');
    if (addStudentForm) {
        addStudentForm.addEventListener('submit', function(e) {
            const officeEmail = document.getElementById('office_email').value.trim();
            const personalEmail = document.getElementById('personal_email').value.trim();
            
            if (personalEmail !== '' && officeEmail === personalEmail) {
                e.preventDefault();
                alert('Validation Error: Personal email and Office email cannot be the same.');
                document.getElementById('personal_email').focus();
            }
        });
    }
});

function toggleDocFields(key) {
    const row = document.querySelector(`tr[data-key="${key}"]`);
    const checkbox = row.querySelector('.doc-checkbox');
    const officeSelect = row.querySelector('.doc-office-select');
    const dateInput = row.querySelector('.doc-date-input');
    
    if (checkbox.checked) {
        officeSelect.disabled = false;
        officeSelect.required = true;
        // Auto set today's date if empty
        if (!dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        row.classList.add('completed');
    } else {
        officeSelect.disabled = true;
        officeSelect.required = false;
        officeSelect.value = '';
        dateInput.value = '';
        row.classList.remove('completed');
    }
    updateOverallProgress();
}

function handleFileSelect(key) {
    const row = document.querySelector(`tr[data-key="${key}"]`);
    const fileInput = row.querySelector('.hidden-file-input');
    const checkbox = row.querySelector('.doc-checkbox');
    const nameLabel = document.getElementById(`file_name_${key}`);
    
    if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        nameLabel.innerText = fileName;
        nameLabel.style.color = '#10b981';
        checkbox.checked = true;
        toggleDocFields(key);
    } else {
        nameLabel.innerText = 'No file chosen';
        nameLabel.style.color = '#94a3b8';
    }
}

function updateOverallProgress() {
    const rows = document.querySelectorAll('.doc-row');
    const total = rows.length;
    let collected = 0;
    
    const requiredRows = document.querySelectorAll('.doc-row[data-required="1"]');
    const totalReq = requiredRows.length;
    let collectedReq = 0;

    rows.forEach(row => {
        if (row.querySelector('.doc-checkbox').checked) {
            collected++;
            if (row.dataset.required === "1") collectedReq++;
        }
    });

    // Update UI
    const pct = total > 0 ? Math.round((collected / total) * 100) : 0;
    const bar = document.getElementById('overall-progress-bar');
    const text = document.getElementById('overall-progress-text');
    const badge = document.getElementById('doc-count-badge');

    if (bar) bar.style.width = pct + '%';
    if (text) text.innerText = `${collected} / ${total} Documents Collected (${collectedReq} / ${totalReq} Mandatory)`;
    if (badge) badge.innerText = `${collected} / ${total} Collected`;
}

// Form validation before submit
document.getElementById('addStudentForm').addEventListener('submit', function(e) {
    const requiredDocs = document.querySelectorAll('.doc-row[data-required="1"]');
    let missingDocs = [];
    
    requiredDocs.forEach(row => {
        const checkbox = row.querySelector('.doc-checkbox');
        const label = row.querySelector('.doc-label').innerText;
        if (!checkbox.checked) {
            missingDocs.push(label);
            row.classList.add('missing-req');
        } else {
            row.classList.remove('missing-req');
        }
    });

    if (missingDocs.length > 0) {
        // We allow submission but maybe warn? 
        // According to requirements "Required and optional fields are clearly handled"
        // Let's check if the user wants to BLOCK submission if docs are missing.
        // Usually registration might happen even if docs are pending, but "Required" implies they should be there.
        // I will just highlight them for now unless it's a hard rule.
    }
    // Initialize Select2
    $('.select2-search').select2({ width: '100%' });
});
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>

