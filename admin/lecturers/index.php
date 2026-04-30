<?php
// =====================================================
// ISSD Management - Admin: Lecturers List
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

<style>
/* Lecturer Table Specific Refinements */
.lect-avatar-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  overflow: hidden;
  background: #f1f5f9;
  border: 1.5px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.lect-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lect-avatar-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #fff;
  font-size: 16px;
}

.table-lms td {
  vertical-align: middle;
}

tr:hover .lect-avatar-wrap {
  transform: scale(1.05);
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(91, 78, 250, 0.15);
}

/* Photo Preview Modal */
.photo-modal {
  display: none;
  position: fixed;
  z-index: 9999;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}
.photo-modal.active {
  display: flex;
  opacity: 1;
}
.photo-modal-content {
  max-width: 90%;
  max-height: 90%;
  background: #fff;
  border-radius: 24px;
  padding: 12px;
  position: relative;
  transform: scale(0.9);
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}
.photo-modal.active .photo-modal-content {
  transform: scale(1);
}
.photo-modal-img {
  max-width: 100%;
  max-height: 80vh;
  border-radius: 16px;
  display: block;
}
.photo-modal-close {
  position: absolute;
  top: -20px; right: -20px;
  width: 40px; height: 40px;
  background: #ef4444;
  color: #fff;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  border: 4px solid #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  transition: 0.2s;
}
.photo-modal-close:hover { transform: scale(1.1); background: #dc2626; }
</style>

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
      <a href="index.php" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#5b4efa;">
          <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $totalL ?></div>
            <div class="stat-label">Total Lecturers</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?status=active" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#10b981;">
          <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $activeL ?></div>
            <div class="stat-label">Active</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?status=inactive" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#94a3b8;">
          <div class="stat-icon"><i class="fas fa-circle-pause"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $inactiveL ?></div>
            <div class="stat-label">Inactive</div>
          </div>
        </div>
      </a>
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
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Lecturer Management</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>All Lecturers</span>
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" id="filterForm" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 300px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search Name, Email, Username..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="status" class="form-control-lms filter-select"
                  style="min-width: 160px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="document.getElementById('filterForm').submit()">
            <option value="">All Status</option>
            <option value="active"   <?= $status==='active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="filter-actions d-flex gap-2">
          <button type="submit" class="btn-lms btn-primary px-4 rounded-3 shadow-sm" style="height: 46px; padding: 0 25px;">
            <i class="fas fa-filter me-1"></i> Filter
          </button>
          <?php if ($search || $status): ?>
            <a href="index.php" class="btn-lms btn-outline px-3 rounded-3 d-flex align-items-center justify-content-center" style="height: 46px; width: 46px;" title="Clear Filters">
              <i class="fas fa-xmark"></i>
            </a>
          <?php endif; ?>
        </div>
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
          <?php foreach ($lecturers as $i => $l): 
              $isHighlighted = (isset($_GET['highlight_id']) && (int)$_GET['highlight_id'] === (int)$l['id']);
              $rowClasses = [];
              if ($l['status']==='inactive') $rowClasses[] = 'course-inactive-row';
              if ($isHighlighted) $rowClasses[] = 'row-highlight';
          ?>
          <tr class="<?= implode(' ', $rowClasses) ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page-1)*15)+$i+1 ?></td>
            <td>
              <div class="d-flex align-center gap-10">
                <?php $photoUrl = lecturerPhotoUrl($l['photo']); ?>
                <div class="lect-avatar-wrap" style="cursor:pointer;" onclick="openPhotoModal('<?= htmlspecialchars($photoUrl) ?>')">
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
            <td style="font-size:13px;"><?= htmlspecialchars($l['phone'] ?: '""') ?></td>
            <td style="font-size:13px;"><?= htmlspecialchars($l['department'] ?: '""') ?></td>
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
              <?= $l['joined_date'] ? date('d M Y', strtotime($l['joined_date'])) : '""' ?>
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
          Showing <?= (($page-1)*15)+1 ?>""<?= min($page*15,$total) ?> of <?= $total ?> lecturers
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

<!-- Photo Preview Modal -->
<div class="photo-modal" id="photoModal" onclick="closePhotoModal(event)">
  <div class="photo-modal-content">
    <div class="photo-modal-close" onclick="closePhotoModal(event, true)"><i class="fas fa-times"></i></div>
    <img src="" alt="Lecturer Photo" class="photo-modal-img" id="modalImg">
  </div>
</div>
<?php
function lecturerAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}

$extraJS = <<<'JS'
<script>
function openPhotoModal(src) {
  const modal = document.getElementById('photoModal');
  const img = document.getElementById('modalImg');
  if (modal && img) {
    img.src = src;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closePhotoModal(e, force = false) {
  const modal = document.getElementById('photoModal');
  if (force || e.target.id === 'photoModal' || e.target.closest('.photo-modal-close')) {
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
}
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>

