<?php
// =====================================================
// LEARN Management - Admin: Add Lead
// admin/leads/add.php
// =====================================================
define('PAGE_TITLE', 'Add Lead');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/leads_controller.php';

requireRole(ROLE_ADMIN);

$errors  = [];
$form = [
    'name'                   => '',
    'phone'                  => '',
    'source'                 => 'Other',
    'status'                 => 'new',
    'next_followup_datetime' => '',
    'notes'                  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';
    
    $result = addLead($pdo, $form);
    if ($result['success']) {
        setFlash('success', 'Lead <strong>' . htmlspecialchars($form['name']) . '</strong> added successfully.');
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
      <h1>Add New Lead</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Leads</a> &rsaquo;
        <span>Add Lead</span>
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

  <form method="POST" action="add.php" id="addLeadForm">

    <div class="card-lms mb-20" style="max-width:800px;margin:0 auto;">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-user-plus" style="color:#3b82f6;"></i> Lead Details
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
                       placeholder="e.g. Kasun Silva" required>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Phone Number <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" class="form-control-lms with-icon"
                       value="<?= htmlspecialchars($form['phone']) ?>"
                       placeholder="07X XXX XXXX" required>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Source</label>
              <select name="source" class="form-control-lms">
                <option value="Facebook" <?= $form['source']==='Facebook'?'selected':'' ?>>Facebook</option>
                <option value="WhatsApp" <?= $form['source']==='WhatsApp'?'selected':'' ?>>WhatsApp</option>
                <option value="Walk-in" <?= $form['source']==='Walk-in'?'selected':'' ?>>Walk-in</option>
                <option value="Other" <?= $form['source']==='Other'?'selected':'' ?>>Other</option>
              </select>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Status <span class="req">*</span></label>
              <select name="status" class="form-control-lms">
                <option value="new" <?= $form['status']==='new'?'selected':'' ?>>New</option>
                <option value="talking" <?= $form['status']==='talking'?'selected':'' ?>>Talking</option>
                <option value="converted" <?= $form['status']==='converted'?'selected':'' ?>>Converted</option>
                <option value="not_interested" <?= $form['status']==='not_interested'?'selected':'' ?>>Not Interested</option>
              </select>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Next Follow-up</label>
              <input type="datetime-local" name="next_followup_datetime" class="form-control-lms"
                     value="<?= htmlspecialchars($form['next_followup_datetime']) ?>">
            </div>
          </div>

          <div class="col-12">
            <div class="form-group-lms">
              <label>Notes</label>
              <textarea name="notes" class="form-control-lms" rows="4"
                        placeholder="Any additional details or previous conversation snippets..."><?= htmlspecialchars($form['notes']) ?></textarea>
            </div>
          </div>

        </div>
      </div>
      <div class="card-lms-body" style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 20px;">
        <button type="submit" class="btn-primary-grad">
          <i class="fas fa-floppy-disk"></i> Save Lead
        </button>
        <a href="index.php" class="btn-lms btn-outline" style="margin-left:8px;">Cancel</a>
      </div>
    </div>

  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
