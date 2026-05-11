<?php
// =====================================================
// ISSD Management - Admin: Add Payment
// admin/payments/add.php
// =====================================================
define('PAGE_TITLE', 'Add Payment');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

requireRole(ROLE_ADMIN);


$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
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


<style>
.select2-container--default .select2-selection--single {
    height: 48px !important;
    padding: 10px 15px !important;
    border: 1.5px solid var(--border-color) !important;
    border-radius: 12px !important;
    background-color: var(--bg-input) !important;
    display: flex !important;
    align-items: center !important;
    transition: all 0.2s ease;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 4px rgba(91, 78, 250, 0.1) !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px !important;
    top: 1px !important;
    right: 12px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    color: var(--text-main) !important;
    font-weight: 600 !important;
    padding-left: 0 !important;
    font-size: 14px !important;
}
.select2-dropdown {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 12px !important;
    box-shadow: var(--shadow-lg) !important;
    overflow: hidden;
    z-index: 9999;
}
.select2-results__option {
    color: var(--text-main) !important;
}
.select2-search__field {
    background-color: var(--bg-input) !important;
    color: var(--text-main) !important;
    border: 1px solid var(--border-color) !important;
}

/* Payment Info Boxes */
.pay-info-box {
    background: var(--bg-input);
    padding: 15px;
    border-radius: 14px;
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}
.pay-info-box.success-tint {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16, 185, 129, 0.2);
}
.pay-info-label {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 5px;
}
.pay-info-value {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

/* Remaining Balance Box */
#rem_bal_box {
    padding: 16px 20px;
    border-radius: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    border: 1.5px solid transparent;
}
#rem_bal_box.danger-tint {
    background: rgba(239, 68, 68, 0.08);
    border-color: rgba(239, 68, 68, 0.2);
}
#rem_bal_box.success-tint {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16, 185, 129, 0.2);
}
#rem_bal_label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
#lbl_rem_bal { font-size: 26px; font-weight: 800; letter-spacing: -1px; }

body.lms-dark-mode .pay-info-box { background: rgba(255,255,255,0.05); }
body.lms-dark-mode .success-tint { background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); }
body.lms-dark-mode .danger-tint { background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); }
</style>


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
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

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
              <select name="student_id" id="student_id" class="form-control-lms select2-search" required>
                <option value="">-- Choose a Student --</option>
                <?php foreach ($grouped as $sId => $sData): ?>
                  <option value="<?= $sId ?>"><?= htmlspecialchars($sData['reg'] . ' - ' . $sData['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Select Course <span class="req">*</span></label>
              <select name="course_id" id="course_id" class="form-control-lms" required>
                <option value="">-- Choose a Course --</option>
              </select>
            </div>
          </div>

        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Payment Month <span class="req">*</span></label>
              <input type="month" name="month" id="month" class="form-control-lms" value="<?= date('Y-m') ?>" required onchange="fetchPaymentInfo()">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Payment Method <span class="req">*</span></label>
              <select name="method" class="form-control-lms" required>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online">Online</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Reference (Optional)</label>
              <input type="text" name="reference" class="form-control-lms" placeholder="Txn ID, Receipt #">
            </div>
          </div>
        </div>

        <hr style="margin:20px 0; border-color: rgba(0,0,0,0.05); opacity: 0.1;">

        <!-- UI Display (Last Month Due, Current Fee, Total Payable) -->
        <div class="row g-3 mb-20">
          <div class="col-md-4">
            <div class="pay-info-box">
              <div class="pay-info-label">Last Month Due (Bal)</div>
              <div id="lbl_prev_bal" class="pay-info-value" style="color:#ef4444;">Rs. 0.00</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="pay-info-box">
              <div class="pay-info-label">Current Fee</div>
              <div id="lbl_monthly_fee" class="pay-info-value" style="color:#3b82f6;">Rs. 0.00</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="pay-info-box success-tint">
              <div class="pay-info-label" style="color:#10b981;">Total Payable</div>
              <div id="lbl_total_due" class="pay-info-value" style="color:#10b981;">Rs. 0.00</div>
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
            <div id="rem_bal_box" class="danger-tint">
              <div id="rem_bal_label" style="color:#ef4444;">Remaining Balance</div>
              <div id="lbl_rem_bal" style="color:#ef4444;">Rs. 0.00</div>
            </div>
          </div>

        </div>

        <!-- Next Due Date (Conditional) -->
        <div class="row g-3 mt-3" id="next_due_row" style="display:none;">
          <div class="col-md-6">
            <div class="form-group-lms">
              <label>Next Due Date <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-calendar-day" style="color:var(--primary);"></i>
                <input type="date" name="next_due_date" id="next_due_date" class="form-control-lms with-icon" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
              </div>
              <small class="text-muted">When should the student pay the remaining balance?</small>
            </div>
          </div>
        </div>

      </div>
      <div class="card-lms-body" style="background: rgba(0,0,0,0.02); border-top:1px solid rgba(0,0,0,0.05); padding:16px 20px;">
        <button type="submit" class="btn-primary-grad px-5 py-2" style="border-radius: 12px;">
          <i class="fas fa-check-circle me-2"></i> Process Payment
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
    cSel.innerHTML = '<option value="">-- Choose a Course --</option>';
    
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
    const sId   = document.getElementById('student_id').value;
    const cId   = document.getElementById('course_id').value;
    const month = document.getElementById('month').value;
    
    if (!sId || !cId) {
        document.getElementById('lbl_prev_bal').textContent = 'Rs. 0.00';
        document.getElementById('lbl_monthly_fee').textContent = 'Rs. 0.00';
        document.getElementById('lbl_total_due').textContent = 'Rs. 0.00';
        currentTotalDue = 0;
        calcRemaining();
        return;
    }

    fetch(`api.php?api=info&student_id=${sId}&course_id=${cId}&month=${month}`)
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
    
    const box   = document.getElementById('rem_bal_box');
    const lbl   = document.getElementById('rem_bal_label');
    const val   = document.getElementById('lbl_rem_bal');

    if (rem <= 0) {
        // Fully paid or Advance
        box.classList.remove('danger-tint');
        box.classList.add('success-tint');
        lbl.style.color = '#10b981';
        val.style.color = '#10b981';
        lbl.textContent = rem < 0 ? 'Advance Payment' : 'Paid in Full';
        val.textContent = 'Rs. ' + Math.abs(rem).toFixed(2);
        document.getElementById('next_due_row').style.display = 'none';
        document.getElementById('next_due_date').required = false;
    } else {
        // Partial
        box.classList.remove('success-tint');
        box.classList.add('danger-tint');
        lbl.style.color = '#ef4444';
        val.style.color = '#ef4444';
        lbl.textContent = 'Remaining Balance';
        val.textContent = 'Rs. ' + rem.toFixed(2);
        document.getElementById('next_due_row').style.display = 'block';
        document.getElementById('next_due_date').required = true;
    }
}

</script>

<?php
$extraJS = <<<'JS'
<script>
$(document).ready(function() {
    console.log("Finance JS Initializing...");
    
    // Initialize Select2
    if ($.fn.select2) {
        $('#student_id, #course_id').select2({ 
            width: '100%',
            placeholder: '-- Select --'
        });
    } else {
        console.warn("Select2 not found, using standard dropdown");
    }

    // Handle Student Selection
    $(document).on('change', '#student_id', function() {
        const sId = $(this).val();
        const $courseSelect = $('#course_id');
        
        console.log("Student selected ID:", sId);
        
        if (!sId) {
            $courseSelect.html('<option value="">-- Choose a Course --</option>');
            if (typeof fetchPaymentInfo === 'function') fetchPaymentInfo();
            return;
        }

        $courseSelect.html('<option value="">-- Loading Courses... --</option>');

        $.ajax({
            url: 'api.php',
            data: { api: 'courses', student_id: sId },
            dataType: 'json',
            success: function(courses) {
                console.log("AJAX Success. Courses:", courses);
                $courseSelect.html('<option value="">-- Choose a Course --</option>');
                if (!courses || courses.length === 0) {
                    $courseSelect.append('<option value="">No enrolled courses found</option>');
                } else {
                    courses.forEach(function(c) {
                        $courseSelect.append($('<option>', {
                            value: c.id,
                            text: c.course_code + ' - ' + c.course_name
                        }));
                    });
                    if (courses.length === 1) {
                        $courseSelect.val(courses[0].id).trigger('change');
                    }
                }
                if (typeof fetchPaymentInfo === 'function') fetchPaymentInfo();
            },
            error: function(xhr, status, err) {
                console.error("AJAX Error:", status, err);
                $courseSelect.html('<option value="">-- Error loading courses --</option>');
            }
        });
    });

    $(document).on('change', '#course_id', function() {
        if (typeof fetchPaymentInfo === 'function') fetchPaymentInfo();
    });

    if (typeof flatpickr === 'function') {
        flatpickr("#next_due_date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            altInput: true,
            altFormat: "F j, Y"
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const sid = urlParams.get('student_id');
    if (sid) {
        $('#student_id').val(sid).trigger('change');
    }
});
</script>
JS;
require_once dirname(__DIR__, 2) . '/includes/footer.php'; 
?>

