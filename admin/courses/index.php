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
      <div class="stat-card" style="--sc-color:#5b4efa;">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $totalC ?></div>
          <div class="stat-label">Total Courses</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#10b981;">
        <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $activeC ?></div>
          <div class="stat-label">Active</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#94a3b8;">
        <div class="stat-icon"><i class="fas fa-circle-pause"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $inactiveC ?></div>
          <div class="stat-label">Inactive</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="--sc-color:#f59e0b;">
        <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= $assignedC ?></div>
          <div class="stat-label">Lecturer Assigned</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Courses Table -->
  <div class="card-lms">
    <div class="card-lms-header students-filter-bar">
      <div class="card-lms-title">
        <i class="fas fa-table-list"></i> All Courses
        <span class="badge-lms info" style="margin-left:6px;font-size:12px;"><?= $total ?></span>
      </div>
      <form method="GET" id="filterForm" class="students-filters">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Course name or code…"
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
              <span class="badge-lms info"><?= $c['student_count'] ?> enrolled</span>
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
