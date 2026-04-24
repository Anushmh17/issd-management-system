<?php
// =====================================================
// LEARN Management - Admin: Students List
// admin/students/index.php
// =====================================================
define('PAGE_TITLE', 'Students');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/student_controller.php';
require_once dirname(__DIR__, 2) . '/backend/document_controller.php';

requireRole(ROLE_ADMIN);

// ---- Handle POST actions before any output ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        if (deleteStudent($pdo, $id)) {
            setFlash('success', 'Student deleted successfully.');
        } else {
            setFlash('danger', 'Failed to delete student.');
        }
        // Preserve filters when redirecting
        $qs = http_build_query([
            'search' => $_POST['search'] ?? '',
            'batch'  => $_POST['batch']  ?? '',
            'status' => $_POST['status'] ?? '',
            'page'   => $_POST['page']   ?? 1,
        ]);
        header('Location: index.php?' . $qs);
        exit;
    }
}

// ---- Filters & Pagination ----
$search = trim($_GET['search'] ?? '');
$batch  = trim($_GET['batch']  ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'batch', 'status');
$result  = getStudentsList($pdo, $filters, $page, 15);

$students = $result['students'];
$total    = $result['total'];
$pages    = $result['pages'];
$batches  = getAllBatches($pdo);

// ---- Bulk document status ----
$sIds = array_map('intval', array_column($students, 'id'));
$docStatuses = getBulkDocStatus($pdo, $sIds);

// ---- Stats ----
$totalAll  = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalNew  = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='new_joined'")->fetchColumn();
$totalDrop = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='dropout'")->fetchColumn();
$totalDone = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='completed'")->fetchColumn();

// ---- Helpers ----
function renderStatusBadge(string $status): string {
    $class = 'status-' . $status;
    $label = str_replace('_', ' ', ucfirst($status));
    return '<span class="badge-lms ' . $class . '"><i class="fas fa-circle-dot"></i>' . $label . '</span>';
}

function studentAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Students Management</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Students</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad" id="btn-add-student">
      <i class="fas fa-user-plus"></i> Add Student
    </a>
  </div>

  <!-- Stats Row -->
  <div class="row g-3 mb-20">
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#5b4efa;">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $totalAll ?></div>
          <div class="stat-label">Total Students</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#3b82f6;">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $totalNew ?></div>
          <div class="stat-label">New Joined</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#ef4444;">
        <div class="stat-icon"><i class="fas fa-user-minus"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $totalDrop ?></div>
          <div class="stat-label">Dropout</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#10b981;">
        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $totalDone ?></div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Students Table Card -->
  <div class="card-lms">
    <div class="card-lms-header students-filter-bar">
      <div class="card-lms-title">
        <i class="fas fa-table-list"></i>
        All Students
        <span class="badge-lms info" style="margin-left:6px;font-size:12px;"><?= $total ?></span>
      </div>

      <!-- Search + Filter Form -->
      <form method="GET" id="filterForm" class="students-filters">
        <div class="search-bar" style="min-width:220px;">
          <i class="fas fa-search"></i>
          <input type="text" name="search" id="searchInput"
                 placeholder="Name, ID or NIC…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="batch" class="form-control-lms filter-select" id="batchFilter"
                onchange="document.getElementById('filterForm').submit()">
          <option value="">All Batches</option>
          <?php foreach ($batches as $b): ?>
            <option value="<?= htmlspecialchars($b) ?>"
              <?= $batch === $b ? 'selected' : '' ?>>
              Batch <?= htmlspecialchars($b) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="status" class="form-control-lms filter-select" id="statusFilter"
                onchange="document.getElementById('filterForm').submit()">
          <option value="">All Status</option>
          <option value="new_joined"  <?= $status === 'new_joined'  ? 'selected' : '' ?>>New Joined</option>
          <option value="dropout"     <?= $status === 'dropout'     ? 'selected' : '' ?>>Dropout</option>
          <option value="completed"   <?= $status === 'completed'   ? 'selected' : '' ?>>Completed</option>
        </select>

        <div class="filter-actions">
          <button type="submit" class="btn-lms btn-primary btn-sm">
            <i class="fas fa-filter"></i> Filter
          </button>
          <?php if ($search || $batch || $status): ?>
            <a href="index.php" class="btn-lms btn-outline btn-sm">
              <i class="fas fa-xmark"></i> Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($students)): ?>
        <div class="empty-state">
          <i class="fas fa-user-slash"></i>
          <p>No students found<?= ($search || $batch || $status) ? ' matching your filters.' : '.' ?></p>
          <?php if (!$search && !$batch && !$status): ?>
            <a href="add.php" class="btn-lms btn-primary mt-10">
              <i class="fas fa-user-plus"></i> Add First Student
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="table-lms" id="studentsTable">
        <thead>
          <tr>
            <th style="width:50px;">#</th>
            <th style="min-width:140px;">Student ID</th>
            <th>Full Name</th>
            <th style="min-width:130px;">Batch</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Doc Status</th>
            <th>Joined</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s): ?>
          <tr>
            <td style="color:#94a3b8;font-size:13px;"><?= (($page - 1) * 15) + $i + 1 ?></td>
            <td>
              <span class="id-badge-lms">
                <?= htmlspecialchars($s['student_id']) ?>
              </span>
            </td>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="avatar-initials"
                     style="background:<?= studentAvatarColor($s['full_name']) ?>;flex-shrink:0;">
                  <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                </div>
                <div>
                  <div class="fw-600" style="font-size:14px;"><?= htmlspecialchars($s['full_name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['nic_number']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="batch-badge-lms">
                <?= htmlspecialchars($s['batch_number']) ?>
              </span>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($s['phone_number']) ?></td>
            <td><?= renderStatusBadge($s['status']) ?></td>
            <td><?= renderDocStatusBadge($docStatuses[(int)$s['id']] ?? 'missing') ?></td>
            <td style="font-size:13px;color:#64748b;">
              <?= $s['join_date'] ? date('d M Y', strtotime($s['join_date'])) : '—' ?>
            </td>
            <td>
              <div class="d-flex gap-6" style="justify-content:center;">
                <a href="<?= BASE_URL ?>/admin/documents/manage.php?student_id=<?= $s['id'] ?>"
                   class="btn-lms btn-sm"
                   style="background:#ede9fe;color:#5b4efa;border:1px solid #ddd6fe;"
                   title="Manage Documents"
                   id="btn-docs-<?= $s['id'] ?>">
                  <i class="fas fa-folder-open"></i>
                </a>
                <a href="edit.php?id=<?= $s['id'] ?>"
                   class="btn-lms btn-outline btn-sm"
                   title="Edit Student"
                   id="btn-edit-<?= $s['id'] ?>">
                  <i class="fas fa-pen-to-square"></i>
                </a>
                <form method="POST" action="index.php" style="display:inline;">
                  <input type="hidden" name="act"    value="delete">
                  <input type="hidden" name="id"     value="<?= $s['id'] ?>">
                  <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                  <input type="hidden" name="batch"  value="<?= htmlspecialchars($batch) ?>">
                  <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                  <input type="hidden" name="page"   value="<?= $page ?>">
                  <button type="submit"
                          class="btn-lms btn-danger btn-sm"
                          title="Delete Student"
                          id="btn-delete-<?= $s['id'] ?>"
                          data-confirm="Permanently delete student '<?= htmlspecialchars($s['full_name']) ?>'? This cannot be undone.">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page - 1) * 15) + 1 ?>–<?= min($page * 15, $total) ?> of <?= $total ?> students
        </div>
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>"
               class="page-btn" id="btn-prev-page">
              <i class="fas fa-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php
          $pStart = max(1, $page - 2);
          $pEnd   = min($pages, $page + 2);
          for ($p = $pStart; $p <= $pEnd; $p++): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"
               id="btn-page-<?= $p ?>">
              <?= $p ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $pages): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>"
               class="page-btn" id="btn-next-page">
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
