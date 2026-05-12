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
    verifyCsrf(); // CSRF protection for state-changing POST actions
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

// ---- Bulk document counts ----
$sIds = array_map('intval', array_column($students, 'id'));
$docCounts = getBulkDocCounts($pdo, $sIds);

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
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.doc-badge i { font-size: 15px; }
body.lms-dark-mode .doc-badge i { font-size: 14px !important; }

.row-highlight {
    background-color: rgba(91, 78, 250, 0.08) !important;
    border-left: 4px solid var(--primary) !important;
}

.id-badge-lms { font-weight: 700; padding: 4px 8px; border-radius: 6px; font-size: 12px; white-space: nowrap; display: inline-block; }
.batch-badge-lms { font-weight: 600; padding: 4px 8px; border-radius: 6px; font-size: 12px; }

/* Legend Styles */
.list-legend { display: flex; flex-direction: column; align-items: flex-end; text-align: right; }
.list-legend-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); margin-bottom: 2px; }
.list-legend-title { font-size: 18px; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px; font-family: 'Poppins', sans-serif; }
.count-badge { background: var(--primary-light); color: var(--primary); padding: 2px 10px; border-radius: 30px; font-size: 13px; font-weight: 800; }

.students-filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-select { font-size: 13px !important; height: 38px !important; border-radius: 10px !important; padding: 0 12px !important; }

.btn-lms.btn-sm { 
  width: 32px !important;
  height: 32px !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 14px !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;
  transform: translateZ(0) !important;
}
.btn-lms.btn-sm i {
  margin-right: 0 !important;
  font-size: 14px !important;
  transform: none !important;
  transition: none !important;
}
.btn-lms.btn-sm:hover,
.btn-lms.btn-sm:active,
.btn-lms.btn-sm:focus {
  transform: none !important;
  top: 0 !important;
  margin: 0 !important;
  box-shadow: none !important;
}

/* Mobile Action Dots & Modal Styles */
.btn-action-dots {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: rgba(148, 163, 184, 0.1);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-size: 14px;
}
.btn-action-dots:hover {
  background: var(--primary-light);
  color: var(--primary);
}

.action-menu-list { list-style: none; padding: 0; margin: 0; }
.action-menu-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 16px 20px;
  border-radius: 14px;
  color: var(--text-main);
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
  border: 1px solid transparent;
  cursor: pointer;
  background: none;
  width: 100%;
  text-align: left;
}
.action-menu-item:hover {
  background: var(--primary-light);
  color: var(--primary);
  border-color: rgba(34, 211, 238, 0.2);
}
.action-menu-item i { width: 20px; font-size: 18px; text-align: center; }
.action-menu-item.danger:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }

.student-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  background: #f8fafc;
  padding: 24px;
  border-radius: 20px;
  margin-bottom: 20px;
  border: 1px solid rgba(0,0,0,0.05);
}
body.lms-dark-mode .student-info-grid { 
  background: rgba(255, 255, 255, 0.05) !important; 
  border-color: rgba(255, 255, 255, 0.25) !important;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
}
.info-item label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; letter-spacing: 0.8px; }
.info-item span { display: block; font-size: 15px; font-weight: 700; color: var(--text-main); }

.btn-back-actions {
  background: #f1f5f9 !important;
  color: #475569 !important;
  justify-content: center !important;
  font-weight: 700 !important;
  border: 1px solid #e2e8f0 !important;
}
body.lms-dark-mode .btn-back-actions {
  background: rgba(255, 255, 255, 0.05) !important;
  color: #cbd5e1 !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

@media (max-width: 768px) {
  #page-content { padding: 15px 15px !important; overflow-x: hidden !important; width: 100% !important; }
  .page-header { padding: 0 !important; flex-direction: column; align-items: flex-start; gap: 12px; }
  .page-header h1 { font-size: 20px !important; }
  .btn-primary-grad { width: 100% !important; justify-content: center !important; }

  .card-lms { border-radius: 24px !important; border: 1.5px solid rgba(255, 255, 255, 0.4) !important; }
  .card-lms-header { padding: 20px !important; gap: 15px !important; }
  .card-lms-body { padding: 0 !important; }
  
  #studentsTable { 
    table-layout: fixed !important; 
    width: 100% !important; 
    margin: 0 !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    border: none !important;
  }
  #studentsTable th, #studentsTable td { 
    padding: 12px 10px !important; 
    border-radius: 0 !important; 
    border-left: none !important; 
    border-right: none !important; 
  }
  
  /* Modal Mobile Tweaks (Ultra-Compact) */
  .modal-dialog { margin: 12px !important; max-width: none !important; }
  .modal-header { padding: 14px 20px !important; }
  .modal-title { font-size: 16px !important; }
  .student-info-grid { padding: 14px !important; gap: 10px !important; margin-bottom: 12px !important; border-radius: 15px !important; }
  .action-menu-item { padding: 10px 15px !important; border-radius: 10px !important; }
  .info-item label { font-size: 9px !important; margin-bottom: 2px !important; }
  .info-item span { font-size: 13px !important; }

  /* Header & Filter Compacting */
  .card-lms-header { padding: 15px 20px !important; gap: 10px !important; }
  .list-legend-title { font-size: 18px !important; }
  .list-legend-label { font-size: 8px !important; }
  .search-bar { min-width: 100% !important; }
  .search-bar input { padding: 8px 0 !important; font-size: 13px !important; }
  .filter-select { height: 38px !important; font-size: 12px !important; min-width: 120px !important; }
  .btn-lms.btn-primary { height: 38px !important; padding: 0 20px !important; font-size: 13px !important; }
  .filter-actions a.btn-lms { height: 38px !important; width: 38px !important; }

  /* Column 1: # */
  #studentsTable th:nth-child(1), #studentsTable td:nth-child(1) { 
    width: 35px !important; 
    padding-left: 10px !important; 
    text-align: center !important;
  }
  /* Column 2: Name */
  #studentsTable th:nth-child(3), #studentsTable td:nth-child(3) { 
    width: auto !important; 
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    max-width: none !important;
  }
  /* Column 7: Status Dot for Mobile */
  #studentsTable th:nth-child(7), #studentsTable td:nth-child(7) {
    display: table-cell !important;
    width: 40px !important;
    text-align: center !important;
  }
  /* Column 9: Actions */
  #studentsTable th:nth-child(9), #studentsTable td:nth-child(9) { 
    width: 45px !important; 
    padding-right: 12px !important;
    text-align: right !important;
    display: table-cell !important;
  }
  #studentsTable td:nth-child(9) {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }
  /* Column 10: Spacer */
  #studentsTable th:nth-child(10), #studentsTable td:nth-child(10) { 
    width: 5px !important; 
  }
  
  .btn-action-dots {
    width: 30px !important;
    height: 30px !important;
    font-size: 13px !important;
  }
}

/* Desktop-only Table Fixes */
@media (min-width: 769px) {
  #studentsTable {
    width: 100% !important;
    margin: 0 !important;
    border-collapse: collapse !important;
  }
  .card-lms-body {
    overflow-x: hidden !important; 
    padding: 0 !important;
  }
  #studentsTable th:first-child, 
  #studentsTable td:first-child {
    padding-left: 25px !important;
  }
  #studentsTable th:last-child, 
  #studentsTable td:last-child {
    padding-right: 25px !important;
  }
}
</style>
CSS;

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
        <div class="search-bar" style="flex: 1; min-width: 300px; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" id="searchInput" placeholder="Search Name, ID or NIC..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="batch" class="form-control-lms filter-select" id="batchFilter"
                  style="min-width: 140px; border-radius: 12px; font-weight: 600; padding: 10px 15px;"
                  onchange="document.getElementById('filterForm').submit()">
            <option value="">All Batches</option>
            <?php foreach ($batches as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>" <?= $batch === $b ? 'selected' : '' ?>>
                Batch <?= htmlspecialchars($b) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="status" class="form-control-lms filter-select" id="statusFilter"
                  style="min-width: 160px; border-radius: 12px; font-weight: 600; padding: 10px 15px;"
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

    <div class="card-lms-body" style="padding:0;">
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
      <table class="table-lms no-sticky" id="studentsTable">
        <thead>
          <tr>
            <th style="width:60px;">#</th>
            <th class="d-none d-md-table-cell" style="width:130px;">Student ID</th>
            <th style="width: auto;">Full Name</th>
            <th class="d-none d-lg-table-cell" style="width:100px;">Batch</th>
            <th class="d-none d-md-table-cell" style="width:130px;">Phone</th>
            <th class="d-none d-md-table-cell" style="width:120px;">Status</th>
            <th class="d-none d-xl-table-cell" style="width:100px;">Doc Status</th>
            <th class="d-none d-xl-table-cell" style="width:110px;">Joined</th>
            <th style="width:150px; text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s): 
              $isHighlighted = (isset($_GET['highlight_id']) && (int)$_GET['highlight_id'] === (int)$s['id']);
              $dc = $docCounts[(int)$s['id']] ?? ['collected' => 0, 'total' => 0];
              
              // Prepare JSON for Mobile Modal
              $sJson = json_encode([
                'id' => $s['id'],
                'full_name' => $s['full_name'],
                'student_id' => $s['student_id'],
                'batch' => $s['batch_number'],
                'phone' => $s['phone_number'],
                'nic' => $s['nic_number'],
                'status' => str_replace('_', ' ', ucfirst($s['status'])),
                'doc_status' => $dc['collected'] . '/' . $dc['total'],
                'join_date' => !empty($s['join_date']) ? date('d M Y', strtotime($s['join_date'])) : '—'
              ]);
          ?>
          <tr id="row-<?= $s['id'] ?>" 
              class="<?= $isHighlighted ? 'row-highlight' : '' ?>"
              onclick="if(window.innerWidth <= 768) openStudentMenu(<?= htmlspecialchars($sJson) ?>, event)">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page - 1) * 15) + $i + 1 ?></td>
            <td class="d-none d-md-table-cell">
              <span class="id-badge-lms">
                <?= htmlspecialchars($s['student_id']) ?>
              </span>
            </td>
            <td>
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
                  <div class="fw-600" style="font-size:14px;">
                    <a href="<?= BASE_URL ?>/admin/payments/add.php?student_id=<?= $s['id'] ?>" 
                       class="d-none d-md-inline" style="color:inherit;text-decoration:none;" title="Click to pay">
                       <?= htmlspecialchars($s['full_name']) ?>
                    </a>
                    <span class="d-inline d-md-none"><?= htmlspecialchars($s['full_name']) ?></span>
                  </div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['nic_number']) ?></div>
                </div>
              </div>
            </td>
            <td class="d-none d-lg-table-cell">
              <span class="batch-badge-lms">
                <?= htmlspecialchars(str_ireplace('BATCH-', '', $s['batch_number'])) ?>
              </span>
            </td>
            <td class="d-none d-md-table-cell" style="font-size:13px;"><?= htmlspecialchars($s['phone_number']) ?></td>
            <td class="d-none d-md-table-cell"><?= renderStatusBadge($s['status']) ?></td>
            <td class="d-table-cell d-md-none" style="text-align:center;">
               <?php 
                 $st_color = $s['status']==='active'?'#10b981':'#ef4444';
                 $st_shadow = $s['status']==='active'?'0 0 8px rgba(16,185,129,0.5)':'none';
               ?>
               <span style="display:inline-flex; width:8px; height:8px; background:<?= $st_color ?>; border-radius:50%; box-shadow:<?= $st_shadow ?>;"></span>
            </td>
            <td class="d-none d-xl-table-cell">
               <?php echo renderDocCountBadge($dc['collected'], $dc['total']); ?>
            </td>
            <td class="d-none d-xl-table-cell" style="font-size:12px;color:#64748b;">
              <?= !empty($s['join_date']) ? date('d M Y', strtotime($s['join_date'])) : '—' ?>
            </td>
            <td>
              <!-- Desktop View Buttons -->
              <div class="d-none d-md-flex gap-2" style="justify-content:center;">
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
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
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

              <!-- Mobile View Dots -->
              <div class="d-flex d-md-none justify-content-center">
                <button class="btn-action-dots">
                  <i class="fas fa-ellipsis-vertical"></i>
                </button>
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
          Showing <?= (($page - 1) * 15) + 1 ?> - <?= min($page * 15, $total) ?> of <?= $total ?> students
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

  <!-- Actions Modal (Mobile Only) -->
  <div class="modal fade" id="studentActionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius: 28px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background: var(--grad-primary); border: none; padding: 25px; color: #fff; position: relative;">
          <div>
            <h5 class="modal-title fw-800" id="modalStudentName" style="font-family: 'Poppins', sans-serif;">Student Name</h5>
            <div id="modalStudentID" style="font-size: 14px; opacity: 0.9; font-weight: 600;">ID: STU-000</div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
          
          <div id="studentDetailView" style="display: none;" class="animate__animated animate__fadeIn">
             <div class="student-info-grid">
                <div class="info-item">
                  <label>Batch</label>
                  <span id="infoBatch">-</span>
                </div>
                <div class="info-item">
                  <label>Status</label>
                  <span id="infoStatus">-</span>
                </div>
                <div class="info-item">
                  <label>Phone</label>
                  <span id="infoPhone">-</span>
                </div>
                <div class="info-item">
                  <label>NIC</label>
                  <span id="infoNIC">-</span>
                </div>
                <div class="info-item">
                  <label>Documents</label>
                  <span id="infoDocs">-</span>
                </div>
                <div class="info-item">
                  <label>Joined</label>
                  <span id="infoJoined">-</span>
                </div>
             </div>
             <div class="d-flex flex-column gap-2">
               <button class="action-menu-item btn-primary-grad text-white" onclick="toggleDetailView(false)" style="background: var(--grad-primary) !important; color: white !important;">
                  <i class="fas fa-list-check"></i> Manage & Actions
               </button>
               <button class="action-menu-item btn-back-actions" data-bs-dismiss="modal">
                  <i class="fas fa-times"></i> Close Details
               </button>
             </div>
          </div>

          <div id="studentActionList" class="action-menu-list">
            <button class="action-menu-item" onclick="toggleDetailView(true)">
              <i class="fas fa-arrow-left" style="color: var(--primary);"></i>
              <div>
                Back to Details
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">View student information</div>
              </div>
            </button>
            
            <a href="#" id="actionPayment" class="action-menu-item">
              <i class="fas fa-money-bill-wave" style="color: #10b981;"></i>
              <div>
                Add Payment
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Record a new payment receipt</div>
              </div>
            </a>

            <a href="#" id="actionDocs" class="action-menu-item">
              <i class="fas fa-folder-open" style="color: #5b4efa;"></i>
              <div>
                Manage Documents
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Upload or verify student documents</div>
              </div>
            </a>
            
            <a href="#" id="actionEdit" class="action-menu-item">
              <i class="fas fa-pen-to-square" style="color: #f59e0b;"></i>
              <div>
                Edit Student Info
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Update profile or contact details</div>
              </div>
            </a>
            
            <button class="action-menu-item danger" onclick="confirmDeleteStudent()">
              <i class="fas fa-trash-can" style="color: #ef4444;"></i>
              <div>
                Delete Student
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Permanently remove from system</div>
              </div>
            </button>
          </div>

          <!-- Hidden delete form for modal -->
          <form id="modalDeleteForm" method="POST" action="index.php" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="id" id="deleteStudentId">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="batch"  value="<?= htmlspecialchars($batch) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <input type="hidden" name="page"   value="<?= $page ?>">
          </form>
        </div>
      </div>
    </div>
  </div>

<script>
let currentStudent = null;

function openStudentMenu(student, event) {
  // Don't open if clicking a link or button specifically
  if (event.target.closest('a') || event.target.closest('button:not(.btn-action-dots)')) return;
  
  currentStudent = student;
  document.getElementById('modalStudentName').textContent = student.full_name;
  document.getElementById('modalStudentID').textContent = 'ID: ' + student.student_id;
  
  // Update links
  document.getElementById('actionPayment').href = `<?= BASE_URL ?>/admin/payments/add.php?student_id=${student.id}`;
  document.getElementById('actionDocs').href = `<?= BASE_URL ?>/admin/documents/manage.php?student_id=${student.id}`;
  document.getElementById('actionEdit').href = `edit.php?id=${student.id}`;
  document.getElementById('deleteStudentId').value = student.id;
  
  // Open details view directly
  toggleDetailView(true);
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('studentActionsModal'));
  modal.show();
}

function toggleDetailView(show) {
  const list = document.getElementById('studentActionList');
  const details = document.getElementById('studentDetailView');
  
  if (show) {
    // Populate details
    document.getElementById('infoBatch').textContent = currentStudent.batch;
    document.getElementById('infoStatus').textContent = currentStudent.status;
    document.getElementById('infoPhone').textContent = currentStudent.phone;
    document.getElementById('infoNIC').textContent = currentStudent.nic;
    document.getElementById('infoDocs').textContent = currentStudent.doc_status;
    document.getElementById('infoJoined').textContent = currentStudent.join_date;
    
    list.style.display = 'none';
    details.style.display = 'block';
  } else {
    list.style.display = 'block';
    details.style.display = 'none';
  }
}

function confirmDeleteStudent() {
  if (confirm(`Are you sure you want to delete student '${currentStudent.full_name}'? This cannot be undone.`)) {
    document.getElementById('modalDeleteForm').submit();
  }
}

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

