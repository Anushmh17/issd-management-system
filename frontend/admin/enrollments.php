<?php
// =====================================================
// ISSD Management - Admin: Enrollments Management (Enhanced)
// =====================================================
define('PAGE_TITLE', 'Enrollments');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$error = '';

// --- Handle Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $course_id = (int)($_POST['course_id'] ?? 0);
        $status = $_POST['status'] ?? 'ongoing';
        $start_date = $_POST['start_date'] ?? date('Y-m-d');

        if (!$student_id || !$course_id) {
            $error = 'Student and Course are required.';
        } else {
            try {
                if ($act === 'add') {
                    $pdo->prepare("INSERT INTO student_courses (student_id, course_id, status, start_date) VALUES (?, ?, ?, ?)")
                        ->execute([$student_id, $course_id, $status, $start_date]);
                    setFlash('success', 'Enrollment created successfully.');
                } else {
                    $id = (int)$_POST['id'];
                    $pdo->prepare("UPDATE student_courses SET student_id=?, course_id=?, status=?, start_date=? WHERE id=?")
                        ->execute([$student_id, $course_id, $status, $start_date, $id]);
                    setFlash('success', 'Enrollment updated successfully.');
                }
                header('Location: enrollments.php'); exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'This student is already enrolled in this course.';
                } else {
                    $error = 'Failed to save enrollment.';
                }
            }
        }
    }

    if ($act === 'delete') {
        $pdo->prepare("DELETE FROM student_courses WHERE id=?")->execute([(int)$_POST['id']]);
        setFlash('success', 'Enrollment deleted.');
        header('Location: enrollments.php'); exit;
    }
}

// --- Fetch Stats ---
$stats = $pdo->query("SELECT 
    COUNT(CASE WHEN status='ongoing' THEN 1 END) as ongoing,
    COUNT(CASE WHEN status='completed' THEN 1 END) as completed,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_30
FROM student_courses")->fetch();

// --- Fetch Records ---
$search = trim($_GET['q'] ?? '');
$f_status = $_GET['status'] ?? '';
$f_course = $_GET['course_id'] ?? '';

$sql = "SELECT sc.*, c.course_name, c.course_code, s.full_name AS student_name, s.student_id AS student_reg,
        (SELECT p.status FROM student_payments p WHERE p.student_id=sc.student_id AND p.course_id=sc.course_id ORDER BY p.created_at DESC LIMIT 1) as last_pay_status,
        (SELECT p.balance FROM student_payments p WHERE p.student_id=sc.student_id AND p.course_id=sc.course_id ORDER BY p.created_at DESC LIMIT 1) as last_pay_balance
        FROM student_courses sc
        JOIN courses c ON c.id = sc.course_id
        JOIN students s ON s.id = sc.student_id";

$where = [];
$params = [];

if ($search) {
    $where[] = "(c.course_name LIKE ? OR c.course_code LIKE ? OR s.full_name LIKE ? OR s.student_id LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($f_status) {
    $where[] = "sc.status = ?";
    $params[] = $f_status;
}
if ($f_course) {
    $where[] = "sc.course_id = ?";
    $params[] = $f_course;
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY sc.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// --- Data for Dropdowns ---
$studentsList = $pdo->query("SELECT id, full_name, student_id FROM students WHERE status!='dropout' ORDER BY full_name")->fetchAll();
$coursesList = $pdo->query("SELECT id, course_name, course_code FROM courses WHERE status='active' ORDER BY course_name")->fetchAll();

$editEnrollment = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM student_courses WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editEnrollment = $stmt->fetch();
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Enrollment Hub</h1>
            <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Enrollments</span></div>
        </div>
        <div class="page-header-right">
            <a href="?action=add" class="btn-primary-grad shadow-sm">
                <i class="fas fa-plus"></i> New Enrollment
            </a>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-lms p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size: 13px; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Ongoing Students</div>
                        <div style="font-size: 32px; font-weight: 800; margin-top: 5px;"><?= number_format($stats['ongoing'] ?? 0) ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-lms p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size: 13px; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Completions</div>
                        <div style="font-size: 32px; font-weight: 800; margin-top: 5px;"><?= number_format($stats['completed'] ?? 0) ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-lms p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size: 13px; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">New (Last 30 Days)</div>
                        <div style="font-size: 32px; font-weight: 800; margin-top: 5px;"><?= number_format($stats['new_30'] ?? 0) ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fas fa-rocket"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section (Add/Edit) -->
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="card-lms mb-4 shadow-sm animate__animated animate__fadeInDown">
        <div class="card-lms-header bg-white border-bottom-0 py-3 px-4">
            <div class="card-lms-title fw-800" style="color: #1e293b; font-size: 18px;">
                <i class="fas <?= $action==='add'?'fa-plus-circle':'fa-edit' ?> text-primary me-2"></i>
                <?= $action==='add'?'New Enrollment':'Edit Enrollment' ?>
            </div>
        </div>
        <div class="card-lms-body px-4 pb-4">
            <form method="POST" action="enrollments.php" class="row g-4">
                <input type="hidden" name="act" value="<?= $action ?>">
                <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?= $editEnrollment['id'] ?>"><?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label fw-700 small text-uppercase text-muted" style="letter-spacing: 1px;">Student Selection</label>
                    <select name="student_id" class="form-control-lms select2-search" required>
                        <option value="">Search Student Name or ID...</option>
                        <?php foreach($studentsList as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($editEnrollment['student_id']??0) == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['full_name'] . ' [' . $s['student_id'] . ']') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-700 small text-uppercase text-muted" style="letter-spacing: 1px;">Course Selection</label>
                    <select name="course_id" class="form-control-lms select2-search" required>
                        <option value="">Search Course Name or Code...</option>
                        <?php foreach($coursesList as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($editEnrollment['course_id']??0) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-700 small text-uppercase text-muted" style="letter-spacing: 1px;">Start Date</label>
                    <input type="date" name="start_date" class="form-control-lms" value="<?= $editEnrollment['start_date'] ?? date('Y-m-d') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-700 small text-uppercase text-muted" style="letter-spacing: 1px;">Enrollment Status</label>
                    <select name="status" class="form-control-lms">
                        <option value="ongoing" <?= ($editEnrollment['status']??'')==='ongoing'?'selected':'' ?>>Ongoing</option>
                        <option value="completed" <?= ($editEnrollment['status']??'')==='completed'?'selected':'' ?>>Completed</option>
                        <option value="dropped" <?= ($editEnrollment['status']??'')==='dropped'?'selected':'' ?>>Dropped</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn-primary-grad w-100 py-2">
                        <i class="fas fa-save me-2"></i> Confirm Enrollment
                    </button>
                    <a href="enrollments.php" class="btn-lms btn-outline py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Table Section -->
    <div class="card-lms shadow-sm">
        <div class="card-lms-header d-flex flex-column gap-3 p-4 bg-white border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div class="list-legend">
                    <div class="list-legend-label">ACTIVE CLASS REGISTER</div>
                    <div class="list-legend-title fw-800" style="font-size: 22px;">Student Enrollments</div>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fw-700"><?= count($enrollments) ?> Total Records</span>
                </div>
            </div>

            <!-- Enhanced Filters -->
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group-lms" style="position:relative;">
                        <i class="fas fa-search" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color: #94a3b8; z-index:10;"></i>
                        <input type="text" name="q" class="form-control-lms ps-5" placeholder="Search student name, ID or course..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="course_id" class="form-control-lms" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        <?php foreach($coursesList as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $f_course == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control-lms" onchange="this.form.submit()">
                        <option value="">Any Status</option>
                        <option value="ongoing" <?= $f_status==='ongoing'?'selected':'' ?>>Ongoing</option>
                        <option value="completed" <?= $f_status==='completed'?'selected':'' ?>>Completed</option>
                        <option value="dropped" <?= $f_status==='dropped'?'selected':'' ?>>Dropped</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn-lms btn-primary px-3 flex-grow-1">Apply Filters</button>
                    <?php if($search || $f_status || $f_course): ?>
                        <a href="enrollments.php" class="btn-lms btn-outline"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card-lms-body p-0 table-responsive">
            <table class="table-lms searchable-table mb-0">
                <thead>
                    <tr>
                        <th style="padding-left:24px;">STUDENT & STATUS</th>
                        <th>COURSE INFO</th>
                        <th>JOIN DATE</th>
                        <th>ENROLLMENT</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrollments as $e): 
                        // Payment Health Logic
                        $payStatus = $e['last_pay_status'];
                        $payBalance = (float)($e['last_pay_balance'] ?? 0);
                        $isHealthy = ($payStatus === 'paid' && $payBalance <= 0);
                        $isNew = (empty($payStatus)); // Never paid
                    ?>
                    <tr>
                        <td style="padding-left:24px;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="position:relative;">
                                    <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center fw-800 text-primary" style="width:40px; height:40px; font-size:14px; border: 2px solid #e2e8f0;">
                                        <?= strtoupper(substr($e['student_name'], 0, 1)) ?>
                                    </div>
                                    <!-- Payment Dot -->
                                    <span class="position-absolute bottom-0 end-0 translate-middle p-1 rounded-circle border border-white" 
                                          style="background-color: <?= $isHealthy ? '#10b981' : ($isNew ? '#94a3b8' : '#ef4444') ?>; width:12px; height:12px;" 
                                          title="<?= $isHealthy ? 'Fully Paid' : ($isNew ? 'No Payment History' : 'Outstanding Balance: Rs. ' . number_format($payBalance, 2)) ?>">
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-800 text-dark" style="font-size: 14px;"><?= htmlspecialchars($e['student_name']) ?></div>
                                    <div class="small text-muted fw-600"><?= htmlspecialchars($e['student_reg']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-700 text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($e['course_name']) ?></div>
                            <div class="badge bg-primary-light text-primary small px-2 py-1 mt-1" style="font-size: 10px;"><?= htmlspecialchars($e['course_code']) ?></div>
                        </td>
                        <td>
                            <div class="fw-600 text-muted small"><i class="fas fa-calendar-day me-1 opacity-50"></i> <?= date('M d, Y', strtotime($e['start_date'])) ?></div>
                        </td>
                        <td>
                            <?php if ($e['status'] === 'ongoing'): ?>
                                <span class="badge-lms primary"><i class="fas fa-spinner fa-spin me-1"></i> Ongoing</span>
                            <?php elseif ($e['status'] === 'completed'): ?>
                                <span class="badge-lms success"><i class="fas fa-check-circle me-1"></i> Completed</span>
                            <?php else: ?>
                                <span class="badge-lms danger"><i class="fas fa-times-circle me-1"></i> Dropped</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="<?= BASE_URL ?>/admin/payments/add.php?student_id=<?= $e['student_id'] ?>&course_id=<?= $e['course_id'] ?>" 
                                   class="btn-lms btn-success btn-sm shadow-sm" title="Process Payment">
                                    <i class="fas fa-wallet"></i>
                                </a>
                                <?php if ($e['status'] === 'completed'): ?>
                                    <a href="<?= BASE_URL ?>/admin/certificates/index.php?action=generate&student_id=<?= $e['student_id'] ?>&course_id=<?= $e['course_id'] ?>" 
                                       class="btn-lms btn-primary btn-sm shadow-sm" title="Generate Certificate">
                                        <i class="fas fa-award"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=edit&id=<?= $e['id'] ?>" class="btn-lms btn-outline btn-sm shadow-sm" title="Modify Enrollment">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Archive this enrollment record?');">
                                    <input type="hidden" name="act" value="delete">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="btn-lms btn-danger btn-sm shadow-sm"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Select2 Premium Styling */
.select2-container--default .select2-selection--single {
    height: 46px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    padding: 0 10px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 44px;
}
.select2-dropdown {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    overflow: hidden;
    z-index: 10000;
}
.select2-search__field {
    border-radius: 8px !important;
}
.bg-primary-light { background-color: rgba(79, 70, 229, 0.1); }
.fw-800 { font-weight: 800; }
.shadow-sm { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important; }
</style>

<?php
$extraJS = <<<'JS'
<script>
$(document).ready(function() {
    // Initialize Select2 for searchable dropdowns
    $('.select2-search').select2({
        width: '100%'
    });

    // Auto-dismiss alerts
    setTimeout(function() {
        $('.alert-lms.auto-dismiss').fadeOut('slow');
    }, 4000);
});
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
