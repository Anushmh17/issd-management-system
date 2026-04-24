<?php
// =====================================================
// LEARN Management - Admin: Courses Management
// =====================================================
define('PAGE_TITLE', 'Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $title = trim($_POST['course_name'] ?? '');
        $code  = strtoupper(trim($_POST['code'] ?? ''));
        $desc  = trim($_POST['description'] ?? '');
        $dur   = trim($_POST['duration'] ?? '');
        $fee   = (float)($_POST['fee'] ?? 0);
        $status= $_POST['status'] ?? 'active';

        if (!$title || !$code) { $error = 'Title and code are required.'; }
        else {
            try {
                if ($act === 'add') {
                    $pdo->prepare("INSERT INTO courses (course_name,course_code,description,duration,monthly_fee,status) VALUES (?,?,?,?,?,?)")
                        ->execute([$title, $code, $desc, $dur, $fee, $status]);
                    setFlash('success','Course added successfully.');
                } else {
                    $id = (int)$_POST['id'];
                    $pdo->prepare("UPDATE courses SET course_name=?,course_code=?,description=?,duration=?,monthly_fee=?,status=? WHERE id=?")
                        ->execute([$title, $code, $desc, $dur, $fee, $status, $id]);
                    setFlash('success','Course updated.');
                }
                header('Location: courses.php'); exit;
            } catch (PDOException $e) {
                $error = $e->getCode()==23000 ? 'Course code already exists.' : 'Failed to save.';
            }
        }
    }

    if ($act === 'delete') {
        $pdo->prepare("DELETE FROM courses WHERE id=?")->execute([(int)$_POST['id']]);
        setFlash('success','Course deleted.'); header('Location: courses.php'); exit;
    }
}

$editCourse = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editCourse = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS enrollment_count FROM courses c WHERE 1";
$params = [];
if ($search) { $sql .= " AND (c.course_name LIKE ? OR c.course_code LIKE ?)"; $params=["%$search%","%$search%"]; }
$sql .= " ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$courses = $stmt->fetchAll();

// Get lecturers for assignment
$lecturers = $pdo->query("SELECT id,name FROM users WHERE role='lecturer' AND status='active' ORDER BY name")->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Courses Management</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Courses</span></div>
    </div>
    <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn-primary-grad"><i class="fas fa-plus"></i> Add Course</a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'add' || $action === 'edit'): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-book-open"></i> <?= $action==='add'?'Add New Course':'Edit Course' ?></div>
      <a href="courses.php" class="btn-lms btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-lms-body">
      <form method="POST" action="courses.php">
        <input type="hidden" name="act" value="<?= $action ?>">
        <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?= $editCourse['id'] ?>"><?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6"><div class="form-group-lms">
            <label>Course Title *</label>
            <input type="text" name="course_name" class="form-control-lms" value="<?= htmlspecialchars($editCourse['course_name']??'') ?>" required placeholder="e.g. Web Development">
          </div></div>
          <div class="col-md-3"><div class="form-group-lms">
            <label>Course Code *</label>
            <input type="text" name="code" class="form-control-lms" value="<?= htmlspecialchars($editCourse['course_code']??'') ?>" required placeholder="e.g. WD101">
          </div></div>
          <div class="col-md-3"><div class="form-group-lms">
            <label>Duration</label>
            <input type="text" name="duration" class="form-control-lms" value="<?= htmlspecialchars($editCourse['duration']??'') ?>" placeholder="e.g. 3 Months">
          </div></div>
          <div class="col-md-3"><div class="form-group-lms">
            <label>Course Fee (Rs.)</label>
            <input type="number" name="fee" class="form-control-lms" value="<?= $editCourse['monthly_fee']??0 ?>" step="0.01" min="0">
          </div></div>
          <div class="col-md-3"><div class="form-group-lms">
            <label>Status</label>
            <select name="status" class="form-control-lms">
              <option value="active" <?= ($editCourse['status']??'')==='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= ($editCourse['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </div></div>
          <div class="col-md-6"><div class="form-group-lms">
            <label>Description</label>
            <textarea name="description" class="form-control-lms" rows="3" placeholder="Brief course description..."><?= htmlspecialchars($editCourse['description']??'') ?></textarea>
          </div></div>
        </div>

        <div style="margin-top:8px;display:flex;gap:10px;">
          <button type="submit" class="btn-lms btn-primary"><i class="fas fa-save"></i> <?= $action==='add'?'Add Course':'Update Course' ?></button>
          <a href="courses.php" class="btn-lms btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Course Cards -->
  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-book-open"></i> All Courses (<?= count($courses) ?>)</div>
      <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search courses..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-lms btn-primary btn-sm">Search</button>
      </form>
    </div>
    <div class="card-lms-body" style="padding:20px;">
      <?php if (empty($courses)): ?>
        <div class="empty-state"><i class="fas fa-book"></i><p>No courses found.</p></div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($courses as $c): ?>
        <div class="col-md-6 col-lg-4">
          <div style="border:1.5px solid var(--border-color);border-radius:var(--radius-md);padding:20px;background:var(--bg-page);transition:var(--transition);position:relative;overflow:hidden;"
               onmouseover="this.style.borderColor='var(--primary)';this.style.boxShadow='var(--shadow-md)'"
               onmouseout="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
            <!-- Top bar -->
            <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary),var(--accent));"></div>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
              <span class="badge-lms primary" style="font-size:11px;"><?= htmlspecialchars($c['code']) ?></span>
              <span class="badge-lms <?= $c['status']==='active'?'success':'danger' ?>"><?= ucfirst($c['status']) ?></span>
            </div>

            <div class="fw-700" style="font-size:15px;margin-bottom:6px;color:var(--text-main);">
              <?= htmlspecialchars($c['course_name']) ?>
            </div>
            <div class="text-muted" style="font-size:12px;margin-bottom:14px;min-height:36px;">
              <?= htmlspecialchars($c['description'] ?? 'No description.') ?>
            </div>

            <div style="display:flex;gap:16px;margin-bottom:16px;">
              <div style="text-align:center;">
                <div class="fw-700" style="color:var(--accent);font-size:16px;">Rs.<?= number_format($c['fee'],0) ?></div>
                <div style="font-size:10px;color:var(--text-muted);">Fee</div>
              </div>
              <div style="text-align:center;">
                <div class="fw-700" style="color:var(--primary);font-size:16px;"><?= $c['duration'] ?? '—' ?></div>
                <div style="font-size:10px;color:var(--text-muted);">Duration</div>
              </div>
              <div style="text-align:center;">
                <div class="fw-700" style="font-size:16px;"><?= $c['enrollment_count'] ?></div>
                <div style="font-size:10px;color:var(--text-muted);">Enrolled</div>
              </div>
            </div>

            <div style="display:flex;gap:8px;">
              <a href="?action=edit&id=<?= $c['id'] ?>" class="btn-lms btn-outline btn-sm" style="flex:1;justify-content:center;">
                <i class="fas fa-pen"></i> Edit
              </a>
              <form method="POST" style="flex:1;">
                <input type="hidden" name="act" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn-lms btn-danger btn-sm w-100"
                  data-confirm="Delete course '<?= htmlspecialchars($c['course_name']) ?>'?">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
