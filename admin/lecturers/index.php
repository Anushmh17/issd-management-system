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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['act'] ?? '';
    if ($act === 'delete') {
        $lid = (int)($_POST['id'] ?? 0);
        if (deleteLecturer($pdo, $lid)) {
            setFlash('success', 'Lecturer deleted successfully.');
        } else {
            setFlash('danger', 'Failed to delete lecturer.');
        }
        header('Location: index.php'); exit;
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
  /* --- PREMIUM BORDER REINFORCEMENT (FORCE APPLIED) --- */
  body.lms-dark-mode .card-lms,
  body.lms-dark-mode .stat-card,
  body.lms-dark-mode .bento-card {
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4), 0 0 15px rgba(255, 255, 255, 0.03) !important;
    border-radius: 24px !important;
    background: rgba(30, 41, 59, 0.5) !important;
    backdrop-filter: blur(20px) !important;
  }
</style>

<style>
/* Lecturer Table Specific Refinements */
.lect-avatar-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  overflow: hidden;
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

/* Username Tag & Status Fixes */
.username-tag {
  font-family: 'Inter', monospace;
  font-size: 11px;
  font-weight: 700;
  background: #f0edff;
  color: #5b4efa;
  padding: 4px 10px;
  border-radius: 8px;
  border: 1.5px solid #e0d9ff;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

body.lms-dark-mode .username-tag {
  background: rgba(91, 78, 250, 0.15) !important;
  color: #a5b4fc !important;
  border-color: rgba(91, 78, 250, 0.3) !important;
}

body.lms-dark-mode .lect-avatar-wrap {
  border-color: rgba(255, 255, 255, 0.25) !important;
}

body.lms-dark-mode .table-lms td {
  color: #e2e8f0;
}

.btn-action-dots {
  width: 30px !important; height: 30px !important; border-radius: 8px !important;
  background: rgba(91, 78, 250, 0.1) !important; color: var(--primary) !important;
  display: flex !important; align-items: center; justify-content: center; border: none !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
}
.btn-action-dots:hover {
  background: var(--primary) !important;
  color: #fff !important;
  transform: translateY(-1px);
}

/* Mobile Responsive Adjustments */
@media (max-width: 768px) {
  #page-content { padding: 15px 15px !important; }
  .page-header { padding: 0 !important; flex-direction: column; align-items: flex-start; gap: 12px; }
  .page-header h1 { font-size: 20px !important; }
  .btn-primary-grad { width: 100% !important; justify-content: center !important; }
  
  .card-lms { border-radius: 24px !important; border: 1.5px solid rgba(255, 255, 255, 0.4) !important; }
  .card-lms-header { padding: 20px !important; gap: 15px !important; }
  .list-legend-title { font-size: 18px !important; }
  .list-legend-label { font-size: 8px !important; }
  .search-bar { min-width: 100% !important; }
  .search-bar input { padding: 8px 0 !important; font-size: 13px !important; }
  .filter-select { height: 38px !important; font-size: 12px !important; min-width: 120px !important; }
  .btn-lms.btn-primary { height: 38px !important; padding: 0 20px !important; font-size: 13px !important; }
  .filter-actions a.btn-lms { height: 38px !important; width: 38px !important; }

  .card-lms-body { padding: 0 !important; overflow-x: hidden !important; }
  #lecturersTable { 
    table-layout: fixed !important; 
    width: 100% !important; 
    margin: 0 !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    border: none !important;
  }
  #lecturersTable th, #lecturersTable td { 
    padding: 12px 10px !important; 
    border-radius: 0 !important; 
    border-left: none !important; 
    border-right: none !important; 
  }

  /* Column Visibility */
  #lecturersTable th:nth-child(3), #lecturersTable td:nth-child(3),
  #lecturersTable th:nth-child(4), #lecturersTable td:nth-child(4),
  #lecturersTable th:nth-child(5), #lecturersTable td:nth-child(5),
  #lecturersTable th:nth-child(6), #lecturersTable td:nth-child(6),
  #lecturersTable th:nth-child(8), #lecturersTable td:nth-child(8) { display: none !important; }

  /* Column Widths */
  #lecturersTable th:nth-child(1), #lecturersTable td:nth-child(1) { 
    width: 35px !important; 
    padding-left: 10px !important; 
    text-align: center !important; 
  }
  #lecturersTable th:nth-child(2), #lecturersTable td:nth-child(2) { 
    width: auto !important; 
    max-width: none !important; /* Let it grow */
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
  }
  #lecturersTable th:nth-child(7), #lecturersTable td:nth-child(7) { 
    width: 60px !important; 
    text-align: center !important; 
  }
  /* Column 9: Actions */
  #lecturersTable th:nth-child(9), #lecturersTable td:nth-child(9) { 
    width: 45px !important; 
    padding-right: 12px !important;
    text-align: right !important;
  }
  #lecturersTable td:nth-child(9) {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }

  }
  
  /* Modal Ultra-Compact */
  .modal-dialog { margin: 12px !important; max-width: none !important; }
  .modal-header { padding: 14px 20px !important; }
  .modal-title { font-size: 16px !important; }
  .info-grid-lms { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: rgba(0,0,0,0.03); padding: 14px; border-radius: 15px; margin-bottom: 12px; }
  .info-item label { font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px; }
  .info-item span { font-size: 13px; font-weight: 700; color: var(--dark); }
  .action-menu-item { width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); background: #fff; text-decoration: none; color: inherit; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
  .action-menu-item i { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(91,78,250,0.1); color: var(--primary); border-radius: 8px; font-size: 14px; }
}
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
        <div class="search-bar" style="flex: 1; min-width: 300px; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search Name, Email, Username..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="status" class="form-control-lms filter-select"
                  style="min-width: 160px; border-radius: 12px; font-weight: 600; padding: 10px 15px;"
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
            <th style="text-align:center;" class="d-none d-md-table-cell">Actions</th>
            <th class="d-table-cell d-md-none" style="text-align:center;">Actions</th>
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
              <span class="username-tag">
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
                <span class="badge-lms success d-none d-md-inline-flex">
                  <i class="fas fa-circle-dot" style="font-size:8px;"></i> Active
                </span>
                <span class="d-md-none" style="display:inline-flex; width:10px; height:10px; background:#10b981; border-radius:50%; box-shadow:0 0 8px rgba(16,185,129,0.5);"></span>
              <?php else: ?>
                <span class="badge-lms d-none d-md-inline-flex" style="background:rgba(255,255,255,0.05); color:#94a3b8; border:1px solid rgba(255,255,255,0.1);">
                  <i class="fas fa-circle-dot" style="font-size:8px;"></i> Inactive
                </span>
                <span class="d-md-none" style="display:inline-flex; width:10px; height:10px; background:#ef4444; border-radius:50%;"></span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:#64748b;">
              <?= $l['joined_date'] ? date('d M Y', strtotime($l['joined_date'])) : '""' ?>
            </td>
            <td class="d-none d-md-table-cell">
              <div class="d-flex gap-6" style="justify-content:center;">
                <a href="edit.php?id=<?= $l['id'] ?>"
                   class="btn-lms btn-outline btn-sm"
                   title="Edit Lecturer" id="btn-edit-<?= $l['id'] ?>">
                  <i class="fas fa-pen-to-square"></i>
                </a>
                <form method="POST" action="index.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id"  value="<?= $l['id'] ?>">
                  <button type="submit"
                          class="btn-lms btn-danger btn-sm"
                          title="Delete"
                          id="btn-delete-<?= $l['id'] ?>"
                          data-confirm="Delete lecturer '<?= htmlspecialchars($l['name']) ?>'? This will also remove their course assignments."
                          data-confirm-type="danger">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
            <td class="d-table-cell d-md-none" style="text-align:center;">
              <?php $lJson = json_encode(['id'=>$l['id'],'name'=>$l['name'],'email'=>$l['email'],'username'=>$l['username'],'phone'=>$l['phone'],'dept'=>$l['department'],'status'=>$l['status'],'joined'=>date('d M Y',strtotime($l['joined_date']))]); ?>
              <button class="btn-action-dots" onclick="openLecturerMenu(<?= htmlspecialchars($lJson) ?>, event)">
                <i class="fas fa-ellipsis-vertical"></i>
              </button>
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

<!-- Lecturer Actions & Details Modal -->
<div class="modal fade" id="lecturerActionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #f8fafc;">
      <div class="modal-header" style="background: var(--grad-primary); border: none; padding: 25px; color: #fff; position: relative;">
        <div style="position: relative; z-index: 2;">
          <h5 class="modal-title fw-800 mb-0" id="modalLectName">Lecturer Name</h5>
          <div id="modalLectUser" style="font-size: 13px; opacity: 0.9; font-weight: 500;">@username</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 25px; right: 25px; z-index: 3;"></button>
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%); z-index: 1;"></div>
      </div>
      <div class="modal-body p-4">
          <div id="lecturerDetailView" class="animate__animated animate__fadeIn">
             <div class="info-grid-lms">
                <div class="info-item">
                  <label>Department</label>
                  <span id="infoDept">-</span>
                </div>
                <div class="info-item">
                  <label>Phone</label>
                  <span id="infoPhone">-</span>
                </div>
                <div class="info-item">
                  <label>Email</label>
                  <span id="infoEmail">-</span>
                </div>
                <div class="info-item">
                  <label>Joined</label>
                  <span id="infoJoined">-</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                  <label>Status</label>
                  <span id="infoStatus">-</span>
                </div>
             </div>
             <div class="d-flex flex-column gap-2">
               <button class="action-menu-item btn-primary-grad text-white" onclick="toggleDetailView(false)" style="background: var(--grad-primary) !important; color: white !important;">
                  <i class="fas fa-list-check"></i> Manage & Actions
               </button>
               <button class="action-menu-item" data-bs-dismiss="modal">
                  <i class="fas fa-times"></i> Close Details
               </button>
             </div>
          </div>

          <div id="lecturerActionList" class="action-menu-list" style="display: none;">
            <button class="action-menu-item" onclick="toggleDetailView(true)">
              <i class="fas fa-arrow-left" style="color: var(--primary);"></i>
              <div>
                Back to Details
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">View lecturer info</div>
              </div>
            </button>
            <a href="#" id="actionEdit" class="action-menu-item">
              <i class="fas fa-pen-to-square"></i>
              <div>
                Edit Profile
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Update information</div>
              </div>
            </a>
            <a href="#" id="actionAssign" class="action-menu-item">
              <i class="fas fa-chalkboard-user"></i>
              <div>
                Assign to Course
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Manage teaching load</div>
              </div>
            </a>
            <form id="deleteForm" method="POST" action="index.php" onsubmit="return confirm('Delete this lecturer?');">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" id="deleteLectId" value="">
              <button type="submit" class="action-menu-item" style="color: #ef4444; width: 100%; text-align: left; border: 1px solid rgba(239, 68, 68, 0.1); background: rgba(239, 68, 68, 0.02);">
                <i class="fas fa-trash-can" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"></i>
                <div>
                  Delete Lecturer
                  <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Remove permanently</div>
                </div>
              </button>
            </form>
          </div>
      </div>
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
function openLecturerMenu(lecturer, event) {
  if (event.target.closest('a') || event.target.closest('button:not(.btn-action-dots)')) return;
  
  document.getElementById('modalLectName').textContent = lecturer.name;
  document.getElementById('modalLectUser').textContent = '@' + lecturer.username;
  
  // Update Details
  document.getElementById('infoDept').textContent = lecturer.dept || 'N/A';
  document.getElementById('infoPhone').textContent = lecturer.phone || 'N/A';
  document.getElementById('infoEmail').textContent = lecturer.email;
  document.getElementById('infoJoined').textContent = lecturer.joined;
  document.getElementById('infoStatus').textContent = lecturer.status.charAt(0).toUpperCase() + lecturer.status.slice(1);
  
  // Update Links
  document.getElementById('actionEdit').href = `edit.php?id=${lecturer.id}`;
  document.getElementById('actionAssign').href = `../courses/assign_lecturer.php?lecturer_id=${lecturer.id}`;
  document.getElementById('deleteLectId').value = lecturer.id;
  
  toggleDetailView(true);
  const modal = new bootstrap.Modal(document.getElementById('lecturerActionsModal'));
  modal.show();
}

function toggleDetailView(show) {
  document.getElementById('lecturerDetailView').style.display = show ? 'block' : 'none';
  document.getElementById('lecturerActionList').style.display = show ? 'none' : 'block';
}

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


?>


<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>