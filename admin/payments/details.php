<?php
// =====================================================
// ISSD Management - Admin: Student Financial Profile
// admin/payments/details.php
// =====================================================
define('PAGE_TITLE', 'Payment Profile');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$student_id = (int)($_GET['student_id'] ?? 0);
if (!$student_id) { header("Location: index.php"); exit; }

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) { die("Student not found."); }

// Get all payments for this student
$stmt = $pdo->prepare("
    SELECT p.*, c.course_name, c.course_code 
    FROM student_payments p
    JOIN courses c ON p.course_id = c.id
    WHERE p.student_id = ?
    ORDER BY p.payment_date DESC
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

// Calculate totals
$totalPaid = 0;
$totalBalance = 0;
foreach ($history as $h) {
    $totalPaid += $h['amount_paid'];
    if ($h['status'] !== 'paid') {
        // Only count the LATEST balance for each course to avoid double counting
        // But for simplicity in this view, we'll just show the latest balance from the most recent record
    }
}
$latestBalance = !empty($history) ? $history[0]['balance'] : 0;

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Financial Profile</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Finance</span> &rsaquo; <span>Profile</span></div>
    </div>
    <a href="index.php" class="btn btn-light rounded-pill px-4 fw-700 shadow-sm"><i class="fas fa-arrow-left me-2"></i>Back to Hub</a>
  </div>

  <div class="row g-4">
    <!-- Student Info Card -->
    <div class="col-md-4">
        <div class="card-lms mb-4">
            <div class="card-lms-body text-center p-4">
                <div class="avatar-initials mx-auto mb-3" style="width:80px; height:80px; font-size:32px; background:var(--primary); color:#fff;">
                    <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                </div>
                <h3 class="fw-900 mb-0"><?= htmlspecialchars($student['full_name']) ?></h3>
                <div class="text-muted fw-600 mb-20"><?= htmlspecialchars($student['student_id']) ?></div>
                
                <div class="d-flex justify-content-center gap-3 mb-20">
                    <div class="text-center">
                        <div class="fw-800" style="font-size:18px;">Rs. <?= number_format($totalPaid, 0) ?></div>
                        <div class="text-muted" style="font-size:10px;">Total Paid</div>
                    </div>
                    <div class="vr"></div>
                    <div class="text-center">
                        <div class="fw-800 text-danger" style="font-size:18px;">Rs. <?= number_format($latestBalance, 0) ?></div>
                        <div class="text-muted" style="font-size:10px;">Current Balance</div>
                    </div>
                </div>

                <a href="add.php?student_id=<?= $student_id ?>" class="btn btn-primary w-100 rounded-pill fw-800 py-2">
                    <i class="fas fa-money-bill-transfer me-2"></i>New Transaction
                </a>
            </div>
        </div>

        <div class="bento-card" style="background:var(--accent-light); border: 1px solid var(--accent); color: var(--accent-dark);">
            <h4 class="fw-800 mb-10" style="font-size:15px;"><i class="fas fa-info-circle me-2"></i>Quick Summary</h4>
            <div class="d-flex justify-content-between mb-2">
                <span style="font-size:12px;">Joined Date</span>
                <span class="fw-700" style="font-size:12px;"><?= date('d M Y', strtotime($student['join_date'])) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span style="font-size:12px;">Status</span>
                <span class="badge bg-white text-dark rounded-pill fw-700" style="font-size:10px;"><?= strtoupper($student['status']) ?></span>
            </div>
        </div>
    </div>

    <!-- History Timeline -->
    <div class="col-md-8">
        <div class="card-lms">
            <div class="card-lms-header">
                <div class="card-lms-title"><i class="fas fa-clock-rotate-left"></i> Full Payment History</div>
            </div>
            <div class="card-lms-body p-0">
                <?php if (empty($history)): ?>
                    <div class="p-50 text-center text-muted">No payments recorded for this student.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-lms mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td style="font-size:12px;"><?= date('d M Y', strtotime($h['payment_date'])) ?></td>
                                    <td>
                                        <div class="fw-700" style="font-size:13px;"><?= htmlspecialchars($h['course_code']) ?></div>
                                        <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($h['course_name']) ?></div>
                                    </td>
                                    <td class="fw-800 text-success">Rs. <?= number_format($h['amount_paid'], 2) ?></td>
                                    <td class="fw-800 text-danger">Rs. <?= number_format($h['balance'], 2) ?></td>
                                    <td>
                                        <span class="badge-lms" style="background:var(--<?= $h['status']=='paid'?'accent':'warning' ?>-light); color:var(--<?= $h['status']=='paid'?'accent':'warning' ?>-dark); font-size:10px;">
                                            <?= strtoupper($h['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="receipt.php?id=<?= $h['id'] ?>" class="btn-lms btn-sm" style="background:#f1f5f9;"><i class="fas fa-print"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
