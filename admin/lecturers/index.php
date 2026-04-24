<?php
// =====================================================
// LEARN Management - Admin: Lecturers List
// admin/lecturers/index.php
// =====================================================
define('PAGE_TITLE', 'Lecturers');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/lecturer_controller.php';

requireRole(ROLE_ADMIN);

// ---- Handle DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'delete') {
    $lid = (int)($_POST['id'] ?? 0);
    if (deleteLecturer($pdo, $lid)) {
        setFlash('success', 'Lecturer deleted successfully.');
    } else {
        setFlash('danger', 'Failed to delete lecturer.');
    }
    header('Location: index.php'); exit;
}

// ---- Filters ----
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'status');
$result  = getLecturersList($pdo, $filters, $page, 15);
$lecturers = $result['lecturers'];
$total     = $result['total'];
$pages     = $result['pages'];

// ---- Stats ----
$totalL    = (int)$pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$activeL   = (int)$pdo->query("SELECT COUNT(*) FROM lecturers WHERE status='active'")->fetchColumn();
$inactiveL = (int)$pdo->query("SELECT COUNT(*) FROM lecturers WHERE status='inactive'")->fetchColumn();
$assignedL = (int)$pdo->query("SELECT COUNT(DISTINCT lecturer_id) FROM course_assignments")->fetchColumn();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Lecturers Management</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Lecturers</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad" id="btn-add-lecturer">
      <i class="fas fa-user-plus"></i> Add Lecturer
    </a>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-20">
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#5b4efa;">
        <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $totalL ?></div>
          <div class="stat-label">Total Lecturers</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#10b981;">
        <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $activeL ?></div>
          <div class="stat-label">Active</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#94a3b8;">
        <div class="stat-icon"><i class="fas fa-circle-pause"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $inactiveL ?></div>
          <div class="stat-label">Inactive</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#3b82f6;">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $assignedL ?></div>
          <div class="stat-label">Course Assigned</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card-lms">
    <div class="card-lms-header students-filter-bar">
      <div class="card-lms-title">
        <i class="fas fa-table-list"></i> All Lecturers
        <span class="badge-lms info" style="margin-left:6px;font-size:12px;"><?= $total ?></span>
      </div>
      <form method="GET" id="filterForm" class="students-filters">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Name, email, username…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="status" class="form-control-lms filter-select"
                onchange="document.getElementById('filterForm').submit()">
          <option value="">All Status</option>
          <option value="active"   <?= $status==='active'   ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" class="btn-lms btn-primary btn-sm">
          <i class="fas fa-filter"></i> Filter
        </button>
        <?php if ($search || $status): ?>
          <a href="index.php" class="btn-lms btn-outline btn-sm">
            <i class="fas fa-xmark"></i> Clear
          </a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($lecturers)): ?>
        <div class="empty-state">
          <i class="fas fa-chalkboard-user"></i>
          <p>No lecturers found<?= ($search||$status)?' matching your filters.':'.'; ?></p>
          <?php if (!$search && !$status): ?>
            <a href="add.php" class="btn-lms btn-primary mt-10">
              <i class="fas fa-user-plus"></i> Add First Lecturer
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="table-lms" id="lecturersTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Lecturer</th>
            <th>Username</th>
            <th>Phone</th>
            <th>Department</th>
            <th>Courses</th>
            <th>Status</th>
            <th>Joined</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lecturers as $i => $l): ?>
          <tr class="<?= $l['status']==='inactive' ? 'course-inactive-row' : '' ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page-1)*15)+$i+1 ?></td>
            <td>
              <div class="d-flex align-center gap-10">
                <?php $photoUrl = lecturerPhotoUrl($l['photo']); ?>
                <div class="lect-avatar-wrap">
                  <img src="<?= htmlspecialchars($photoUrl) ?>"
                       alt="<?= htmlspecialchars($l['name']) ?>"
                       class="lect-avatar"
                       onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                  <div class="avatar-initials lect-avatar-fallback" style="display:none;background:<?= lecturerAvatarColor($l['name']) ?>;">
                    <?= strtoupper(substr($l['name'], 0, 1)) ?>
                  </div>
                </div>
                <div>
                  <div class="fw-600" style="font-size:14px;"><?= htmlspecialchars($l['name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($l['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span style="font-family:monospace;font-size:12px;background:#f0edff;
                           color:#5b4efa;padding:3px 8px;border-radius:5px;border:1px solid #e0d9ff;">
                @<?= htmlspecialchars($l['username']) ?>
              </span>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($l['phone'] ?: '—') ?></td>
            <td style="font-size:13px;"><?= htmlspecialchars($l['department'] ?: '—') ?></td>
            <td>
              <span class="badge-lms info"><?= $l['course_count'] ?> course<?= $l['course_count']!=1?'s':'' ?></span>
            </td>
            <td>
              <?php if ($l['status'] === 'active'): ?>
                <span class="badge-lms" style="background:#d1fae5;color:#059669;border:1px solid #a7f3d0;">
                  <i class="fas fa-circle-dot" style="font-size:8px;"></i> Active
                </span>
              <?php else: ?>
                <span class="badge-lms" style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">
                  <i class="fas fa-circle-dot" style="font-size:8px;"></i> Inactive
                </span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:#64748b;">
              <?= $l['joined_date'] ? date('d M Y', strtotime($l['joined_date'])) : '—' ?>
            </td>
            <td>
              <div class="d-flex gap-6" style="justify-content:center;">
                <a href="edit.php?id=<?= $l['id'] ?>"
                   class="btn-lms btn-outline btn-sm"
                   title="Edit Lecturer" id="btn-edit-<?= $l['id'] ?>">
                  <i class="fas fa-pen-to-square"></i>
                </a>
                <form method="POST" action="index.php" style="display:inline;">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id"  value="<?= $l['id'] ?>">
                  <button type="submit"
                          class="btn-lms btn-danger btn-sm"
                          title="Delete"
                          id="btn-delete-<?= $l['id'] ?>"
                          data-confirm="Delete lecturer '<?= htmlspecialchars($l['name']) ?>'? This will also remove their course assignments.">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($pages > 1): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page-1)*15)+1 ?>–<?= min($page*15,$total) ?> of <?= $total ?> lecturers
        </div>
        <div class="pagination-controls">
          <?php if ($page>1): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page-1])) ?>" class="page-btn">
              <i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($p=max(1,$page-2); $p<=min($pages,$page+2); $p++): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$p])) ?>"
               class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page<$pages): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page+1])) ?>" class="page-btn">
              <i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php
function lecturerAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
