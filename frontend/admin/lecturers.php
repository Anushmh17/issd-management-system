<?php
// =====================================================
// LEARN Management - Admin: Lecturers Management
// =====================================================
define('PAGE_TITLE', 'Lecturers');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $phone  = trim($_POST['phone'] ?? '');
        $pass   = trim($_POST['password'] ?? '');
        $empId  = trim($_POST['employee_id'] ?? '');
        $dept   = trim($_POST['department'] ?? '');
        $qual   = trim($_POST['qualification'] ?? '');
        $joined = $_POST['joined_date'] ?? null;

        if (!$name || !$email || !$pass) {
            $error = 'Name, email and password are required.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,phone,status) VALUES (?,?,?,'lecturer',?,'active')");
                $stmt->execute([$name,$email,$hash,$phone]);
                $uid = $pdo->lastInsertId();

                $pdo->prepare("INSERT INTO lecturer_profiles (user_id,employee_id,department,qualification,joined_date) VALUES (?,?,?,?,?)")
                    ->execute([$uid,$empId,$dept,$qual,$joined?:null]);
                $pdo->commit();
                setFlash('success','Lecturer added successfully.');
                header('Location: lecturers.php'); exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = $e->getCode()==23000 ? 'Email already exists.' : 'Failed to add lecturer.';
            }
        }
    }

    if ($act === 'edit') {
        $id     = (int)$_POST['id'];
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $phone  = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $dept   = trim($_POST['department'] ?? '');
        $qual   = trim($_POST['qualification'] ?? '');

        try {
            $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,status=? WHERE id=? AND role='lecturer'")
                ->execute([$name,$email,$phone,$status,$id]);
            $pdo->prepare("UPDATE lecturer_profiles SET department=?,qualification=? WHERE user_id=?")
                ->execute([$dept,$qual,$id]);
            setFlash('success','Lecturer updated.'); header('Location: lecturers.php'); exit;
        } catch (PDOException $e) { $error='Update failed.'; }
    }

    if ($act === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='lecturer'")->execute([$id]);
        setFlash('success','Lecturer deleted.'); header('Location: lecturers.php'); exit;
    }
}

$editLecturer = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT u.*, lp.employee_id, lp.department, lp.qualification, lp.joined_date
        FROM users u LEFT JOIN lecturer_profiles lp ON lp.user_id=u.id
        WHERE u.id=? AND u.role='lecturer'
    ");
    $stmt->execute([(int)$_GET['id']]);
    $editLecturer = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, lp.employee_id, lp.department,
        (SELECT COUNT(DISTINCT course_id) FROM enrollments e WHERE e.lecturer_id=u.id) AS courses_count
        FROM users u LEFT JOIN lecturer_profiles lp ON lp.user_id=u.id
        WHERE u.role='lecturer'";
$params = [];
if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR lp.department LIKE ?)";
    $params = ["%$search%","%$search%","%$search%"];
}
$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$lecturers = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Lecturers Management</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Lecturers</span></div>
    </div>
    <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn-primary-grad"><i class="fas fa-user-plus"></i> Add Lecturer</a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'add' || $action === 'edit'): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-chalkboard-user"></i> <?= $action==='add'?'Add New Lecturer':'Edit Lecturer' ?></div>
      <a href="lecturers.php" class="btn-lms btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-lms-body">
      <form method="POST" action="lecturers.php" autocomplete="off">
        <input type="hidden" name="act" value="<?= $action ?>">
        <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?= $editLecturer['id'] ?>"><?php endif; ?>

        <div class="row g-3">
          <div class="col-md-4"><div class="form-group-lms">
            <label>Full Name *</label>
            <input type="text" name="name" class="form-control-lms" value="<?= htmlspecialchars($editLecturer['name']??'') ?>" required>
          </div></div>
          <div class="col-md-4"><div class="form-group-lms">
            <label>Email *</label>
            <input type="email" name="email" class="form-control-lms" value="<?= htmlspecialchars($editLecturer['email']??'') ?>" required>
          </div></div>
          <div class="col-md-4"><div class="form-group-lms">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control-lms" value="<?= htmlspecialchars($editLecturer['phone']??'') ?>">
          </div></div>

          <?php if ($action==='add'): ?>
          <div class="col-md-4"><div class="form-group-lms">
            <label>Password *</label>
            <input type="password" name="password" class="form-control-lms" required autocomplete="new-password">
          </div></div>
          <div class="col-md-4"><div class="form-group-lms">
            <label>Employee ID</label>
            <input type="text" name="employee_id" class="form-control-lms" placeholder="e.g. LEC001">
          </div></div>
          <div class="col-md-4"><div class="form-group-lms">
            <label>Joined Date</label>
            <input type="date" name="joined_date" class="form-control-lms" value="<?= date('Y-m-d') ?>">
          </div></div>
          <?php else: ?>
          <div class="col-md-4"><div class="form-group-lms">
            <label>Status</label>
            <select name="status" class="form-control-lms">
              <option value="active" <?= ($editLecturer['status']??'')==='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= ($editLecturer['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </div></div>
          <?php endif; ?>

          <div class="col-md-4"><div class="form-group-lms">
            <label>Department</label>
            <input type="text" name="department" class="form-control-lms" value="<?= htmlspecialchars($editLecturer['department']??'') ?>" placeholder="e.g. Computer Science">
          </div></div>
          <div class="col-md-8"><div class="form-group-lms">
            <label>Qualification</label>
            <input type="text" name="qualification" class="form-control-lms" value="<?= htmlspecialchars($editLecturer['qualification']??'') ?>" placeholder="e.g. M.Sc. Computer Science">
          </div></div>
        </div>

        <div style="margin-top:8px;display:flex;gap:10px;">
          <button type="submit" class="btn-lms btn-primary"><i class="fas fa-save"></i> <?= $action==='add'?'Add Lecturer':'Update Lecturer' ?></button>
          <a href="lecturers.php" class="btn-lms btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-chalkboard-user"></i> All Lecturers (<?= count($lecturers) ?>)</div>
      <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search lecturers..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-lms btn-primary btn-sm">Search</button>
      </form>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($lecturers)): ?>
        <div class="empty-state"><i class="fas fa-chalkboard-user"></i><p>No lecturers yet.</p></div>
      <?php else: ?>
      <table class="table-lms">
        <thead>
          <tr><th>#</th><th>Lecturer</th><th>Emp. ID</th><th>Department</th><th>Courses</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($lecturers as $i => $l): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="avatar-initials"><?= strtoupper(substr($l['name'],0,1)) ?></div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($l['name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($l['email']) ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($l['employee_id']??'—') ?></td>
            <td><?= htmlspecialchars($l['department']??'—') ?></td>
            <td><span class="badge-lms info"><?= $l['courses_count'] ?></span></td>
            <td><span class="badge-lms <?= $l['status']==='active'?'success':'danger' ?>"><?= ucfirst($l['status']) ?></span></td>
            <td>
              <div class="d-flex gap-10">
                <a href="?action=edit&id=<?= $l['id'] ?>" class="btn-lms btn-outline btn-sm"><i class="fas fa-pen"></i></a>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <button type="submit" class="btn-lms btn-danger btn-sm" data-confirm="Delete lecturer '<?= htmlspecialchars($l['name']) ?>'?"><i class="fas fa-trash"></i></button>
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
