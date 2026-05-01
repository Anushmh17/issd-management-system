<?php
// =====================================================
// ISSD Management - Admin: Students List
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
$followup = trim($_GET['followup'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'batch', 'status', 'followup');
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


$extraCSS = <<<CSS
<style>
.doc-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.doc-badge.completed { background: #dcfce7; color: #10b981; border: 1px solid #b9f6ca; }
.doc-badge.pending { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }
.doc-badge.missing { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }

.id-badge-lms { background: #f1f5f9; color: #475569; font-weight: 700; padding: 4px 8px; border-radius: 6px; font-size: 12px; }
.batch-badge-lms { background: #e0f2fe; color: #0369a1; font-weight: 600; padding: 4px 8px; border-radius: 6px; font-size: 12px; }

/* Legend Styles */
.list-legend { display: flex; flex-direction: column; align-items: flex-end; text-align: right; }
.list-legend-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); margin-bottom: 2px; }
.list-legend-title { font-size: 18px; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px; font-family: 'Poppins', sans-serif; }
.count-badge { background: var(--primary-light); color: var(--primary); padding: 2px 10px; border-radius: 30px; font-size: 13px; font-weight: 800; }

.students-filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-select { font-size: 13px !important; height: 38px !important; border-radius: 10px !important; padding: 0 12px !important; background-color: #f8fafc !important; }
</style>
CSS;

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
      <a href="index.php" class="text-decoration-none">
        <div class="stat-card" style="--sc-color: var(--primary);">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $totalAll ?></div>
            <div class="stat-label">Total Students</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?status=new_joined" class="text-decoration-none">
        <div class="stat-card" style="--sc-color: var(--info);">
          <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $totalNew ?></div>
            <div class="stat-label">New Joined</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?status=dropout" class="text-decoration-none">
        <div class="stat-card" style="--sc-color: var(--danger);">
          <div class="stat-icon"><i class="fas fa-user-minus"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $totalDrop ?></div>
            <div class="stat-label">Dropout</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?status=completed" class="text-decoration-none">
        <div class="stat-card" style="--sc-color: var(--accent);">
          <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $totalDone ?></div>
            <div class="stat-label">Completed</div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Students Table Card -->
  <div class="card-lms">
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Student Management</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>All Students</span>
            <span class="count-badge"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" id="filterForm" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 300px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" id="searchInput" placeholder="Search Name, ID or NIC..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="batch" class="form-control-lms filter-select" id="batchFilter"
                  style="min-width: 140px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="document.getElementById('filterForm').submit()">
            <option value="">All Batches</option>
            <?php foreach ($batches as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>" <?= $batch === $b ? 'selected' : '' ?>>
                Batch <?= htmlspecialchars($b) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="status" class="form-control-lms filter-select" id="statusFilter"
                  style="min-width: 160px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="document.getElementById('filterForm').submit()">
            <option value="">All Status</option>
            <option value="new_joined"  <?= $status === 'new_joined'  ? 'selected' : '' ?>>New Joined</option>
            <option value="dropout"     <?= $status === 'dropout'     ? 'selected' : '' ?>>Dropout</option>
            <option value="completed"   <?= $status === 'completed'   ? 'selected' : '' ?>>Completed</option>
          </select>
        </div>

        <div class="filter-actions d-flex gap-2">
          <button type="submit" class="btn-lms btn-primary px-4 rounded-3 shadow-sm" style="height: 46px; padding: 0 25px;">
            <i class="fas fa-filter me-1"></i> Filter
          </button>
          <?php if ($search || $batch || $status): ?>
            <a href="index.php" class="btn-lms btn-outline px-3 rounded-3 d-flex align-items-center justify-content-center" style="height: 46px; width: 46px;" title="Clear Filters">
              <i class="fas fa-xmark"></i>
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
            <th style="min-width:100px;">Batch</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Doc Status</th>
            <th>Joined</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s): 
              $isHighlighted = (isset($_GET['highlight_id']) && (int)$_GET['highlight_id'] === (int)$s['id']);
          ?>
          <tr id="row-<?= $s['id'] ?>" class="<?= $isHighlighted ? 'row-highlight' : '' ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page - 1) * 15) + $i + 1 ?></td>
            <td>
              <span class="id-badge-lms">
                <?= htmlspecialchars($s['student_id']) ?>
              </span>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/admin/payments/add.php?student_id=<?= $s['id'] ?>" 
                 style="color:inherit;text-decoration:none;" title="Click to pay">
                <div class="d-flex align-center gap-10">
                  <div class="avatar-initials"
                       style="background:<?= !empty($s['profile_picture']) ? 'none' : studentAvatarColor($s['full_name']) ?>;flex-shrink:0; overflow:hidden; padding:0;">
                    <?php if (!empty($s['profile_picture'])): ?>
                      <img src="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($s['profile_picture']) ?>" 
                           style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="fw-600" style="font-size:14px;"><?= htmlspecialchars($s['full_name']) ?></div>
                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['nic_number']) ?></div>
                  </div>
                </div>
              </a>
            </td>
            <td>
              <span class="batch-badge-lms">
                <?= htmlspecialchars(str_ireplace('BATCH-', '', $s['batch_number'])) ?>
              </span>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($s['phone_number']) ?></td>
            <td><?= renderStatusBadge($s['status']) ?></td>
            <td><?= renderDocStatusBadge($docStatuses[(int)$s['id']] ?? 'missing') ?></td>
            <td style="font-size:13px;color:#64748b;">
              <?= $s['join_date'] ? date('d M Y', strtotime($s['join_date'])) : '""' ?>
            </td>
            <td>
              <div class="d-flex gap-6" style="justify-content:center;">
                <a href="<?= BASE_URL ?>/admin/payments/add.php?student_id=<?= $s['id'] ?>"
                   class="btn-lms btn-sm"
                   style="background: #ecfdf5; color: #10b981; border: 1px solid #d1fae5;"
                   title="Add Payment">
                  <i class="fas fa-money-bill-wave"></i>
                </a>
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
          Showing <?= (($page - 1) * 15) + 1 ?>""<?= min($page * 15, $total) ?> of <?= $total ?> students
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const highlightId = urlParams.get('highlight_id');
  if (highlightId) {
    const targetRow = document.getElementById('row-' + highlightId);
    if (targetRow) {
      setTimeout(() => {
        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetRow.classList.add('highlight-row');
        setTimeout(() => targetRow.classList.remove('highlight-row'), 4500);
      }, 500);
    }
  }
});
</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

