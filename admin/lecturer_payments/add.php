<?php
// =====================================================
// ISSD Management - Admin: Record Lecturer Payout
// admin/lecturer_payments/add.php
// =====================================================
define('PAGE_TITLE', 'Record Payout');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

$lecturers = $pdo->query("SELECT id, name FROM users WHERE role = 'lecturer' AND status = 'active'")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = addLecturerPayment($pdo, $_POST);
    if ($res['success']) {
        $success = "Payout recorded successfully.";
    } else {
        $error = implode(', ', $res['errors']);
    }
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Record Lecturer Payout</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Payroll</span> &rsaquo; <span>Record</span></div>
    </div>
    <a href="index.php" class="btn btn-light rounded-pill px-4 fw-700 shadow-sm"><i class="fas fa-arrow-left me-2"></i>Back to Hub</a>
  </div>

  <div class="row">
    <div class="col-md-6 mx-auto">
      <div class="card-lms">
        <div class="card-lms-header">
            <div class="card-lms-title"><i class="fas fa-money-bill-transfer"></i> Payout Details</div>
        </div>
        <div class="card-lms-body">
            <?php if ($error): ?><div class="alert-lms danger mb-20"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert-lms success mb-20"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group-lms mb-3">
                    <label>Select Lecturer</label>
                    <select name="lecturer_id" class="form-select-lms" required>
                        <option value="">-- Choose Lecturer --</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-group-lms">
                            <label>Amount (Rs.)</label>
                            <input type="number" step="0.01" name="amount" class="form-control-lms" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group-lms">
                            <label>For Month</label>
                            <input type="month" name="month" class="form-control-lms" value="<?= date('Y-m') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group-lms mb-4">
                    <label>Notes / References</label>
                    <textarea name="notes" class="form-control-lms" rows="3" placeholder="Commission for Python Batch, etc..."></textarea>
                </div>

                <button type="submit" class="btn-primary-grad w-100 py-3 rounded-pill fw-800">
                    <i class="fas fa-check-circle me-2"></i>Confirm & Record Payout
                </button>
            </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
