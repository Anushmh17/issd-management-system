<?php
// =====================================================
// LEARN Management - Admin: Reports
// =====================================================
define('PAGE_TITLE', 'Reports');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

// Summarize Payments (monthly)
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_paid) as total
    FROM student_payments
    WHERE status = 'paid'
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Summarize Enrollments per Course
$courseEnrollments = $pdo->query("
    SELECT c.course_name as title, COUNT(e.id) as cnt
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    GROUP BY c.id
    ORDER BY cnt DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Quick counts
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status!='dropout'")->fetchColumn();
$totalLecturers = $pdo->query("SELECT COUNT(*) FROM lecturers WHERE status='active'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(amount_paid) FROM student_payments WHERE status='paid'")->fetchColumn();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Reports & Analytics</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Reports</span></div>
    </div>
    <button onclick="window.print()" class="btn-lms btn-outline"><i class="fas fa-print"></i> Print Report</button>
  </div>

  <div class="row g-4" id="report-printable">
    <!-- Highlight Cards -->
    <div class="col-md-3">
        <div class="card-lms p-4 text-center">
            <h3 style="color:var(--primary);margin:0;font-size:28px;font-weight:700;"><?= $totalStudents ?></h3>
            <div class="text-muted" style="font-size:13px;margin-top:5px;font-weight:600;text-transform:uppercase;">Total Students</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-lms p-4 text-center">
            <h3 style="color:var(--accent);margin:0;font-size:28px;font-weight:700;"><?= $totalLecturers ?></h3>
            <div class="text-muted" style="font-size:13px;margin-top:5px;font-weight:600;text-transform:uppercase;">Total Lecturers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-lms p-4 text-center">
            <h3 style="color:var(--info);margin:0;font-size:28px;font-weight:700;"><?= $totalCourses ?></h3>
            <div class="text-muted" style="font-size:13px;margin-top:5px;font-weight:600;text-transform:uppercase;">Total Courses</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-lms p-4 text-center">
            <h3 style="color:var(--warning);margin:0;font-size:28px;font-weight:700;">Rs.<?= number_format($totalRevenue,0) ?></h3>
            <div class="text-muted" style="font-size:13px;margin-top:5px;font-weight:600;text-transform:uppercase;">Total Revenue</div>
        </div>
    </div>

    <!-- Charts / Tables area -->
    <div class="col-md-6">
        <div class="card-lms h-100">
            <div class="card-lms-header"><div class="card-lms-title"><i class="fas fa-chart-bar"></i> Monthly Revenue (Last 6 Months)</div></div>
            <div class="card-lms-body p-0">
                <table class="table-lms">
                    <thead><tr><th>Month</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                        <?php if(empty($monthlyRevenue)): ?>
                            <tr><td colspan="2" class="text-center text-muted">No revenue data available.</td></tr>
                        <?php else: ?>
                            <?php foreach($monthlyRevenue as $month => $total): ?>
                            <tr>
                                <td><div class="fw-600"><?= date('F Y', strtotime($month.'-01')) ?></div></td>
                                <td class="text-end fw-700" style="color:var(--accent);">Rs.<?= number_format($total,2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-lms h-100">
            <div class="card-lms-header"><div class="card-lms-title"><i class="fas fa-chart-pie"></i> Top 5 Courses by Enrollment</div></div>
            <div class="card-lms-body p-0">
                <table class="table-lms">
                    <thead><tr><th>Course Name</th><th class="text-center">Students Enrolled</th></tr></thead>
                    <tbody>
                        <?php if(empty($courseEnrollments)): ?>
                            <tr><td colspan="2" class="text-center text-muted">No enrollment data available.</td></tr>
                        <?php else: ?>
                            <?php foreach($courseEnrollments as $course => $cnt): ?>
                            <tr>
                                <td><div class="fw-600"><?= htmlspecialchars($course) ?></div></td>
                                <td class="text-center"><span class="badge-lms primary"><?= $cnt ?> Students</span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>

<style>
@media print {
    #sidebar, #top-navbar, .btn-lms, .page-header-left .breadcrumb-custom { display: none !important; }
    #main-content { margin-left: 0 !important; }
    body { background: white !important; }
    .card-lms { border: none !important; box-shadow: none !important; }
}
</style>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
