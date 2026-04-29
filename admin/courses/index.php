<?php
// =====================================================
// LEARN Management - Admin: Courses List
// admin/courses/index.php
// =====================================================
define('PAGE_TITLE', 'Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/course_controller.php';

requireRole(ROLE_ADMIN);

// ---- Handle DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'delete') {
    $cid = (int)($_POST['id'] ?? 0);
    if (deleteCourse($pdo, $cid)) {
        setFlash('success', 'Course deleted successfully.');
    } else {
        setFlash('danger', 'Failed to delete course.');
    }
    header('Location: index.php'); exit;
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
  .search-bar { max-width: none !important; background: #f8fafc; }
  .filter-select { width: 100% !important; }
  .students-filters .btn-lms { width: 100%; justify-content: center; }

  .enroll-badge { flex-direction: column !important; padding: 6px 10px !important; gap: 0 !important; line-height: 1.1; }
  .enroll-badge .count { font-size: 13px; font-weight: 800; }
  .enroll-badge .label { font-size: 8.5px; text-transform: uppercase; opacity: 0.8; letter-spacing: 0.5px; }
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
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" id="filterForm" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 300px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search Course Name or Code…"
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
            <th>#</th>
            <th>Course</th>
            <th>Code</th>
            <th>Duration</th>
            <th>Monthly Fee</th>
            <th>Assigned Lecturer</th>
            <th>Students</th>
            <th>Status</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $i => $c): ?>
          <tr class="<?= $c['status']==='inactive' ? 'course-inactive-row' : '' ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page-1)*15)+$i+1 ?></td>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="course-icon-box">
                  <i class="fas fa-book-open"></i>
                </div>
                <div>
                  <div class="fw-600" style="font-size:14px;"><?= htmlspecialchars($c['course_name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars(substr($c['description'] ?? '', 0, 50)) ?>…</div>
                </div>
              </div>
            </td>
            <td>
              <span class="course-code-badge"><?= htmlspecialchars($c['course_code']) ?></span>
            </td>
            <td style="font-size:13px;">
              <i class="fas fa-clock" style="color:#94a3b8;margin-right:4px;"></i>
              <?= htmlspecialchars($c['duration'] ?: '—') ?>
            </td>
            <td>
              <span class="course-fee-badge">Rs. <?= number_format((float)$c['monthly_fee'], 0) ?>/mo</span>
            </td>
            <td>
              <?php if ($c['lecturer_name']): ?>
                <div class="d-flex align-center gap-6">
                  <div class="avatar-initials" style="width:26px;height:26px;font-size:11px;background:#5b4efa;">
                    <?= strtoupper(substr($c['lecturer_name'], 0, 1)) ?>
                  </div>
                  <span style="font-size:13px;"><?= htmlspecialchars($c['lecturer_name']) ?></span>
                </div>
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
            <td>
              <div class="d-flex gap-6" style="justify-content:center;">
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
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page-1)*15)+1 ?>–<?= min($page*15,$total) ?> of <?= $total ?> courses
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

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
