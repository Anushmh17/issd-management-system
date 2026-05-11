<?php
// =====================================================
// ISSD Management - Admin: Reports & Analytics
// =====================================================
define('PAGE_TITLE', 'Reports & Analytics');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

// Monthly Revenue (last 6 months)
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_paid) as total
    FROM student_payments
    WHERE status IN ('paid', 'partial')
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Top courses by enrollment
$courseEnrollments = $pdo->query("
    SELECT c.course_name as title, COUNT(e.id) as cnt
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    GROUP BY c.id
    ORDER BY cnt DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Quick counts
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalLecturers = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$totalCourses   = $pdo->query("SELECT COUNT(*) FROM courses WHERE status='active'")->fetchColumn();
$totalEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$totalNotices   = $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM student_payments WHERE status IN ('paid', 'partial')")->fetchColumn();

// Max monthly revenue for bar chart scaling
$maxRevenue = !empty($monthlyRevenue) ? max($monthlyRevenue) : 1;
// Max enrollment for progress bar scaling
$maxEnrollment = !empty($courseEnrollments) ? max($courseEnrollments) : 1;

// NEW: Student registration trend (last 6 months)
$regTrends = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt
    FROM students
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. NEW: Comparative Performance Data
$thisMonthStr = date('Y-m');
$lastMonthStr = date('Y-m', strtotime('first day of last month'));

$thisMonthRevenue = $pdo->query("SELECT SUM(amount_paid) FROM student_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$thisMonthStr'")->fetchColumn() ?: 0;
$lastMonthRevenue = $pdo->query("SELECT SUM(amount_paid) FROM student_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$lastMonthStr'")->fetchColumn() ?: 0;

$thisMonthReg = $pdo->query("SELECT COUNT(*) FROM students WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonthStr'")->fetchColumn() ?: 0;
$lastMonthReg = $pdo->query("SELECT COUNT(*) FROM students WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonthStr'")->fetchColumn() ?: 0;

$totalLecturerPay = $pdo->query("SELECT SUM(amount) FROM lecturer_payments")->fetchColumn() ?: 0;
$netBalance = $totalRevenue - $totalLecturerPay;

// Trends
$revTrend = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
$regTrendVal = $lastMonthReg > 0 ? (($thisMonthReg - $lastMonthReg) / $lastMonthReg) * 100 : 0;

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';

// Prepare Chart Data
$chartRevenue = array_reverse($monthlyRevenue, true);
$revenueLabels = array_map(function($m) { return date('M Y', strtotime($m.'-01')); }, array_keys($chartRevenue));
$revenueValues = array_values($chartRevenue);

$chartReg = array_reverse($regTrends, true);
$regLabels = array_map(function($m) { return date('M Y', strtotime($m.'-01')); }, array_keys($chartReg));
$regValues = array_values($chartReg);

$courseLabels = array_keys($courseEnrollments);
$courseValues = array_values($courseEnrollments);
?>

<style>
  /* Fix for the revenue table alignment */
  .revenue-table { width: 100%; border-collapse: separate; border-spacing: 0; }
  .revenue-table thead th {
    background: var(--accent-indigo, #6366f1);
    color: #fff;
    padding: 14px 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: none;
  }
  .revenue-table thead th:first-child { border-radius: 14px 0 0 14px; }
  .revenue-table thead th:last-child { border-radius: 0 14px 14px 0; }
  .revenue-table tbody td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-light, #f1f5f9);
    font-size: 14px;
    color: var(--text-main, #1e293b);
  }
  .revenue-table tr:last-child td { border-bottom: none; }
  
  .stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
    background: var(--bg-input, #f8fafc);
    color: var(--text-muted, #64748b);
  }
  .stat-icon.indigo { background: #e0e7ff; color: #4f46e5; }
  .stat-icon.emerald { background: #dcfce7; color: #059669; }
  .stat-icon.amber { background: #fef3c7; color: #d97706; }
  .stat-icon.teal { background: #ccfbf1; color: #0d9488; }

  /* Dark mode overrides */
  body.lms-dark-mode .revenue-table tbody td {
    border-bottom-color: rgba(255,255,255,0.05);
  }
  body.lms-dark-mode .stat-icon:not([class*="indigo"]):not([class*="emerald"]):not([class*="amber"]):not([class*="teal"]) {
    background: rgba(255,255,255,0.05);
    color: #94a3b8;
  }
  body.lms-dark-mode .bento-card h3 {
    color: #fff !important;
  }
  body.lms-dark-mode .rpt-kpi,
  body.lms-dark-mode .rpt-card,
  body.lms-dark-mode .rpt-insight {
    background: rgba(30, 41, 59, 0.4) !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
  }
  body.lms-dark-mode .rpt-bar-track,
  body.lms-dark-mode .rpt-progress-track {
    background: rgba(255, 255, 255, 0.05) !important;
  }


</style>

<div id="page-content">

  <!-- Print-Only Header (Letterhead) -->
  <div class="print-header-lms d-none d-print-block">
    <div class="d-flex align-items-center justify-content-between border-bottom border-4 pb-4 mb-5" style="border-color: #1e4d4d !important;">
      <div class="d-flex align-items-center gap-4">
        <div class="print-logo-insignia" style="width: 65px; height: 65px; background: #1e4d4d; display: flex; align-items: center; justify-content: center; border-radius: 14px; color: #fff; font-weight: 900; font-size: 32px; box-shadow: 0 5px 15px rgba(30, 77, 77, 0.2);">I</div>
        <div>
            <h1 class="fw-900 m-0" style="color:#1e4d4d; font-size: 32px; letter-spacing: -1.5px; line-height: 1;">ISSD <span style="font-weight: 400; color: #94a3b8;">SYSTEM</span></h1>
            <p class="text-muted m-0 fw-700" style="font-size: 11px; text-transform: uppercase; letter-spacing: 2.5px; margin-top: 6px;">Institute of Software Skills Development</p>
            <div style="display: inline-block; background: #f1f5f9; color: #475569; padding: 3px 12px; border-radius: 50px; font-size: 9px; font-weight: 800; letter-spacing: 1px; margin-top: 8px; border: 1px solid #e2e8f0;">ANALYTICS & REPORTING DIVISION</div>
        </div>
      </div>
      <div class="text-end" style="max-width: 300px;">
        <h4 class="fw-800 text-uppercase mb-1" style="font-size: 14px; color: #1e4d4d; letter-spacing: 0.5px; line-height: 1.2;">Institutional Performance Report</h4>
        <div class="text-muted fw-700 mb-1" style="font-size:10px; color: #64748b !important;">REF: ISSD/REP/<?= date('Y/m') ?>/<?= str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) ?></div>
        <div class="text-muted" style="font-size:10px;">Generated: <?= date('F d, Y') ?> &bull; <?= date('h:i A') ?></div>
      </div>
    </div>
  </div>

  <!-- Page Header (Web View) -->
  <div class="page-header d-print-none">
    <div class="page-header-left">
      <h1><i class="fas fa-chart-pie text-primary me-2"></i>Reports &amp; Analytics</h1>
      <p class="text-muted">Analyze institute performance and financial trends.</p>
    </div>
    <button onclick="window.print()" class="btn-lms btn-outline"><i class="fas fa-print"></i> Print Report</button>
  </div>

  <div id="report-printable">

    <!-- ===================== DESKTOP: original layout ===================== -->
    <div class="reports-desktop">
      <div class="row g-3 mb-3">
        <!-- Highlight Cards -->
        <div class="col-md-3">
          <div class="bento-card text-center shadow-sm">
            <div class="stat-icon indigo mx-auto">
              <i class="fas fa-user-graduate"></i>
            </div>

            <h3 class="fw-800 m-0" style="color:#0f172a; font-size:28px;"><?= $totalStudents ?></h3>
            <div class="text-muted" style="font-size:12px; font-weight:700; text-transform:uppercase; margin-top:5px;">Total Students</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bento-card text-center shadow-sm">
            <div class="stat-icon emerald mx-auto">
              <i class="fas fa-chalkboard-user"></i>
            </div>

            <h3 class="fw-800 m-0" style="color:#0f172a; font-size:28px;"><?= $totalLecturers ?></h3>
            <div class="text-muted" style="font-size:12px; font-weight:700; text-transform:uppercase; margin-top:5px;">Total Lecturers</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bento-card text-center shadow-sm">
            <div class="stat-icon amber mx-auto">
              <i class="fas fa-book"></i>
            </div>

            <h3 class="fw-800 m-0" style="color:#0f172a; font-size:28px;"><?= $totalCourses ?></h3>
            <div class="text-muted" style="font-size:12px; font-weight:700; text-transform:uppercase; margin-top:5px;">Total Courses</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="bento-card text-center shadow-sm" style="border: 1px solid #10b981;">
            <div class="stat-icon teal mx-auto">
              <i class="fas fa-wallet"></i>
            </div>

            <h3 class="fw-800 m-0" style="color:#0f172a; font-size:24px;">Rs. <?= number_format($totalRevenue,0) ?></h3>
            <div class="text-muted" style="font-size:11px; font-weight:700; text-transform:uppercase; margin-top:5px;">Total Revenue</div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-2">
        <!-- Monthly Revenue Table -->
        <div class="col-md-6">
          <div class="bento-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h3 class="fw-800 m-0" style="font-size:18px;"><i class="fas fa-chart-line text-success me-2"></i> Monthly Revenue</h3>
              <span class="badge bg-success-subtle text-success rounded-pill px-3 d-print-none" style="font-size:10px;">LAST 6 MONTHS</span>
            </div>
            <div class="p-0" style="height: 220px; position: relative;">
               <canvas id="revenueChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Student Registration Trends -->
        <div class="col-md-6">
          <div class="bento-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h3 class="fw-800 m-0" style="font-size:18px;"><i class="fas fa-users text-primary me-2"></i> Registration Trends</h3>
              <span class="badge bg-primary-subtle text-primary rounded-pill px-3 d-print-none" style="font-size:10px;">MONTHLY</span>
            </div>
            <div class="p-0" style="height: 220px; position: relative;">
               <canvas id="registrationChart"></canvas>
            </div>
          </div>
        </div>
      </div><!-- /row -->

      <!-- NEW: Comparative Performance Dashboard -->
      <div class="row g-3 mb-2">
        <div class="col-md-12">
           <div class="bento-card" style="background: linear-gradient(135deg, rgba(30, 77, 77, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);">
              <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-800 m-0" style="font-size:18px;"><i class="fas fa-chart-line text-primary me-2"></i> Institutional Growth & Financial Summary</h3>
                <span class="badge bg-primary-grad text-white rounded-pill px-3 py-1" style="font-size:10px; background:var(--grad-primary);">COMPARATIVE ANALYTICS</span>
              </div>
              
              <div class="row g-4">
                  <!-- Revenue Comparison -->
                  <div class="col-md-4">
                      <div class="p-3 rounded-4 border shadow-sm h-100" style="background: var(--primary-light); border-color: var(--border-color) !important;">
                          <div class="text-muted small fw-800 text-uppercase mb-2" style="letter-spacing:1px; font-size: 10px;">Revenue Growth</div>
                          <div class="d-flex align-items-end gap-2 mb-1">
                              <h4 class="fw-900 m-0" style="font-size:24px; color: var(--text-main);">Rs. <?= number_format($thisMonthRevenue) ?></h4>
                              <span class="badge bg-<?= $revTrend >= 0 ? 'success' : 'danger' ?>-subtle text-<?= $revTrend >= 0 ? 'success' : 'danger' ?> rounded-pill" style="font-size:11px; padding: 4px 10px; border: 1px solid currentColor;">
                                  <i class="fas fa-arrow-<?= $revTrend >= 0 ? 'up' : 'down' ?> me-1"></i><?= round(abs($revTrend), 1) ?>%
                              </span>
                          </div>
                          <div class="text-muted" style="font-size:11px; font-weight:600;">vs Rs. <?= number_format($lastMonthRevenue) ?> prev</div>
                      </div>
                  </div>

                  <!-- Registration Comparison -->
                  <div class="col-md-4">
                      <div class="p-3 rounded-4 border shadow-sm h-100" style="background: var(--primary-light); border-color: var(--border-color) !important;">
                          <div class="text-muted small fw-800 text-uppercase mb-2" style="letter-spacing:1px; font-size: 10px;">Student Intake</div>
                          <div class="d-flex align-items-end gap-2 mb-1">
                              <h4 class="fw-900 m-0" style="font-size:24px; color: var(--text-main);"><?= $thisMonthReg ?> <small style="font-size:12px; font-weight:700; opacity:0.8;">New</small></h4>
                              <span class="badge bg-<?= $regTrendVal >= 0 ? 'success' : 'danger' ?>-subtle text-<?= $regTrendVal >= 0 ? 'success' : 'danger' ?> rounded-pill" style="font-size:11px; padding: 4px 10px; border: 1px solid currentColor;">
                                  <i class="fas fa-arrow-<?= $regTrendVal >= 0 ? 'up' : 'down' ?> me-1"></i><?= round(abs($regTrendVal), 1) ?>%
                              </span>
                          </div>
                          <div class="text-muted" style="font-size:11px; font-weight:600;">vs <?= $lastMonthReg ?> prev month</div>
                      </div>
                  </div>

                  <!-- Financial Health -->
                  <div class="col-md-4">
                      <div class="p-3 rounded-4 border shadow-sm h-100" style="background: var(--primary-light); border-color: var(--border-color) !important;">
                          <div class="text-muted small fw-800 text-uppercase mb-2" style="letter-spacing:1px; font-size: 10px;">Lecturer Payouts</div>
                          <div class="d-flex align-items-end gap-2 mb-1">
                              <h4 class="fw-900 m-0" style="font-size:22px; color: var(--danger);">Rs. <?= number_format($totalLecturerPay) ?></h4>
                          </div>
                          <div class="text-muted" style="font-size:11px; font-weight:600;">System-wide professional fees</div>
                      </div>
                  </div>

                  <!-- Summary Stats Row -->
                  <div class="col-md-6">
                      <?php 
                        $isProfit = $netBalance >= 0;
                        $balanceGrad = $isProfit ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' : 'linear-gradient(135deg, #be123c 0%, #fb7185 100%)';
                        $balanceIcon = $isProfit ? 'fa-vault' : 'fa-triangle-exclamation';
                      ?>
                      <div class="p-3 rounded-4 shadow-lg d-flex align-items-center justify-content-between" 
                           style="background: <?= $balanceGrad ?>; border:none; color: #fff;">
                          <div>
                              <div class="opacity-75 small fw-800 text-uppercase" style="letter-spacing:1px; font-size:10px;">Net Institutional Balance</div>
                              <h3 class="fw-900 m-0" style="font-size:28px;">Rs. <?= number_format($netBalance) ?></h3>
                          </div>
                          <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                              <i class="fas <?= $balanceIcon ?> fa-xl"></i>
                          </div>
                      </div>
                  </div>

                  <div class="col-md-6">
                      <div class="p-3 rounded-4 border shadow-lg d-flex align-items-center justify-content-between" 
                           style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border:none; color: #fff;">
                          <div>
                              <div class="opacity-75 small fw-800 text-uppercase" style="letter-spacing:1px; font-size:10px;">Total System Reach</div>
                              <h3 class="fw-900 m-0" style="font-size:28px;"><?= number_format($totalStudents + $totalLecturers) ?> <small style="font-size:14px; opacity:0.8;">Active Users</small></h3>
                          </div>
                          <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                              <i class="fas fa-globe fa-xl"></i>
                          </div>
                      </div>
                  </div>
              </div>
           </div>
        </div>
      </div>

      <!-- Course Distribution -->
      <div class="row g-3 mb-2">
        <div class="col-md-12">
          <div class="bento-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h3 class="fw-800 m-0" style="font-size:18px;"><i class="fas fa-ranking-star text-info me-2"></i> Course Distribution (Top 5)</h3>
              <span class="badge bg-info-subtle text-info rounded-pill px-3" style="font-size:10px;">MARKET SHARE</span>
            </div>
            <div class="p-0">
               <div class="row align-items-center">
                  <div class="col-md-5">
                      <div style="height: 200px; position: relative;">
                          <canvas id="courseChart"></canvas>
                      </div>
                  </div>
                  <div class="col-md-7">
                      <div class="row g-2">
                        <?php 
                        $colors = ['#5B4EFA','#00C9A7','#4CC9F0','#FF9F43','#FF6B6B'];
                        $i = 0;
                        foreach($courseEnrollments as $course => $cnt): 
                            $color = $colors[$i % count($colors)];
                        ?>
                        <div class="col-12 mb-2">
                            <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: rgba(0,0,0,0.02); border-left: 4px solid <?= $color ?>;">
                                <div class="fw-700 small"><?= htmlspecialchars($course) ?></div>
                                <div class="fw-800 text-info"><?= $cnt ?> <span class="text-muted" style="font-size:9px;">Students</span></div>
                            </div>
                        </div>
                        <?php $i++; endforeach; ?>
                      </div>
                  </div>
               </div>
            </div>
          </div>
        </div>
      </div>
      </div>
 
       <!-- Print-Only Signature Footer -->
       <div class="print-footer-lms d-none d-print-block mt-2">
         <div class="row mt-0 pt-0">
            <div class="col-4 text-center">
               <div style="border-top: 1.5px solid #000; padding-top: 5px; width: 80%; margin: 0 auto; font-weight:700;">Accountant / Prepared By</div>
               <div class="text-muted mt-1" style="font-size:9px;">Date: ________________</div>
            </div>
            <div class="col-4 text-center">
               <div style="border-top: 1.5px solid #000; padding-top: 5px; width: 80%; margin: 0 auto; font-weight:700;">Director / Approved By</div>
               <div class="text-muted mt-1" style="font-size:9px;">Date: ________________</div>
            </div>
            <div class="col-4 text-center">
               <div style="border-top: 1.5px solid #000; padding-top: 5px; width: 80%; margin: 0 auto; font-weight:700;">Official Institute Stamp</div>
               <div class="text-muted mt-1" style="font-size:9px;">(Confidential)</div>
            </div>
         </div>
      </div>
    </div><!-- /reports-desktop -->


    <!-- ===================== MOBILE: redesigned layout ===================== -->
    <div class="reports-mobile">

      <!-- KPI strip -->
      <div class="rpt-kpi-strip">
        <div class="rpt-kpi">
          <div class="rpt-kpi-icon" style="background:linear-gradient(135deg,#5B4EFA,#7c6fff);">
            <i class="fas fa-user-graduate"></i>
          </div>
          <div class="rpt-kpi-val"><?= $totalStudents ?></div>
          <div class="rpt-kpi-lbl">Students</div>
        </div>
        <div class="rpt-kpi">
          <div class="rpt-kpi-icon" style="background:linear-gradient(135deg,#00C9A7,#00a386);">
            <i class="fas fa-chalkboard-user"></i>
          </div>
          <div class="rpt-kpi-val"><?= $totalLecturers ?></div>
          <div class="rpt-kpi-lbl">Lecturers</div>
        </div>
        <div class="rpt-kpi">
          <div class="rpt-kpi-icon" style="background:linear-gradient(135deg,#4CC9F0,#1da1db);">
            <i class="fas fa-book-open"></i>
          </div>
          <div class="rpt-kpi-val"><?= $totalCourses ?></div>
          <div class="rpt-kpi-lbl">Courses</div>
        </div>
        <div class="rpt-kpi">
          <div class="rpt-kpi-icon" style="background:linear-gradient(135deg,#FF9F43,#e08800);">
            <i class="fas fa-list-check"></i>
          </div>
          <div class="rpt-kpi-val"><?= $totalEnrollments ?></div>
          <div class="rpt-kpi-lbl">Enrolled</div>
        </div>
      </div>

      <!-- Revenue Hero Card -->
      <div class="rpt-hero-card">
        <div class="rpt-hero-bg"></div>
        <div class="rpt-hero-label"><i class="fas fa-coins"></i> Total Revenue Collected</div>
        <div class="rpt-hero-value">Rs.<?= number_format($totalRevenue, 0) ?></div>
        <div class="rpt-hero-sub">
          <span><i class="fas fa-arrow-trend-up"></i> From <?= count($monthlyRevenue) ?> active months</span>
          <span><?= $totalEnrollments ?> total enrollments</span>
        </div>
      </div>

      <!-- Monthly Revenue Bar Chart -->
      <div class="rpt-card">
        <div class="rpt-card-header">
          <div class="rpt-card-title"><i class="fas fa-chart-bar"></i> Monthly Revenue</div>
          <span class="rpt-card-badge">Last 6 Months</span>
        </div>
        <div class="rpt-card-body">
          <?php if(empty($monthlyRevenue)): ?>
            <div class="rpt-empty"><i class="fas fa-chart-bar"></i><p>No revenue data yet.</p></div>
          <?php else: ?>
            <div class="rpt-bar-chart">
              <?php foreach(array_reverse($monthlyRevenue, true) as $month => $total):
                $pct = $maxRevenue > 0 ? round(($total / $maxRevenue) * 100) : 0;
                $label = date('M y', strtotime($month.'-01'));
              ?>
              <div class="rpt-bar-row">
                <div class="rpt-bar-label"><?= $label ?></div>
                <div class="rpt-bar-track">
                  <div class="rpt-bar-fill" style="width:<?= $pct ?>%;" data-pct="<?= $pct ?>"></div>
                </div>
                <div class="rpt-bar-amount">Rs.<?= number_format($total,0) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Top Courses by Enrollment -->
      <div class="rpt-card">
        <div class="rpt-card-header">
          <div class="rpt-card-title"><i class="fas fa-ranking-star"></i> Top Courses</div>
          <span class="rpt-card-badge">By Enrollment</span>
        </div>
        <div class="rpt-card-body">
          <?php if(empty($courseEnrollments)): ?>
            <div class="rpt-empty"><i class="fas fa-book"></i><p>No enrollment data yet.</p></div>
          <?php else:
            $colors = ['#5B4EFA','#00C9A7','#4CC9F0','#FF9F43','#FF6B6B'];
            $i = 0;
            foreach($courseEnrollments as $course => $cnt):
              $pct = $maxEnrollment > 0 ? round(($cnt / $maxEnrollment) * 100) : 0;
              $color = $colors[$i % count($colors)];
          ?>
          <div class="rpt-course-row">
            <div class="rpt-course-top">
              <div class="rpt-course-rank" style="background:<?= $color ?>1a;color:<?= $color ?>;"><?= $i+1 ?></div>
              <div class="rpt-course-name"><?= htmlspecialchars($course) ?></div>
              <div class="rpt-course-count" style="color:<?= $color ?>;"><?= $cnt ?> <span>students</span></div>
            </div>
            <div class="rpt-progress-track">
              <div class="rpt-progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
            </div>
          </div>
          <?php $i++; endforeach; endif; ?>
        </div>
      </div>

      <!-- Quick Stats Pills -->
      <div class="rpt-card">
        <div class="rpt-card-header">
          <div class="rpt-card-title"><i class="fas fa-bolt"></i> Quick Insights</div>
        </div>
        <div class="rpt-card-body" style="padding-top:10px;">
          <div class="rpt-insight-grid">
          <a href="../../admin/notices" class="rpt-insight" style="text-decoration:none;">
              <i class="fas fa-bell" style="color:#FF9F43;"></i>
              <div class="rpt-insight-val"><?= $totalNotices ?></div>
              <div class="rpt-insight-lbl">Notices Posted</div>
            </a>
            <a href="../../admin/payments" class="rpt-insight" style="text-decoration:none;">
              <i class="fas fa-dollar-sign" style="color:#00C9A7;"></i>
              <div class="rpt-insight-val"><?= !empty($monthlyRevenue) ? 'Rs.'.number_format(array_values($monthlyRevenue)[0],0) : '""' ?></div>
              <div class="rpt-insight-lbl">This Month</div>
            </a>
            <a href="../../admin/courses" class="rpt-insight" style="text-decoration:none;">
              <i class="fas fa-trophy" style="color:#5B4EFA;"></i>
              <div class="rpt-insight-val"><?= !empty($courseEnrollments) ? array_key_first($courseEnrollments) : '""' ?></div>
              <div class="rpt-insight-lbl">Top Course</div>
            </a>
            <a href="../../admin/enrollments" class="rpt-insight" style="text-decoration:none;">
              <i class="fas fa-chart-line" style="color:#FF6B6B;"></i>
              <div class="rpt-insight-val"><?= $totalEnrollments > 0 && $totalStudents > 0 ? round($totalEnrollments/$totalStudents,1) : 0 ?>x</div>
              <div class="rpt-insight-lbl">Avg Courses/Student</div>
            </a>
          </div>
        </div>
      </div>

    </div><!-- /reports-mobile -->

  </div><!-- /report-printable -->
</div>

<style>
/* ============================================================
   REPORTS PAGE "" MOBILE-ONLY REDESIGN (â‰¤768px)
   Desktop is completely untouched above this block
   ============================================================ */

/* Hide mobile layout on desktop, hide desktop layout on mobile */
.reports-mobile { display: none; }
.reports-desktop { display: block; }

@media (max-width: 768px) {
  .reports-desktop { display: none !important; }
  .reports-mobile  { display: block; }

  /* â"€â"€ KPI Strip â"€â"€ */
  .rpt-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 16px;
  }
  .rpt-kpi {
    background: #fff;
    border-radius: 14px;
    padding: 14px 8px 12px;
    text-align: center;
    border: 1.5px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s;
  }
  .rpt-kpi:active { transform: scale(0.95); }
  .rpt-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    margin-bottom: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
  }
  .rpt-kpi-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1;
  }
  .rpt-kpi-lbl {
    font-size: 9px;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
  }

  /* â"€â"€ Revenue Hero Card â"€â"€ */
  .rpt-hero-card {
    position: relative;
    background: linear-gradient(135deg, #5B4EFA 0%, #4338e0 50%, #00C9A7 100%);
    border-radius: 20px;
    padding: 24px 20px 20px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(91,78,250,0.35);
  }
  .rpt-hero-bg {
    position: absolute;
    top: -40px; right: -40px;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
  }
  .rpt-hero-bg::after {
    content: '';
    position: absolute;
    top: 30px; left: 30px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
  }
  .rpt-hero-label {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,0.75);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .rpt-hero-value {
    font-size: 34px;
    font-weight: 800;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    line-height: 1.1;
    margin-bottom: 12px;
  }
  .rpt-hero-sub {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: rgba(255,255,255,0.65);
    font-weight: 500;
  }
  .rpt-hero-sub span { display: flex; align-items: center; gap: 4px; }

  /* â"€â"€ Generic Card â"€â"€ */
  .rpt-card {
    background: #fff;
    border-radius: 18px;
    border: 1.5px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    margin-bottom: 16px;
    overflow: hidden;
  }
  .rpt-card-header {
    padding: 16px 18px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .rpt-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-main);
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .rpt-card-title i { color: var(--primary); }
  .rpt-card-badge {
    font-size: 10px;
    font-weight: 700;
    background: var(--primary-light);
    color: var(--primary);
    padding: 3px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }
  .rpt-card-body {
    padding: 16px 18px 18px;
  }

  /* â"€â"€ Revenue Bar Chart â"€â"€ */
  .rpt-bar-chart { display: flex; flex-direction: column; gap: 14px; }
  .rpt-bar-row { display: flex; align-items: center; gap: 10px; }
  .rpt-bar-label {
    width: 46px;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    flex-shrink: 0;
    text-align: right;
  }
  .rpt-bar-track {
    flex: 1;
    height: 10px;
    background: var(--border-color);
    border-radius: 99px;
    overflow: hidden;
  }
  .rpt-bar-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    width: 0;
    transition: width 0.9s cubic-bezier(0.4,0,0.2,1);
  }
  .rpt-bar-amount {
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
    white-space: nowrap;
    width: 70px;
    text-align: right;
    flex-shrink: 0;
  }

  /* â"€â"€ Course Progress Rows â"€â"€ */
  .rpt-course-row { margin-bottom: 18px; }
  .rpt-course-row:last-child { margin-bottom: 0; }
  .rpt-course-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 7px;
  }
  .rpt-course-rank {
    width: 26px; height: 26px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    font-weight: 800;
    flex-shrink: 0;
  }
  .rpt-course-name {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
    line-height: 1.3;
  }
  .rpt-course-count {
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .rpt-course-count span {
    font-size: 10px;
    font-weight: 500;
    color: var(--text-muted);
  }
  .rpt-progress-track {
    height: 6px;
    background: var(--border-color);
    border-radius: 99px;
    overflow: hidden;
  }
  .rpt-progress-fill {
    height: 100%;
    border-radius: 99px;
    width: 0;
    transition: width 1s cubic-bezier(0.4,0,0.2,1) 0.2s;
    opacity: 0.85;
  }

  /* â"€â"€ Quick Insights Grid â"€â"€ */
  .rpt-insight-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .rpt-insight {
    background: var(--bg-page);
    border-radius: 14px;
    padding: 16px 14px;
    text-align: center;
    border: 1.5px solid var(--border-color);
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    display: block;
  }
  .rpt-insight:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border-color: var(--primary);
  }
  .rpt-insight:active { transform: scale(0.95); }
  .rpt-insight i { font-size: 22px; margin-bottom: 8px; display: block; }
  .rpt-insight-val {
    font-size: 15px;
    font-weight: 800;
    color: var(--text-main);
    word-break: break-word;
    line-height: 1.2;
    margin-bottom: 4px;
  }
  .rpt-insight-lbl {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  /* â"€â"€ Empty State â"€â"€ */
  .rpt-empty {
    text-align: center;
    padding: 30px 20px;
    color: var(--text-muted);
  }
  .rpt-empty i { font-size: 36px; margin-bottom: 10px; opacity: 0.3; display: block; }
  .rpt-empty p { font-size: 13px; margin: 0; }
}

/* ── Print Styles ── */
@media print {
  #sidebar, #top-navbar, .btn-lms, .breadcrumb-custom, .page-header-left p { display: none !important; }
  #page-content { margin: 0 !important; padding: 0 !important; }
  #main-content { margin-left: 0 !important; padding: 0 !important; }
  body { background: white !important; font-size: 10px; color: #000; }
  
  .reports-desktop { display: block !important; }
  .reports-mobile { display: none !important; }
  
  /* Grid System for Print */
  .reports-desktop .row { display: flex !important; flex-wrap: wrap !important; margin: 0 -10px !important; }
  .reports-desktop .col-md-3 { width: 25% !important; padding: 0 10px !important; }
  .reports-desktop .col-md-6 { width: 50% !important; padding: 0 10px !important; }
  .reports-desktop .col-md-12 { width: 100% !important; padding: 0 10px !important; }
  
  .bento-card {
    padding: 8px !important;
    border: 1px solid #eee !important;
    border-radius: 6px !important;
    background: #fff !important;
    box-shadow: none !important;
    color: #000 !important;
  }
  
  /* Remove heavy backgrounds for ink efficiency */
  .col-md-3 .bento-card[style*="background"] {
    background: #f8fafc !important;
    color: #000 !important;
    border: 2px solid #10b981 !important;
  }
  
  .stat-icon {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #ddd !important;
    width: 32px !important;
    height: 32px !important;
  }
  
  h3 { font-size: 18px !important; margin-bottom: 5px !important; color: #000 !important; }
  .text-muted { color: #64748b !important; }
  
  .revenue-table thead th { 
    background: #f1f5f9 !important; 
    color: #1e293b !important; 
    border-bottom: 2px solid #cbd5e1 !important;
    padding: 6px 10px !important;
  }
  .revenue-table tbody td { padding: 6px 10px !important; border-bottom: 1px solid #f1f5f9 !important; }
  
  .badge { border: 1px solid #ddd !important; background: #fff !important; color: #000 !important; }
  
  @page {
    margin: 0;
    size: auto;
  }
  html, body {
    height: 100% !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  #app-wrapper, #main-content, #page-content {
    height: 100% !important;
    min-height: 0 !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: hidden !important;
  }
  #report-printable {
    zoom: 100% !important;
    width: 100% !important;
    max-height: 29cm !important;
    overflow: hidden !important;
    page-break-after: avoid !important;
    break-after: avoid !important;
  }
  .bento-card {
    page-break-inside: avoid !important;
    height: auto !important;
    margin-bottom: 5px !important;
  }
  .row {
    margin-bottom: 5px !important;
  }
  #page-footer, .reports-mobile, .no-print, .toast-container-lms, .modal {
    display: none !important;
    height: 0 !important;
    visibility: hidden !important;
  }
}
</style>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shared Chart Configurations
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    const ctxReg = document.getElementById('registrationChart').getContext('2d');
    const ctxCourse = document.getElementById('courseChart').getContext('2d');

    const chartColors = ['#5B4EFA', '#00C9A7', '#4CC9F0', '#FF9F43', '#FF6B6B'];

    // 1. Revenue Bar Chart
    new Chart(ctxRevenue, {
        type: 'bar',
        data: {
            labels: <?= json_encode($revenueLabels) ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?= json_encode($revenueValues) ?>,
                backgroundColor: 'rgba(34, 211, 238, 0.7)',
                borderColor: '#22d3ee',
                borderWidth: 1,
                borderRadius: 8,
                hoverBackgroundColor: '#22d3ee'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12, 
                    displayColors: false
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } }
                },
                x: { 
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } }
                }
            }
        }
    });

    // 2. Registration Trend Chart (Line)
    new Chart(ctxReg, {
        type: 'line',
        data: {
            labels: <?= json_encode($regLabels) ?>,
            datasets: [{
                label: 'New Students',
                data: <?= json_encode($regValues) ?>,
                borderColor: '#f43f5e',
                backgroundColor: 'rgba(244, 63, 94, 0.1)',
                fill: true,
                tension: 0.5,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#f43f5e',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 12 }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    ticks: { color: '#94a3b8', font: { size: 10, weight: '600' }, stepSize: 1 }
                },
                x: { 
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } }
                }
            }
        }
    });

    // 3. Course Distribution Doughnut Chart
    new Chart(ctxCourse, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($courseLabels) ?>,
            datasets: [{
                data: <?= json_encode($courseValues) ?>,
                backgroundColor: ['#22d3ee', '#34d399', '#f43f5e', '#fbbf24', '#818cf8'],
                borderWidth: 3,
                borderColor: 'rgba(255,255,255,0.1)',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', padding: 12 }
            }
        }
    });

    // Trigger animation for any CSS bars if still present
    setTimeout(function() {
        document.querySelectorAll('.rpt-bar-fill, .rpt-progress-fill').forEach(function(el) {
            var target = el.style.width;
            el.style.width = '0';
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    el.style.width = target;
                });
            });
        });
    }, 200);
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

