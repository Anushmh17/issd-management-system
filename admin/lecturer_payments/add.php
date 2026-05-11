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

$preselect = (int)($_GET['lecturer_id'] ?? 0);

// Load all active lecturers with their payment settings
$lecturers = $pdo->query("SELECT id, name, department, payment_mode, per_student_rate FROM lecturers WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Load all course assignments with student count + monthly fee
$courseRows = $pdo->query("
    SELECT ca.lecturer_id,
           c.id        AS course_id,
           c.course_name,
           c.course_code,
           c.monthly_fee,
           (SELECT COUNT(*) FROM student_courses sc
            WHERE sc.course_id = c.id AND sc.status = 'ongoing') AS student_count
    FROM course_assignments ca
    JOIN courses c ON c.id = ca.course_id
    WHERE c.status = 'active'
    ORDER BY c.course_name ASC
")->fetchAll();

// Group by lecturer_id for fast JS lookup
$coursesByLecturer = [];
foreach ($courseRows as $row) {
    $coursesByLecturer[(int)$row['lecturer_id']][] = [
        'id'            => (int)$row['course_id'],
        'name'          => $row['course_name'],
        'code'          => $row['course_code'],
        'monthly_fee'   => (float)$row['monthly_fee'],
        'student_count' => (int)$row['student_count'],
    ];
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $paymentType = trim($_POST['payment_type'] ?? 'flat');

    // Calculate amount for per-student mode
    if ($paymentType === 'per_student') {
        $rate  = (float)($_POST['rate_per_student'] ?? 0);
        $count = (int)($_POST['student_count_actual'] ?? 0);
        $total = round($rate * $count, 2);
        $_POST['amount'] = $total;
        $breakdown = "Per-student: Rs. " . number_format($rate, 2) . " × {$count} students = Rs. " . number_format($total, 2);
        $extraNote = trim($_POST['notes'] ?? '');
        $_POST['notes'] = $breakdown . ($extraNote ? "\n" . $extraNote : '');
    }

    $res = addLecturerPayment($pdo, $_POST);

    if ($res['success']) {
        // Auto-clear any pending payout alert notifications for this lecturer
        $lid = (int)($_POST['lecturer_id'] ?? 0);
        if ($lid) {
            $lname = $pdo->prepare("SELECT name FROM lecturers WHERE id = ?");
            $lname->execute([$lid]);
            $name = $lname->fetchColumn();
            if ($name) {
                $pdo->prepare("UPDATE notifications SET status = 'read' WHERE title = ? AND status = 'unread'")
                    ->execute(["Lecturer Payout Due: $name"]);
            }
        }
        $success = "Payout recorded successfully!";
    } else {
        $error = implode(', ', $res['errors'] ?? ['Unknown error.']);
    }
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Record Lecturer Payout</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <a href="index.php">Payroll</a> &rsaquo; <span>Record</span></div>
    </div>
    <a href="index.php" class="btn btn-light rounded-pill px-4 fw-700 shadow-sm"><i class="fas fa-arrow-left me-2"></i>Back to Hub</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card-lms">
        <div class="card-lms-header">
          <div class="card-lms-title"><i class="fas fa-money-bill-transfer"></i> Payout Details</div>
        </div>
        <div class="card-lms-body">

          <?php if ($error): ?>
            <div class="alert-lms danger mb-20"><i class="fas fa-circle-xmark me-2"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert-lms success mb-20"><i class="fas fa-circle-check me-2"></i><?= $success ?> <a href="index.php" class="fw-700">← Back to Hub</a></div>
          <?php endif; ?>

          <form method="POST" id="payoutForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="payment_type" id="payment_type_hidden" value="flat">

            <!-- Step 1: Lecturer -->
            <div class="form-group-lms mb-3">
              <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px;">
                <i class="fas fa-chalkboard-user me-1" style="color:#6366f1;"></i> Lecturer
              </label>
              <select name="lecturer_id" id="lecturer_select" class="form-select-lms" required onchange="loadCourses()">
                <option value="">— Choose Lecturer —</option>
                <?php foreach ($lecturers as $l): ?>
                  <option value="<?= $l['id'] ?>"
                    data-payment-mode="<?= htmlspecialchars($l['payment_mode'] ?? 'flat_monthly') ?>"
                    data-rate="<?= htmlspecialchars($l['per_student_rate'] ?? '') ?>"
                    <?= $preselect === (int)$l['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?><?= $l['department'] ? ' (' . htmlspecialchars($l['department']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Step 2: Course -->
            <div class="form-group-lms mb-3" id="course_row" style="display:none;">
              <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px;">
                <i class="fas fa-book-open me-1" style="color:#10b981;"></i> Course
              </label>
              <select name="course_id" id="course_select" class="form-select-lms" onchange="onCourseChange()">
                <option value="">— No specific course (General payout) —</option>
              </select>
            </div>

            <!-- Step 3: Payment Type (shown when course selected) -->
            <div id="payment_type_row" style="display:none; margin-bottom:16px;">
              <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px;">
                <i class="fas fa-sliders me-1" style="color:#f59e0b;"></i> Payment Mode
              </label>
              <div style="display:flex; gap:10px; margin-top:8px;">
                <label id="btn_flat" onclick="setPaymentType('flat')"
                  style="flex:1; display:flex; align-items:center; gap:10px; padding:14px 16px; border:2px solid #6366f1; border-radius:14px; cursor:pointer; background:#eef2ff; transition:all 0.2s;">
                  <div style="width:20px; height:20px; border-radius:50%; background:#6366f1; display:flex; align-items:center; justify-content:center;" id="dot_flat">
                    <div style="width:8px; height:8px; border-radius:50%; background:#fff;"></div>
                  </div>
                  <div>
                    <div style="font-size:13px; font-weight:800; color:#4338ca;">Flat Monthly</div>
                    <div style="font-size:11px; color:#6366f1;">Fixed amount per month</div>
                  </div>
                </label>
                <label id="btn_per_student" onclick="setPaymentType('per_student')"
                  style="flex:1; display:flex; align-items:center; gap:10px; padding:14px 16px; border:2px solid #e2e8f0; border-radius:14px; cursor:pointer; background:#f8fafc; transition:all 0.2s;">
                  <div style="width:20px; height:20px; border-radius:50%; border:2px solid #cbd5e1; background:#fff; display:flex; align-items:center; justify-content:center;" id="dot_per_student">
                    <div style="width:8px; height:8px; border-radius:50%; background:transparent;"></div>
                  </div>
                  <div>
                    <div style="font-size:13px; font-weight:800; color:#475569;">Per Student</div>
                    <div style="font-size:11px; color:#94a3b8;">Rate × enrolled students</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Flat Amount Panel -->
            <div id="flat_panel">
              <div class="form-group-lms mb-3">
                <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px;">
                  <i class="fas fa-rupee-sign me-1" style="color:#6366f1;"></i> Amount (Rs.)
                </label>
                <input type="number" step="0.01" name="amount" id="flat_amount" class="form-control-lms" placeholder="0.00" required>
              </div>
            </div>

            <!-- Per-Student Panel -->
            <div id="per_student_panel" style="display:none;">
              <div style="background:#fff7ed; border:1.5px solid #fed7aa; border-radius:16px; padding:18px; margin-bottom:16px;">
                <div style="font-size:11px; font-weight:800; color:#92400e; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:14px;">
                  <i class="fas fa-calculator me-1"></i> Rate Calculator
                </div>
                <div class="row g-3">
                  <div class="col-6">
                    <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:5px;">Rate per Student (Rs.)</label>
                    <input type="number" step="0.01" id="rate_per_student" name="rate_per_student" class="form-control-lms"
                           placeholder="e.g. 500" oninput="calcTotal()">
                  </div>
                  <div class="col-6">
                    <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:5px;">
                      Student Count
                      <span id="student_count_hint" style="color:#10b981; margin-left:4px; font-size:10px;"></span>
                    </label>
                    <input type="number" id="student_count_actual" name="student_count_actual" class="form-control-lms"
                           placeholder="0" oninput="calcTotal()">
                  </div>
                </div>
                <!-- Calculated Total -->
                <div id="calc_total_box" style="display:none; margin-top:14px; padding:12px 16px; background:#fff; border-radius:12px; border:2px solid #10b981;">
                  <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:2px;">Calculated Total</div>
                  <div id="calc_total_val" style="font-size:24px; font-weight:800; color:#10b981;">Rs. 0.00</div>
                </div>
              </div>
            </div>

            <!-- Month + Notes Row -->
            <div class="row g-3 mb-3">
              <div class="col-md-5">
                <div class="form-group-lms">
                  <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px;">
                    <i class="fas fa-calendar me-1" style="color:#6366f1;"></i> For Month
                  </label>
                  <input type="month" name="month" class="form-control-lms" value="<?= date('Y-m') ?>" required>
                </div>
              </div>
              <div class="col-md-7">
                <div class="form-group-lms">
                  <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px;">
                    <i class="fas fa-note-sticky me-1" style="color:#6366f1;"></i> Notes (optional)
                  </label>
                  <input type="text" name="notes" class="form-control-lms" placeholder="e.g. Python batch commission...">
                </div>
              </div>
            </div>

            <button type="submit" class="btn-primary-grad w-100 py-3 rounded-pill fw-800" style="font-size:15px;">
              <i class="fas fa-check-circle me-2"></i>Confirm &amp; Record Payout
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Courses data from server -->
<script>
const coursesByLecturer = <?= json_encode($coursesByLecturer) ?>;
let currentPaymentType = 'flat';

function loadCourses() {
    const sel  = document.getElementById('lecturer_select');
    const lid  = parseInt(sel.value);
    const courseSelect = document.getElementById('course_select');
    const courseRow    = document.getElementById('course_row');
    const typeRow      = document.getElementById('payment_type_row');

    courseSelect.innerHTML = '<option value="">— No specific course (General payout) —</option>';
    document.getElementById('flat_amount').value = '';
    document.getElementById('rate_per_student').value = '';
    document.getElementById('student_count_actual').value = '';
    document.getElementById('student_count_hint').textContent = '';
    document.getElementById('calc_total_box').style.display = 'none';
    typeRow.style.display = 'none';

    if (!lid) { courseRow.style.display = 'none'; return; }

    const courses = coursesByLecturer[lid] || [];
    courseRow.style.display = courses.length ? 'block' : 'none';
    courses.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.name} (${c.code}) — ${c.student_count} student${c.student_count !== 1 ? 's' : ''}`;
        opt.dataset.fee   = c.monthly_fee;
        opt.dataset.count = c.student_count;
        courseSelect.appendChild(opt);
    });

    // Auto-apply lecturer's saved payment settings
    const selectedOpt  = sel.options[sel.selectedIndex];
    const savedMode    = selectedOpt.dataset.paymentMode || 'flat';
    const savedRate    = selectedOpt.dataset.rate || '';

    if (courses.length) {
        typeRow.style.display = 'block';
        const mode = savedMode === 'per_student' ? 'per_student' : 'flat';
        setPaymentType(mode);
        if (mode === 'per_student' && savedRate) {
            document.getElementById('rate_per_student').value = savedRate;
        }
    }
}

function onCourseChange() {
    const courseSelect = document.getElementById('course_select');
    const typeRow      = document.getElementById('payment_type_row');
    const selected     = courseSelect.options[courseSelect.selectedIndex];

    if (!courseSelect.value) {
        typeRow.style.display = 'none';
        setPaymentType('flat');
        document.getElementById('flat_amount').value = '';
        return;
    }

    const fee   = parseFloat(selected.dataset.fee   || 0);
    const count = parseInt(selected.dataset.count    || 0);

    typeRow.style.display = 'flex';
    setPaymentType('flat');

    // Pre-fill flat amount with course monthly fee
    document.getElementById('flat_amount').value = fee > 0 ? fee.toFixed(2) : '';
    // Pre-fill per-student count
    document.getElementById('student_count_actual').value = count;
    document.getElementById('student_count_hint').textContent = `(${count} enrolled)`;
    calcTotal();
}

function setPaymentType(type) {
    currentPaymentType = type;
    document.getElementById('payment_type_hidden').value = type;

    const flatPanel = document.getElementById('flat_panel');
    const psPanel   = document.getElementById('per_student_panel');
    const btnFlat   = document.getElementById('btn_flat');
    const btnPS     = document.getElementById('btn_per_student');
    const dotFlat   = document.getElementById('dot_flat');
    const dotPS     = document.getElementById('dot_per_student');
    const amountField = document.getElementById('flat_amount');

    if (type === 'flat') {
        flatPanel.style.display = 'block';
        psPanel.style.display   = 'none';
        amountField.required    = true;

        btnFlat.style.cssText  += ';border-color:#6366f1;background:#eef2ff;';
        btnPS.style.cssText    += ';border-color:#e2e8f0;background:#f8fafc;';
        dotFlat.style.background = '#6366f1';
        dotFlat.innerHTML = '<div style="width:8px;height:8px;border-radius:50%;background:#fff;"></div>';
        dotPS.style.cssText += ';border:2px solid #cbd5e1;background:#fff;';
        dotPS.innerHTML = '<div style="width:8px;height:8px;border-radius:50%;background:transparent;"></div>';
    } else {
        flatPanel.style.display = 'none';
        psPanel.style.display   = 'block';
        amountField.required    = false;

        btnPS.style.cssText    += ';border-color:#f59e0b;background:#fff7ed;';
        btnFlat.style.cssText  += ';border-color:#e2e8f0;background:#f8fafc;';
        dotPS.style.cssText    += ';background:#f59e0b;border-color:#f59e0b;';
        dotPS.innerHTML = '<div style="width:8px;height:8px;border-radius:50%;background:#fff;"></div>';
        dotFlat.style.background = '#fff';
        dotFlat.style.border = '2px solid #cbd5e1';
        dotFlat.innerHTML = '<div style="width:8px;height:8px;border-radius:50%;background:transparent;"></div>';
        calcTotal();
    }
}

function calcTotal() {
    const rate  = parseFloat(document.getElementById('rate_per_student').value) || 0;
    const count = parseInt(document.getElementById('student_count_actual').value) || 0;
    const total = rate * count;
    const box   = document.getElementById('calc_total_box');
    const val   = document.getElementById('calc_total_val');

    if (rate > 0 && count > 0) {
        box.style.display = 'block';
        val.textContent   = 'Rs. ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        box.style.display = 'none';
    }
}

// Auto-trigger course load if lecturer was pre-selected via URL
window.addEventListener('DOMContentLoaded', function() {
    const lid = document.getElementById('lecturer_select').value;
    if (lid) loadCourses();
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
