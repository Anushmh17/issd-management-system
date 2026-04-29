<?php
// =====================================================
// LEARN Management - Admin: Enrollments Management
// =====================================================
define('PAGE_TITLE', 'Enrollments');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$error = '';

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

$editEnrollment = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM student_courses WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editEnrollment = $stmt->fetch();
}

// Fetch records for lists and dropdowns
$search = trim($_GET['q'] ?? '');
$sql = "SELECT sc.*, c.course_name, c.course_code, s.full_name AS student_name, u.name AS lecturer_name
        FROM student_courses sc
        JOIN courses c ON c.id = sc.course_id
        JOIN students s ON s.id = sc.student_id
        LEFT JOIN course_assignments ca ON ca.course_id = c.id
        LEFT JOIN users u ON u.id = ca.lecturer_id";
$params = [];
if ($search) {
    $sql .= " WHERE c.course_name LIKE ? OR c.course_code LIKE ? OR s.full_name LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY sc.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

$students = $pdo->query("SELECT id, full_name, student_id FROM students WHERE status!='dropout' ORDER BY full_name")->fetchAll();
$courses = $pdo->query("SELECT id, course_name as title, course_code as code FROM courses WHERE status='active' ORDER BY course_name")->fetchAll();
$lecturers = $pdo->query("SELECT id, name FROM users WHERE role='lecturer' AND status='active' ORDER BY name")->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Enrollments</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Enrollments</span></div>
    </div>
    <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn-primary-grad"><i class="fas fa-plus"></i> New Enrollment</a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'add' || $action === 'edit'): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-list-check"></i> <?= $action==='add'?'Add Enrollment':'Edit Enrollment' ?></div>
      <a href="enrollments.php" class="btn-lms btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-lms-body">
      <form method="POST" action="enrollments.php">
        <input type="hidden" name="act" value="<?= $action ?>">
        <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?= $editEnrollment['id'] ?>"><?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6"><div class="form-group-lms">
            <label>Student *</label>
            <select name="student_id" class="form-control-lms" required>
              <option value="">Select Student...</option>
              <?php foreach($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($editEnrollment['student_id']??0) == $s['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['full_name'] . ' (' . $s['student_id'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div></div>
          
          <div class="col-md-6"><div class="form-group-lms">
            <label>Course *</label>
            <select name="course_id" class="form-control-lms" required>
              <option value="">Select Course...</option>
              <?php foreach($courses as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($editEnrollment['course_id']??0) == $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['title']) ?> (<?= htmlspecialchars($c['code']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div></div>
          
          <div class="col-md-6"><div class="form-group-lms">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control-lms" value="<?= $editEnrollment['start_date'] ?? date('Y-m-d') ?>">
          </div></div>
          
          <div class="col-md-6"><div class="form-group-lms">
            <label>Status</label>
            <select name="status" class="form-control-lms">
              <option value="ongoing" <?= ($editEnrollment['status']??'')==='ongoing'?'selected':'' ?>>Ongoing</option>
              <option value="completed" <?= ($editEnrollment['status']??'')==='completed'?'selected':'' ?>>Completed</option>
              <option value="dropped" <?= ($editEnrollment['status']??'')==='dropped'?'selected':'' ?>>Dropped</option>
            </select>
          </div></div>
        </div>

        <div style="margin-top:8px;display:flex;gap:10px;">
          <button type="submit" class="btn-lms btn-primary"><i class="fas fa-save"></i> <?= $action==='add'?'Save Enrollment':'Update Enrollment' ?></button>
          <a href="enrollments.php" class="btn-lms btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card-lms">
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Enrollment Management</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>All Enrollments</span>
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= count($enrollments) ?></span>
          </div>
        </div>
      </div>

      <!-- Search Row -->
      <form method="GET" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 300px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="q" placeholder="Search Student name, Course or Code…"
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-lms btn-primary px-4 rounded-3 shadow-sm" style="height: 46px; padding: 0 25px;">
          Search
        </button>
      </form>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($enrollments)): ?>
        <div class="empty-state"><i class="fas fa-list-check"></i><p>No enrollments found.</p></div>
      <?php else: ?>
      <table class="table-lms searchable-table">
        <thead>
          <tr>
            <th>Start Date</th>
            <th>Student</th>
            <th>Course</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enrollments as $e): ?>
          <tr>
            <td><?= $e['start_date'] ? date('M d, Y', strtotime($e['start_date'])) : '—' ?></td>
            <td class="fw-600">
              <a href="<?= BASE_URL ?>/admin/payments/add.php?student_id=<?= $e['student_id'] ?>&course_id=<?= $e['course_id'] ?>" 
                 style="color:inherit;text-decoration:none;" title="Click to pay">
                <?= htmlspecialchars($e['student_name']) ?>
              </a>
            </td>
            <td>
              <div><?= htmlspecialchars($e['course_name']) ?></div>
              <small class="text-muted"><?= htmlspecialchars($e['course_code']) ?></small>
            </td>
            <td>
              <span class="badge-lms <?= $e['status']==='ongoing'?'primary':($e['status']==='completed'?'success':'danger') ?>">
                <?= ucfirst($e['status']) ?>
              </span>
            </td>
            <td>
              <div class="d-flex gap-10">
                <a href="<?= BASE_URL ?>/admin/payments/add.php?student_id=<?= $e['student_id'] ?>&course_id=<?= $e['course_id'] ?>" 
                   class="btn-lms btn-success btn-sm" title="Add Payment">
                  <i class="fas fa-money-bill-wave"></i>
                </a>
                <a href="?action=edit&id=<?= $e['id'] ?>" class="btn-lms btn-outline btn-sm"><i class="fas fa-pen"></i></a>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= $e['id'] ?>">
                  <button type="submit" class="btn-lms btn-danger btn-sm" data-confirm="Delete this enrollment?"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
