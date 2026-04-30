<?php
// =====================================================
// ISSD Management - Admin: Notices Management
// =====================================================
define('PAGE_TITLE', 'Notices');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$error = '';
$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $target_role = $_POST['target_role'] ?? 'all';

        if (!$title || !$content) {
            $error = 'Title and Content are required.';
        } else {
            try {
                if ($act === 'add') {
                    $pdo->prepare("INSERT INTO notices (title, content, target_role, posted_by) VALUES (?, ?, ?, ?)")
                        ->execute([$title, $content, $target_role, $userId]);
                    setFlash('success', 'Notice posted successfully.');
                } else {
                    $id = (int)$_POST['id'];
                    $pdo->prepare("UPDATE notices SET title=?, content=?, target_role=? WHERE id=?")
                        ->execute([$title, $content, $target_role, $id]);
                    setFlash('success', 'Notice updated successfully.');
                }
                header('Location: notices.php'); exit;
            } catch (PDOException $e) {
                $error = 'Failed to save notice.';
            }
        }
    }

    if ($act === 'delete') {
        $pdo->prepare("DELETE FROM notices WHERE id=?")->execute([(int)$_POST['id']]);
        setFlash('success', 'Notice deleted.');
        header('Location: notices.php'); exit;
    }
}

$editNotice = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editNotice = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT n.*, u.name AS posted_by_name FROM notices n JOIN users u ON u.id = n.posted_by";
$params = [];
if ($search) {
    $sql .= " WHERE n.title LIKE ? OR n.content LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notices = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Notices</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Admin &rsaquo; <span>Notices</span></div>
    </div>
    <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn-primary-grad"><i class="fas fa-plus"></i> Post Notice</a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'add' || $action === 'edit'): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-bell"></i> <?= $action==='add'?'Post New Notice':'Edit Notice' ?></div>
      <a href="notices.php" class="btn-lms btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-lms-body">
      <form method="POST" action="notices.php">
        <input type="hidden" name="act" value="<?= $action ?>">
        <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?= $editNotice['id'] ?>"><?php endif; ?>

        <div class="row g-3">
          <div class="col-md-8"><div class="form-group-lms">
            <label>Notice Title *</label>
            <input type="text" name="title" class="form-control-lms" value="<?= htmlspecialchars($editNotice['title']??'') ?>" required>
          </div></div>
          
          <div class="col-md-4"><div class="form-group-lms">
            <label>Target Audience</label>
            <select name="target_role" class="form-control-lms">
              <option value="all" <?= ($editNotice['target_role']??'')==='all'?'selected':'' ?>>All (Everyone)</option>
              <option value="student" <?= ($editNotice['target_role']??'')==='student'?'selected':'' ?>>Students Only</option>
              <option value="lecturer" <?= ($editNotice['target_role']??'')==='lecturer'?'selected':'' ?>>Lecturers Only</option>
              <option value="admin" <?= ($editNotice['target_role']??'')==='admin'?'selected':'' ?>>Admins Only</option>
            </select>
          </div></div>

          <div class="col-12"><div class="form-group-lms">
            <label>Content *</label>
            <textarea name="content" class="form-control-lms" rows="6" required><?= htmlspecialchars($editNotice['content']??'') ?></textarea>
          </div></div>
        </div>

        <div style="margin-top:8px;display:flex;gap:10px;">
          <button type="submit" class="btn-lms btn-primary"><i class="fas fa-paper-plane"></i> <?= $action==='add'?'Publish Notice':'Save Changes' ?></button>
          <a href="notices.php" class="btn-lms btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-bullhorn"></i> All Notices (<?= count($notices) ?>)</div>
      <form method="GET" class="header-filter-form">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-lms btn-primary btn-sm">Search</button>
      </form>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($notices)): ?>
        <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notices found.</p></div>
      <?php else: ?>
      <table class="table-lms searchable-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Title</th>
            <th>Target</th>
            <th>Posted By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notices as $n): ?>
          <tr>
            <td style="white-space:nowrap;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></td>
            <td class="fw-600">
                <?= htmlspecialchars($n['title']) ?>
                <div class="text-muted fw-400" style="font-size:12px;margin-top:4px;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($n['content']) ?>
                </div>
            </td>
            <td>
              <?php
                $bg = match($n['target_role']) {
                    'student' => 'success',
                    'lecturer' => 'warning',
                    'admin' => 'danger',
                    default => 'primary'
                };
              ?>
              <span class="badge-lms <?= $bg ?>"><?= ucfirst($n['target_role']) ?></span>
            </td>
            <td><?= htmlspecialchars($n['posted_by_name']) ?></td>
            <td>
              <div class="d-flex gap-10">
                <button type="button" class="btn-lms btn-outline btn-sm notice-card-clickable"
                        data-real-id="<?= $n['id'] ?>"
                        data-title="<?= htmlspecialchars($n['title']) ?>"
                        data-content="<?= htmlspecialchars($n['content']) ?>"
                        data-author="<?= htmlspecialchars($n['posted_by_name']) ?>"
                        data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>">
                  <i class="fas fa-eye"></i>
                </button>
                <a href="?action=edit&id=<?= $n['id'] ?>" class="btn-lms btn-outline btn-sm"><i class="fas fa-pen"></i></a>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= $n['id'] ?>">
                  <button type="submit" class="btn-lms btn-danger btn-sm" data-confirm="Delete this notice?"><i class="fas fa-trash"></i></button>
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

