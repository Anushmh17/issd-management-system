<?php
// =====================================================
// LEARN Management - Admin: Students Management
// =====================================================
define('PAGE_TITLE', 'Students');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$action  = $_GET['action'] ?? 'list';
$message = '';
$error   = '';

// ---- Handle POST: Add / Edit / Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $pass    = trim($_POST['password'] ?? '');
        $sid     = trim($_POST['student_id'] ?? '');
        $dob     = $_POST['dob'] ?? null;
        $gender  = $_POST['gender'] ?? null;
        $address = trim($_POST['address'] ?? '');
        $enrolled= $_POST['enrolled_date'] ?? date('Y-m-d');

        if (!$name || !$email || !$pass) {
            $error = 'Name, email and password are required.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,phone,status) VALUES (?,?,?,'student',?,'active')");
                $stmt->execute([$name, $email, $hash, $phone]);
                $uid = $pdo->lastInsertId();

                $stmt2 = $pdo->prepare("INSERT INTO student_profiles (user_id,student_id,dob,gender,address,enrolled_date) VALUES (?,?,?,?,?,?)");
                $stmt2->execute([$uid, $sid, $dob ?: null, $gender ?: null, $address, $enrolled]);
                $pdo->commit();

                setFlash('success', 'Student added successfully.');
                header('Location: students.php'); exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = $e->getCode() == 23000 ? 'Email already exists.' : 'Failed to add student.';
            }
        }
    }

    if ($act === 'edit') {
        $id     = (int)$_POST['id'];
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $phone  = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';

        try {
            $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,status=? WHERE id=? AND role='student'")
                ->execute([$name,$email,$phone,$status,$id]);
            setFlash('success','Student updated successfully.');
            header('Location: students.php'); exit;
        } catch (PDOException $e) {
            $error = 'Update failed.';
        }
    }

    if ($act === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='student'")->execute([$id]);
        setFlash('success','Student deleted.');
        header('Location: students.php'); exit;
    }
}

// ---- Fetch for edit ----
$editStudent = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT u.*, sp.student_id AS s_id, sp.dob, sp.gender, sp.address, sp.enrolled_date
        FROM users u LEFT JOIN student_profiles sp ON sp.user_id=u.id
        WHERE u.id=? AND u.role='student'
    ");
    $stmt->execute([(int)$_GET['id']]);
    $editStudent = $stmt->fetch();
}

// ---- Fetch all students ----
$search = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, sp.student_id AS s_id, sp.enrolled_date,
        (SELECT COUNT(*) FROM enrollments e WHERE e.student_id=u.id) AS course_count
        FROM users u
        LEFT JOIN student_profiles sp ON sp.user_id=u.id
        WHERE u.role='student'";
$params = [];
if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR sp.student_id LIKE ?)";
    $params = ["%$search%","%$search%","%$search%"];
}
$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Students Management</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Students</span></div>
    </div>
    <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn-primary-grad"><i class="fas fa-user-plus"></i> Add Student</a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- ADD / EDIT FORM -->
  <?php if ($action === 'add' || $action === 'edit'): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title">
        <i class="fas fa-<?= $action==='add'?'user-plus':'user-edit' ?>"></i>
        <?= $action==='add'?'Add New Student':'Edit Student' ?>
      </div>
      <a href="students.php" class="btn-lms btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-lms-body">
      <form method="POST" action="students.php">
        <input type="hidden" name="act" value="<?= $action ?>">
        <?php if ($action==='edit'): ?>
          <input type="hidden" name="id" value="<?= $editStudent['id'] ?>">
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Full Name *</label>
              <input type="text" name="name" class="form-control-lms"
                value="<?= htmlspecialchars($editStudent['name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Email Address *</label>
              <input type="email" name="email" class="form-control-lms"
                value="<?= htmlspecialchars($editStudent['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control-lms"
                value="<?= htmlspecialchars($editStudent['phone'] ?? '') ?>">
            </div>
          </div>
          <?php if ($action==='add'): ?>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Password *</label>
              <input type="password" name="password" class="form-control-lms" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Student ID</label>
              <input type="text" name="student_id" class="form-control-lms"
                placeholder="e.g. STU001">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Enrolled Date</label>
              <input type="date" name="enrolled_date" class="form-control-lms"
                value="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Date of Birth</label>
              <input type="date" name="dob" class="form-control-lms">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Gender</label>
              <select name="gender" class="form-control-lms">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Address</label>
              <input type="text" name="address" class="form-control-lms">
            </div>
          </div>
          <?php else: ?>
          <div class="col-md-4">
            <div class="form-group-lms">
              <label>Status</label>
              <select name="status" class="form-control-lms">
                <option value="active" <?= ($editStudent['status']??'')==='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= ($editStudent['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
              </select>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div style="margin-top:8px;display:flex;gap:10px;">
          <button type="submit" class="btn-lms btn-primary">
            <i class="fas fa-save"></i> <?= $action==='add'?'Add Student':'Update Student' ?>
          </button>
          <a href="students.php" class="btn-lms btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- STUDENTS TABLE -->
  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-users"></i> All Students (<?= count($students) ?>)</div>
      <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="q" id="tableSearch" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-lms btn-primary btn-sm">Search</button>
      </form>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($students)): ?>
        <div class="empty-state"><i class="fas fa-users"></i><p>No students found.</p></div>
      <?php else: ?>
      <table class="table-lms searchable-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Student ID</th>
            <th>Phone</th>
            <th>Courses</th>
            <th>Enrolled</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="avatar-initials"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['email']) ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($s['s_id'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
            <td><span class="badge-lms info"><?= $s['course_count'] ?></span></td>
            <td><?= $s['enrolled_date'] ? date('M d, Y',strtotime($s['enrolled_date'])) : '—' ?></td>
            <td>
              <span class="badge-lms <?= $s['status']==='active'?'success':'danger' ?>">
                <?= ucfirst($s['status']) ?>
              </span>
            </td>
            <td>
              <div class="d-flex gap-10">
                <a href="?action=edit&id=<?= $s['id'] ?>" class="btn-lms btn-outline btn-sm" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn-lms btn-danger btn-sm"
                    data-confirm="Delete student '<?= htmlspecialchars($s['name']) ?>'? This cannot be undone.">
                    <i class="fas fa-trash"></i>
                  </button>
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
