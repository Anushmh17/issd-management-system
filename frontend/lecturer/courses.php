<?php
// =====================================================
// LEARN Management - Lecturer: My Courses
// =====================================================
define('PAGE_TITLE', 'My Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);
$userId = currentUserId();

$sql = "SELECT DISTINCT c.*,
               (SELECT COUNT(*) FROM enrollments e2 WHERE e2.course_id = c.id AND e2.lecturer_id = ?) AS student_count
        FROM courses c
        JOIN enrollments e ON e.course_id = c.id
        WHERE e.lecturer_id = ? AND c.status = 'active'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $userId]);
$courses = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Courses</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Lecturer &rsaquo; <span>Courses</span></div>
    </div>
  </div>

  <div class="row g-4">
    <?php if (empty($courses)): ?>
        <div class="col-12"><div class="card-lms"><div class="empty-state"><i class="fas fa-book-open"></i><p>You have not been assigned to any active courses yet.</p></div></div></div>
    <?php else: ?>
        <?php foreach ($courses as $c): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card-lms h-100" style="position:relative;overflow:hidden;border:1.5px solid var(--primary-light);">
            <div style="position:absolute;top:0;left:0;right:0;height:4px;background:var(--primary);"></div>
            <div class="card-lms-body">
              <div class="d-flex justify-between align-center mb-3">
                  <span class="badge-lms primary"><?= htmlspecialchars($c['course_code']) ?></span>
                  <span class="text-muted" style="font-size:12px;"><i class="fas fa-clock"></i> <?= htmlspecialchars($c['duration']) ?></span>
              </div>
              <h3 class="fw-700 mb-2" style="font-size:18px;color:var(--text-main);"><?= htmlspecialchars($c['course_name']) ?></h3>
              <p class="text-muted mb-4" style="font-size:13px;line-height:1.5;min-height:40px;">
                  <?= htmlspecialchars($c['description'] ?? 'No description provided.') ?>
              </p>
              
              <div class="d-flex justify-between align-center" style="border-top:1px solid var(--border-color);padding-top:15px;">
                  <div>
                      <div class="fw-700" style="font-size:16px;color:var(--accent);"><?= $c['student_count'] ?></div>
                      <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">My Students</div>
                  </div>
                  <a href="<?= BASE_URL ?>/frontend/lecturer/students.php?q=<?= urlencode($c['course_name']) ?>" class="btn-lms btn-outline btn-sm">View Students <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
