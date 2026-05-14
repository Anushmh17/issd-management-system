<?php
// =====================================================
// ISSD Management - Admin: Courses List
// admin/courses/index.php
// =====================================================
define('PAGE_TITLE', 'Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

// ---- Handle DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['act'] ?? '') === 'delete') {
        $cid = (int)($_POST['id'] ?? 0);
        if (deleteCourse($pdo, $cid)) {
            setFlash('success', 'Course deleted successfully.');
        } else {
            setFlash('danger', 'Failed to delete course. Ensure no students are enrolled in this course before deleting.');
        }
        header('Location: index.php'); exit;
    }
}

// ---- Filters ----
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'status');
$result  = getCoursesList($pdo, $filters, $page, 15);
$courses = $result['courses'];
$total   = $result['total'];
$pages   = $result['pages'];

// ---- Stats ----
$totalC    = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$activeC   = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE status='active'")->fetchColumn();
$inactiveC = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE status='inactive'")->fetchColumn();
$assignedC = (int)$pdo->query("SELECT COUNT(DISTINCT course_id) FROM course_assignments")->fetchColumn();

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
/* Courses Page Mobile Adjustments */
@media (max-width: 768px) {
  #page-content { padding: 16px 20px; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 16px; margin-bottom: 20px; }
  .page-header .d-flex { width: 100%; flex-wrap: wrap; gap: 8px !important; }
  .page-header .btn-lms { flex: 1; min-width: 140px; padding: 10px 12px; font-size: 12px; justify-content: center; }
  .page-header .btn-primary-grad { width: 100%; justify-content: center; padding: 12px; }
  
  .stat-card { padding: 15px 12px !important; gap: 12px !important; border-radius: 18px !important; }
  .stat-card .stat-icon { width: 38px !important; height: 38px !important; font-size: 16px !important; border-radius: 12px !important; }
  .stat-card .stat-value { font-size: 20px !important; }
  .stat-card .stat-label { font-size: 9.5px !important; margin-top: 2px !important; }
  
  .students-filter-bar { flex-direction: column; align-items: flex-start !important; padding: 20px 16px !important; gap: 16px; }
  .students-filters { flex-direction: column; width: 100%; gap: 12px !important; }
  .search-bar { max-width: none !important; }
  .filter-select { width: 100% !important; }
  .students-filters .btn-lms { width: 100%; justify-content: center; }

  .enroll-badge { flex-direction: column !important; padding: 6px 10px !important; gap: 0 !important; line-height: 1.1; }
  .enroll-badge .count { font-size: 13px; font-weight: 800; }
  .enroll-badge .label { font-size: 8.5px; text-transform: uppercase; opacity: 0.8; letter-spacing: 0.5px; }
}

/* Count Badge & Dark Mode Polish */
.count-badge {
  background: var(--primary-light);
  color: var(--primary);
  padding: 4px 14px;
  border-radius: 30px;
  font-size: 14px;
  font-weight: 800;
  display: inline-block;
  margin-left: 8px;
}

body.lms-dark-mode .count-badge {
  background: rgba(34, 211, 238, 0.15) !important;
  color: #22d3ee !important;
  border: 1px solid rgba(34, 211, 238, 0.2);
}

body.lms-dark-mode .list-legend-label {
  background: rgba(34, 211, 238, 0.15) !important;
  color: #22d3ee !important;
  font-weight: 800;
}

body.lms-dark-mode .table-lms td { color: #e2e8f0; }

.course-code-badge { background: #f8fafc; color: #475569; border: 1.5px solid #e2e8f0; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; font-family: monospace; }
body.lms-dark-mode .course-code-badge { background: rgba(255, 255, 255, 0.05) !important; color: #cbd5e1 !important; border-color: rgba(255, 255, 255, 0.1) !important; }

  .course-fee-badge { background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 800; }
body.lms-dark-mode .course-fee-badge { background: rgba(16, 185, 129, 0.1) !important; color: #34d399 !important; }

/* Mobile Responsive Adjustments */
@media (max-width: 768px) {
  #page-content { padding: 15px 15px !important; }
  .page-header { padding: 0 !important; flex-direction: column; align-items: flex-start; gap: 12px; }
  .page-header h1 { font-size: 20px !important; }
  .page-header .d-flex { width: 100% !important; flex-direction: column !important; }
  .btn-primary-grad { width: 100% !important; justify-content: center !important; }
  .btn-lms { width: 100% !important; justify-content: center !important; }
  
  .card-lms { border-radius: 24px !important; border: 1.5px solid rgba(255, 255, 255, 0.4) !important; }
  .card-lms-header { padding: 20px !important; gap: 15px !important; }
  .card-lms-body { padding: 0 !important; overflow-x: auto !important; }
  .list-legend-title { font-size: 18px !important; }
  .list-legend-label { font-size: 8px !important; }
  .search-bar { min-width: 100% !important; }
  .search-bar input { padding: 8px 0 !important; font-size: 13px !important; }
  .filter-select { height: 38px !important; font-size: 12px !important; min-width: 120px !important; }
  .btn-lms.btn-primary { height: 38px !important; padding: 0 20px !important; font-size: 13px !important; }
  .filter-actions a.btn-lms { height: 38px !important; width: 38px !important; }

  .card-lms-body { padding: 0 !important; overflow-x: hidden !important; }
  #coursesTable th:nth-child(2), #coursesTable td:nth-child(2) { width: auto !important; }
  /* Column 9: Actions */
  #coursesTable th:nth-child(9), #coursesTable td:nth-child(9) { 
    width: 45px !important; 
    padding-right: 12px !important;
    text-align: right !important;
    display: table-cell !important;
  }
  #coursesTable td:nth-child(9) {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
  }

  /* Column Visibility */
  #coursesTable th:nth-child(3), #coursesTable td:nth-child(3),
  #coursesTable th:nth-child(4), #coursesTable td:nth-child(4),
  #coursesTable th:nth-child(6), #coursesTable td:nth-child(6),
  #coursesTable th:nth-child(7), #coursesTable td:nth-child(7) { display: none !important; }

  /* Column Widths — final values only */
  #coursesTable th:nth-child(1), #coursesTable td:nth-child(1) { width: 35px !important; padding-left: 10px !important; text-align: center !important; }
  #coursesTable th:nth-child(2), #coursesTable td:nth-child(2) { width: auto !important; }
  #coursesTable th:nth-child(5), #coursesTable td:nth-child(5) { width: 100px !important; }
  #coursesTable th:nth-child(8), #coursesTable td:nth-child(8) { width: 90px !important; text-align: center !important; }

  .btn-action-dots {
    width: 30px !important; height: 30px !important; border-radius: 8px !important;
    background: rgba(34, 211, 238, 0.1) !important; color: #0891b2 !important;
    display: flex !important; align-items: center; justify-content: center; border: none !important;
  }
  
  /* Modal Ultra-Compact */
  .modal-dialog { margin: 12px !important; max-width: none !important; }
  .modal-header { padding: 14px 20px !important; }
  .modal-title { font-size: 16px !important; }
  .info-grid-lms { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: rgba(0,0,0,0.03); padding: 14px; border-radius: 15px; margin-bottom: 12px; }
  .info-item label { font-size: 9px; opacity: 0.6; text-transform: uppercase; display: block; margin-bottom: 2px; }
  .info-item span { font-size: 13px; font-weight: 700; color: var(--dark); }
  .action-menu-item { width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); background: #fff; text-decoration: none; color: inherit; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
  .action-menu-item i { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(34, 211, 238, 0.1); color: #0891b2; border-radius: 8px; font-size: 14px; }
}
</style>

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Courses Management</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Courses</span>
      </div>
    </div>
    <div class="d-flex gap-10">
      <a href="assign_lecturer.php" class="btn-lms btn-outline" id="btn-assign-lecturer">
        <i class="fas fa-chalkboard-user"></i> Assign Lecturer
      </a>
      <a href="assign_student.php" class="btn-lms btn-outline" id="btn-assign-student">
        <i class="fas fa-user-graduate"></i> Enroll Student
      </a>
      <a href="add.php" class="btn-primary-grad" id="btn-add-course">
        <i class="fas fa-plus"></i> Add Course
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-20">
    <div class="col-6 col-md-3">
      <a href="index.php" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#5b4efa;">
          <div class="stat-icon"><i class="fas fa-book-open"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $totalC ?></div>
            <div class="stat-label">Total Courses</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?status=active" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#10b981;">
          <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $activeC ?></div>
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
            <div class="stat-value"><?= $inactiveC ?></div>
            <div class="stat-label">Inactive</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="assign_lecturer.php" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#f59e0b;">
          <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $assignedC ?></div>
            <div class="stat-label">Lecturer Assigned</div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Courses Table -->
  <div class="card-lms">
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Course Management</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>All Courses</span>
            <span class="count-badge"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" id="filterForm" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 300px; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search Course Name or Code..."
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
      <?php if (empty($courses)): ?>
        <div class="empty-state">
          <i class="fas fa-book-open"></i>
          <p>No courses found<?= ($search||$status)?' matching your filters.':'.'; ?></p>
          <?php if (!$search && !$status): ?>
            <a href="add.php" class="btn-lms btn-primary mt-10">
              <i class="fas fa-plus"></i> Add First Course
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="table-lms" id="coursesTable">
        <thead>
          <tr>
            <th style="width: 50px;">#</th>
            <th>Course</th>
            <th style="width: 90px;">Code</th>
            <th style="width: 100px;">Duration</th>
            <th style="width: 120px;">Monthly Fee</th>
            <th style="width: 160px;">Assigned Lecturer</th>
            <th style="width: 100px;">Students</th>
            <th style="width: 100px;">Status</th>
            <th style="width: 140px; text-align:center;" class="d-none d-md-table-cell">Actions</th>
            <th class="d-table-cell d-md-none" style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $i => $c): ?>
          <tr class="<?= $c['status']==='inactive' ? 'course-inactive-row' : '' ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page-1)*15)+$i+1 ?></td>
            <td>
              <div>
                <div class="fw-600" style="font-size:13px; line-height: 1.2;"><?= htmlspecialchars($c['course_name']) ?></div>
                <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars(substr($c['description'] ?? '', 0, 45)) ?>...</div>
              </div>
            </td>
            <td>
              <span class="course-code-badge"><?= htmlspecialchars($c['course_code']) ?></span>
            </td>
            <td style="font-size:13px;">
              <i class="fas fa-clock" style="color:#94a3b8;margin-right:4px;"></i>
              <?= htmlspecialchars($c['duration'] ?: '""') ?>
            </td>
            <td>
              <span class="course-fee-badge">Rs. <?= number_format((float)$c['monthly_fee'], 0) ?>/mo</span>
            </td>
            <td>
              <?php if ($c['lecturer_name']): ?>
                <span style="font-size:13px; font-weight: 600;"><i class="fas fa-user-tie me-2 opacity-50"></i> <?= htmlspecialchars($c['lecturer_name']) ?></span>
              <?php else: ?>
                <span style="font-size:12px;color:#94a3b8;font-style:italic;">Not assigned</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge-lms info enroll-badge">
                <span class="count"><?= $c['student_count'] ?></span>
                <span class="label">Enrolled</span>
              </span>
            </td>
            <td>
              <?php if ($c['status'] === 'active'): ?>
                <span class="badge-lms" style="background:#d1fae5;color:#059669;border:1px solid #a7f3d0;">
                  <i class="fas fa-circle-dot" style="font-size:8px;"></i> Active
                </span>
              <?php else: ?>
                <span class="badge-lms" style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">
                  <i class="fas fa-circle-dot" style="font-size:8px;"></i> Inactive
                </span>
              <?php endif; ?>
            </td>
            <td class="d-none d-md-table-cell" style="text-align:center; vertical-align: middle;">
              <div class="d-flex gap-2 justify-content-center">
                <a href="assign_lecturer.php?course_id=<?= $c['id'] ?>"
                   class="btn-lms btn-sm"
                   style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;"
                   title="Assign Lecturer" id="btn-lect-<?= $c['id'] ?>">
                  <i class="fas fa-chalkboard-user"></i>
                </a>
                <a href="edit.php?id=<?= $c['id'] ?>"
                   class="btn-lms btn-outline btn-sm"
                   title="Edit Course" id="btn-edit-<?= $c['id'] ?>">
                  <i class="fas fa-pen-to-square"></i>
                </a>
                <form method="POST" action="index.php" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id"  value="<?= $c['id'] ?>">
                  <button type="submit"
                          class="btn-lms btn-danger btn-sm"
                          title="Delete Course"
                          id="btn-delete-<?= $c['id'] ?>"
                          data-confirm="Delete course '<?= htmlspecialchars($c['course_name']) ?>'? All enrollments will also be removed.">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
            <td class="d-table-cell d-md-none" style="text-align:center; vertical-align: middle;">
              <?php $cJson = json_encode(['id'=>$c['id'],'name'=>$c['course_name'],'code'=>$c['course_code'],'duration'=>$c['duration'],'fee'=>'Rs. '.number_format($c['monthly_fee'],0),'lecturer'=>$c['lecturer_name'] ?: 'Not assigned','students'=>$c['student_count'],'status'=>$c['status']]); ?>
              <button class="btn-action-dots" onclick="openCourseMenu(<?= htmlspecialchars($cJson) ?>, event)">
                <i class="fas fa-ellipsis-vertical"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page-1)*15)+1 ?>""<?= min($page*15,$total) ?> of <?= $total ?> courses
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

<!-- Course Actions & Details Modal -->
<div class="modal fade" id="courseActionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #f8fafc;">
      <div class="modal-header" style="background: var(--grad-primary); border: none; padding: 25px; color: #fff; position: relative;">
        <div style="position: relative; z-index: 2;">
          <h5 class="modal-title fw-800 mb-0" id="modalCourseName">Course Name</h5>
          <div id="modalCourseCode" style="font-size: 13px; opacity: 0.9; font-weight: 500;">CODE123</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 25px; right: 25px; z-index: 3;"></button>
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%); z-index: 1;"></div>
      </div>
      <div class="modal-body p-4">
          <div id="courseDetailView" class="animate__animated animate__fadeIn">
             <div class="info-grid-lms">
                <div class="info-item">
                  <label>Duration</label>
                  <span id="infoDuration">-</span>
                </div>
                <div class="info-item">
                  <label>Monthly Fee</label>
                  <span id="infoFee">-</span>
                </div>
                <div class="info-item">
                  <label>Students</label>
                  <span id="infoStudents">0</span>
                </div>
                <div class="info-item">
                  <label>Status</label>
                  <span id="infoStatus">-</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                  <label>Assigned Lecturer</label>
                  <span id="infoLecturer">-</span>
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

          <div id="courseActionList" class="action-menu-list" style="display: none;">
            <button class="action-menu-item" onclick="toggleDetailView(true)">
              <i class="fas fa-arrow-left" style="color: var(--primary);"></i>
              <div>
                Back to Details
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">View course info</div>
              </div>
            </button>
            <a href="#" id="actionEdit" class="action-menu-item">
              <i class="fas fa-pen-to-square"></i>
              <div>
                Edit Course
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Update information</div>
              </div>
            </a>
            <a href="#" id="actionLecturer" class="action-menu-item">
              <i class="fas fa-chalkboard-user"></i>
              <div>
                Assign Lecturer
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Manage instructor</div>
              </div>
            </a>
            <form id="deleteForm" method="POST" action="index.php" onsubmit="return confirm('Delete this course?');">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" id="deleteCourseId" value="">
              <button type="submit" class="action-menu-item" style="color: #ef4444; width: 100%; text-align: left; border: 1px solid rgba(239, 68, 68, 0.1); background: rgba(239, 68, 68, 0.02);">
                <i class="fas fa-trash-can" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"></i>
                <div>
                  Delete Course
                  <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Remove permanently</div>
                </div>
              </button>
            </form>
          </div>
      </div>
    </div>
  </div>
</div>

<script>
function openCourseMenu(course, event) {
  if (event.target.closest('a') || event.target.closest('button:not(.btn-action-dots)')) return;
  
  document.getElementById('modalCourseName').textContent = course.name;
  document.getElementById('modalCourseCode').textContent = course.code;
  
  // Update Details
  document.getElementById('infoDuration').textContent = course.duration || 'N/A';
  document.getElementById('infoFee').textContent = course.fee;
  document.getElementById('infoStudents').textContent = course.students;
  document.getElementById('infoStatus').textContent = course.status.charAt(0).toUpperCase() + course.status.slice(1);
  document.getElementById('infoLecturer').textContent = course.lecturer;
  
  // Update Links
  document.getElementById('actionEdit').href = `edit.php?id=${course.id}`;
  document.getElementById('actionLecturer').href = `assign_lecturer.php?course_id=${course.id}`;
  document.getElementById('deleteCourseId').value = course.id;
  
  toggleDetailView(true);
  const modal = new bootstrap.Modal(document.getElementById('courseActionsModal'));
  modal.show();
}

function toggleDetailView(show) {
  document.getElementById('courseDetailView').style.display = show ? 'block' : 'none';
  document.getElementById('courseActionList').style.display = show ? 'none' : 'block';
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

