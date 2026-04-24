<?php
// =====================================================
// LEARN Management - Admin: Add Payment
// admin/payments/add.php
// =====================================================
define('PAGE_TITLE', 'Add Payment');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);

// Handle AJAX Info Fetch
if (isset($_GET['api']) && $_GET['api'] === 'info') {
    $sid = (int)($_GET['student_id'] ?? 0);
    $cid = (int)($_GET['course_id'] ?? 0);
    if ($sid && $cid) {
        header('Content-Type: application/json');
        echo json_encode(getPaymentInfoForm($pdo, $sid, $cid));
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['monthly_fee'=>0, 'previous_balance'=>0, 'total_due'=>0]);
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = addPayment($pdo, $_POST);
    if ($result['success']) {
        setFlash('success', 'Payment recorded successfully.');
        header('Location: index.php'); exit;
    }
    $errors = $result['errors'];
}

$studentsWithCourses = getStudentsWithActiveCourses($pdo);
// Group by student for easier selection
$grouped = [];
foreach ($studentsWithCourses as $row) {
    if (!isset($grouped[$row['student_id']])) {
        $grouped[$row['student_id']] = [
            'name' => $row['full_name'],
            'reg'  => $row['student_reg'],
            'courses' => []
        ];
    }
    $grouped[$row['student_id']]['courses'][] = [
        'id' => $row['course_id'],
        'name' => $row['course_code'] . ' - ' . $row['course_name']
    ];
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Process Student Payment</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Payments</a> &rsaquo;
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
          <i class="fas fa-user-graduate" style="color:var(--primary);"></i> Student Payment Details
        </div>
      </div>
      <div class="card-lms-body">
        
        <div class="row g-3">
          
          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Select Student <span class="req">*</span></label>
              <select name="student_id" id="student_id" class="form-control-lms" required onchange="updateCourseList()">
                <option value="">— Choose a Student —</option>
                <?php foreach ($grouped as $sId => $sData): ?>
                  <option value="<?= $sId ?>"><?= htmlspecialchars($sData['reg'] . ' - ' . $sData['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Select Course <span class="req">*</span></label>
              <select name="course_id" id="course_id" class="form-control-lms" required onchange="fetchPaymentInfo()">
                <option value="">— Choose a Course —</option>
              </select>
            </div>
          </div>

        </div>

        <hr style="margin:20px 0;border-color:#e2e8f0;">

        <!-- UI Display (Last Month Due, Current Fee, Total Payable) -->
        <div class="row g-3 mb-20">
          <div class="col-md-4">
            <div style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
              <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Last Month Due (Bal)</div>
              <div id="lbl_prev_bal" style="font-size:20px;font-weight:700;color:#dc2626;">Rs. 0.00</div>
            </div>
          </div>
          <div class="col-md-4">
            <div style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
              <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Current Fee</div>
              <div id="lbl_monthly_fee" style="font-size:20px;font-weight:700;color:#3b82f6;">Rs. 0.00</div>
            </div>
          </div>
          <div class="col-md-4">
            <div style="background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid #bbf7d0;">
              <div style="font-size:11px;color:#059669;font-weight:600;text-transform:uppercase;">Total Payable</div>
              <div id="lbl_total_due" style="font-size:20px;font-weight:800;color:#059669;">Rs. 0.00</div>
            </div>
          </div>
        </div>

        <div class="row g-3" style="align-items:flex-end;">
          <div class="col-md-6">
            <div class="form-group-lms mb-0">
              <label>Amount Paid <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-coins" style="color:#f59e0b;"></i>
                <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control-lms with-icon" 
                       placeholder="0.00" oninput="calcRemaining()" required>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div style="padding:10px 14px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;display:flex;justify-content:space-between;align-items:center;">
              <div style="font-size:12px;color:#991b1b;font-weight:700;text-transform:uppercase;">Remaining Balance</div>
              <div id="lbl_rem_bal" style="font-size:22px;font-weight:800;color:#dc2626;">Rs. 0.00</div>
            </div>
          </div>
        </div>

      </div>
      <div class="card-lms-body" style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 20px;">
        <button type="submit" class="btn-primary-grad">
          <i class="fas fa-floppy-disk"></i> Process Payment
        </button>
      </div>
    </div>

  </form>
</div>

<script>
const studentData = <?= json_encode($grouped) ?>;
let currentTotalDue = 0;

function updateCourseList() {
    const sId = document.getElementById('student_id').value;
    const cSel = document.getElementById('course_id');
    cSel.innerHTML = '<option value="">— Choose a Course —</option>';
    
    if (sId && studentData[sId]) {
        studentData[sId].courses.forEach(c => {
            let opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            cSel.appendChild(opt);
        });
    }
    fetchPaymentInfo();
}

function fetchPaymentInfo() {
    const sId = document.getElementById('student_id').value;
    const cId = document.getElementById('course_id').value;
    
    if (!sId || !cId) {
        document.getElementById('lbl_prev_bal').textContent = 'Rs. 0.00';
        document.getElementById('lbl_monthly_fee').textContent = 'Rs. 0.00';
        document.getElementById('lbl_total_due').textContent = 'Rs. 0.00';
        currentTotalDue = 0;
        calcRemaining();
        return;
    }

    fetch(`add.php?api=info&student_id=${sId}&course_id=${cId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('lbl_prev_bal').textContent = 'Rs. ' + parseFloat(data.previous_balance).toFixed(2);
            document.getElementById('lbl_monthly_fee').textContent = 'Rs. ' + parseFloat(data.monthly_fee).toFixed(2);
            document.getElementById('lbl_total_due').textContent = 'Rs. ' + parseFloat(data.total_due).toFixed(2);
            currentTotalDue = parseFloat(data.total_due);
            calcRemaining();
        })
        .catch(err => console.error(err));
}

function calcRemaining() {
    const paid = parseFloat(document.getElementById('amount_paid').value) || 0;
    let rem = currentTotalDue - paid;
    if (rem < 0) rem = 0; // If overpaid, don't show negative here, or you could if you want.
    document.getElementById('lbl_rem_bal').textContent = 'Rs. ' + rem.toFixed(2);
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
