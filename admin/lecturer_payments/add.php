<?php
// =====================================================
// LEARN Management - Admin: Add Lecturer Payment
// admin/lecturer_payments/add.php
// =====================================================
define('PAGE_TITLE', 'Add Lecturer Payment');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/lecturer_payment_controller.php';

requireRole(ROLE_ADMIN);

$errors = [];
$form = [
    'lecturer_id'   => '',
    'amount'        => '',
    'payment_month' => date('Y-m'),
    'status'        => 'pending',
    'notes'         => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = $_POST[$k] ?? '';

    $result = addLecturerPayment($pdo, $form);
    if ($result['success']) {
        setFlash('success', 'Lecturer payment added successfully.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
}

// Get active lecturers
$stmt = $pdo->query("SELECT id, name FROM lecturers WHERE status = 'active' ORDER BY name ASC");
$lecturers = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Record Lecturer Payment</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Lecturer Payouts</a> &rsaquo;
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

  <form method="POST" action="add.php">

    <div class="card-lms" style="max-width:800px;margin:0 auto;">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <i class="fas fa-sack-dollar" style="color:#10b981;"></i> Payout Details
        </div>
      </div>
      <div class="card-lms-body">
        
        <div class="row g-3">
          
          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Select Lecturer <span class="req">*</span></label>
              <select name="lecturer_id" class="form-control-lms" required>
                <option value="">— Choose a Lecturer —</option>
                <?php foreach ($lecturers as $l): ?>
                  <option value="<?= $l['id'] ?>" <?= $form['lecturer_id']==$l['id']?'selected':'' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Amount (Rs) <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-money-bill-wave"></i>
                <input type="number" step="0.01" name="amount" class="form-control-lms with-icon" 
                       value="<?= htmlspecialchars($form['amount']) ?>" placeholder="0.00" required>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Payment Month <span class="req">*</span></label>
              <input type="month" name="payment_month" class="form-control-lms" 
                     value="<?= htmlspecialchars($form['payment_month']) ?>" required>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Initial Status</label>
              <select name="status" class="form-control-lms">
                <option value="pending" <?= $form['status']==='pending'?'selected':'' ?>>Pending (Mark later)</option>
                <option value="paid"    <?= $form['status']==='paid'?'selected':'' ?>>Paid (Processed immediately)</option>
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group-lms">
              <label>Notes</label>
              <textarea name="notes" class="form-control-lms" rows="3" placeholder="Any optional remarks..."><?= htmlspecialchars($form['notes']) ?></textarea>
            </div>
          </div>

        </div>

      </div>
      <div class="card-lms-body" style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 20px;">
        <button type="submit" class="btn-primary-grad">
          <i class="fas fa-floppy-disk"></i> Save Payout
        </button>
      </div>
    </div>

  </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
