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
    'house_address'         => $student['house_address'] ?? '',
    'boarding_address'      => $student['boarding_address'] ?? '',
    'status'                => $student['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Merge posted data
    foreach ($form as $key => $_) {
        $form[$key] = $_POST[$key] ?? '';
    }

    $result = updateStudent($pdo, $id, $form);

    if ($result['success']) {
        setFlash('success', 'Student <strong>' . htmlspecialchars($student['full_name']) . '</strong> updated successfully.');
        header('Location: index.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

function hasError(array $errors, string $keyword): bool {
    foreach ($errors as $e) {
        if (stripos($e, $keyword) !== false) return true;
    }
    return false;
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Edit Student</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Students</a> &rsaquo;
        <span>Edit: <?= htmlspecialchars($student['full_name']) ?></span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline" id="btn-back-list">
      <i class="fas fa-arrow-left"></i> Back to List
    </a>
  </div>

  <!-- Student ID Banner -->
  <div class="student-id-banner mb-20">
    <div class="sib-icon"><i class="fas fa-id-badge"></i></div>
    <div class="sib-info">
      <div class="sib-label">Student ID</div>
      <div class="sib-value"><?= htmlspecialchars($student['student_id']) ?></div>
    </div>
    <div class="sib-meta">
      <span><i class="fas fa-calendar-plus"></i> Registered: <?= date('d M Y', strtotime($student['created_at'])) ?></span>
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

  <form method="POST" action="edit.php?id=<?= $id ?>" id="editStudentForm" novalidate>

    <!-- ── SECTION 1: Personal Info ── -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-id-card" style="color:#5b4efa;"></i> Personal Information
        </div>
        <span class="section-badge">Required fields marked *</span>
      </div>
      <div class="card-lms-body">
        <div class="row g-3">

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="full_name">Full Name <span class="req">*</span></label>
              <input type="text" id="full_name" name="full_name"
                     class="form-control-lms <?= hasError($errors, 'Full name') ? 'is-invalid-lms' : '' ?>"
                     value="<?= htmlspecialchars($form['full_name']) ?>"
                     placeholder="e.g. Kamal Perera" required>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="nic_number">NIC Number <span class="req">*</span></label>
              <input type="text" id="nic_number" name="nic_number"
                     class="form-control-lms <?= hasError($errors, 'NIC') ? 'is-invalid-lms' : '' ?>"
                     value="<?= htmlspecialchars($form['nic_number']) ?>"
                     placeholder="e.g. 991234567V" required>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="batch_number">Batch Number <span class="req">*</span></label>
              <input type="text" id="batch_number" name="batch_number"
                     class="form-control-lms <?= hasError($errors, 'Batch') ? 'is-invalid-lms' : '' ?>"
                     value="<?= htmlspecialchars($form['batch_number']) ?>"
                     placeholder="e.g. BATCH-2026-01" required>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="join_date">Join Date</label>
              <input type="date" id="join_date" name="join_date"
                     class="form-control-lms"
                     value="<?= htmlspecialchars($form['join_date']) ?>">
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="status">Status</label>
              <select id="status" name="status" class="form-control-lms">
                <?php foreach (['new_joined' => 'New Joined', 'dropout' => 'Dropout', 'completed' => 'Completed'] as $val => $lbl): ?>
                  <option value="<?= $val ?>" <?= $form['status'] === $val ? 'selected' : '' ?>>
                    <?= $lbl ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── SECTION 2: Contact Info ── -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-phone-volume" style="color:#3b82f6;"></i> Contact Information
        </div>
      </div>
      <div class="card-lms-body">
        <div class="row g-3">

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="phone_number">Phone Number <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-phone"></i>
                <input type="tel" id="phone_number" name="phone_number"
                       class="form-control-lms with-icon <?= hasError($errors, 'Phone') ? 'is-invalid-lms' : '' ?>"
                       value="<?= htmlspecialchars($form['phone_number']) ?>"
                       placeholder="07X XXX XXXX" required>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="whatsapp_number">WhatsApp Number</label>
              <div class="input-icon-wrap">
                <i class="fab fa-whatsapp" style="color:#25d366;"></i>
                <input type="tel" id="whatsapp_number" name="whatsapp_number"
                       class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['whatsapp_number']) ?>"
                       placeholder="07X XXX XXXX">
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="personal_email">Personal Email</label>
              <div class="input-icon-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" id="personal_email" name="personal_email"
                       class="form-control-lms with-icon <?= hasError($errors, 'Personal email') ? 'is-invalid-lms' : '' ?>"
                       value="<?= htmlspecialchars($form['personal_email']) ?>"
                       placeholder="student@gmail.com">
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="office_email">Office Email</label>
              <div class="input-icon-wrap">
                <i class="fas fa-envelope-circle-check" style="color:#5b4efa;"></i>
                <input type="email" id="office_email" name="office_email"
                       class="form-control-lms with-icon <?= hasError($errors, 'Office email') ? 'is-invalid-lms' : '' ?>"
                       value="<?= htmlspecialchars($form['office_email']) ?>"
                       placeholder="student@institute.com">
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label for="office_email_password">Office Email Password</label>
              <div class="input-icon-wrap" style="position:relative;">
                <i class="fas fa-lock"></i>
                <input type="password" id="office_email_password" name="office_email_password"
                       class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['office_email_password']) ?>"
                       placeholder="Leave blank to keep current"
                       autocomplete="new-password">
                <button type="button" class="pwd-toggle" onclick="togglePwd('office_email_password', this)"
                        title="Show/Hide">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── SECTION 3: Guardian Info ── -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-people-roof" style="color:#f59e0b;"></i> Guardian Information
        </div>
      </div>
      <div class="card-lms-body">
        <div class="row g-3">

          <div class="col-md-6">
            <div class="form-group-lms">
              <label for="guardian_name">Guardian Name</label>
              <input type="text" id="guardian_name" name="guardian_name"
                     class="form-control-lms"
                     value="<?= htmlspecialchars($form['guardian_name']) ?>"
                     placeholder="e.g. Nimal Perera">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label for="guardian_phone">Guardian Phone</label>
              <div class="input-icon-wrap">
                <i class="fas fa-phone"></i>
                <input type="tel" id="guardian_phone" name="guardian_phone"
                       class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['guardian_phone']) ?>"
                       placeholder="07X XXX XXXX">
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── SECTION 4: Address Info ── -->
    <div class="card-lms mb-20">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-location-dot" style="color:#10b981;"></i> Address Information
        </div>
      </div>
      <div class="card-lms-body">
        <div class="row g-3">

          <div class="col-md-6">
            <div class="form-group-lms">
              <label for="house_address">House Address</label>
              <textarea id="house_address" name="house_address"
                        class="form-control-lms" rows="3"
                        placeholder="Permanent home address…"><?= htmlspecialchars($form['house_address']) ?></textarea>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label for="boarding_address">Boarding Address</label>
              <textarea id="boarding_address" name="boarding_address"
                        class="form-control-lms" rows="3"
                        placeholder="Current boarding/temporary address…"><?= htmlspecialchars($form['boarding_address']) ?></textarea>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── Action Buttons ── -->
    <div class="form-actions">
      <button type="submit" class="btn-primary-grad" id="btn-update-student">
        <i class="fas fa-floppy-disk"></i> Update Student
      </button>
      <a href="index.php" class="btn-lms btn-outline" id="btn-cancel-edit">
        <i class="fas fa-xmark"></i> Cancel
      </a>
    </div>

  </form>
</div>

<?php
$extraJS = <<<JS
<script>
function togglePwd(fieldId, btn) {
  const inp = document.getElementById(fieldId);
  const ico = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.className = 'fas fa-eye-slash';
  } else {
    inp.type = 'password';
    ico.className = 'fas fa-eye';
  }
}
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
