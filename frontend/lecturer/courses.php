<?php
// =====================================================
// ISSD Management - Lecturer: My Courses
// =====================================================
define('PAGE_TITLE', 'My Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);
$userId = currentUserId();
$lecturerId = (isset($_SESSION['lecturer_id'])) ? $_SESSION['lecturer_id'] : $userId;

$sql = "SELECT DISTINCT c.*,
               (SELECT COUNT(DISTINCT student_id) FROM (
                   SELECT student_id, course_id FROM enrollments
                   UNION
                   SELECT student_id, course_id FROM student_courses
               ) as all_stu WHERE all_stu.course_id = c.id) AS student_count
        FROM courses c
        JOIN course_assignments ca ON ca.course_id = c.id
        WHERE ca.lecturer_id = ? AND c.status = 'active'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$lecturerId]);
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
        <div class="col-12">
          <div class="glass-card">
            <div class="empty-state">
              <i class="fas fa-book-open" style="font-size: 48px; color: var(--primary); opacity: 0.5;"></i>
              <p style="font-size: 16px; margin-top: 15px;">You have not been assigned to any active courses yet.</p>
            </div>
          </div>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $c): ?>
        <div class="col-md-6 col-lg-4">
          <div class="glass-card h-100 premium-hover-card" style="padding: 28px; position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
            <!-- Left Side Accent -->
            <div style="position: absolute; top: 0; left: 0; bottom: 0; width: 4px; background: linear-gradient(to bottom, var(--primary), var(--accent));"></div>
            
            <div class="card-content-wrap" style="display: flex; flex-direction: column; height: 100%;">
              <div class="d-flex justify-content-between align-items-center mb-20">
                  <div class="badge-lms primary" style="padding: 6px 12px; font-weight: 800; font-size: 10px; letter-spacing: 1px; border-radius: 8px; background: rgba(34, 211, 238, 0.15); color: var(--primary);"><?= htmlspecialchars($c['course_code']) ?></div>
                  <div class="d-flex align-items-center gap-6 text-muted fw-700" style="font-size:10px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-clock" style="color:var(--primary); font-size: 12px;"></i> <?= htmlspecialchars($c['duration']) ?>
                  </div>
              </div>
              
              <h3 class="fw-800 mb-12" style="font-size:22px; color:var(--text-main); letter-spacing: -0.5px; line-height: 1.2;">
                <?= htmlspecialchars($c['course_name']) ?>
              </h3>
              
              <div class="text-muted mb-24" style="font-size:13px; line-height:1.6; min-height:40px; opacity: 0.8;">
                  <?= htmlspecialchars($c['description'] ?? 'Explore the core principles and advanced techniques of this comprehensive module.') ?>
              </div>
              
              <div class="d-flex justify-content-between align-items-center mt-auto">
                  <div class="d-flex align-items-center" style="gap: 20px;">
                      <div class="stat-mini-icon shadow-sm" style="background: linear-gradient(135deg, rgba(34, 211, 238, 0.2), rgba(34, 211, 238, 0.05)); color: var(--primary); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid rgba(34, 211, 238, 0.1);">
                        <i class="fas fa-user-graduate"></i>
                      </div>
                      <div>
                        <div class="fw-800" style="font-size:20px; color:var(--text-main); line-height:1;"><?= $c['student_count'] ?></div>
                        <div class="text-muted fw-700" style="font-size:9px; text-transform:uppercase; letter-spacing:1px; margin-top: 2px;">Students</div>
                      </div>
                  </div>
                  
                  <a href="<?= BASE_URL ?>/frontend/lecturer/students.php?q=<?= urlencode($c['course_name']) ?>" 
                     class="btn-lms btn-primary btn-sm shadow-premium" 
                     style="border-radius: 12px; font-weight: 700; padding: 10px 20px; font-size: 12px; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; box-shadow: 0 4px 15px rgba(34, 211, 238, 0.3);">
                    Manage <i class="fas fa-arrow-right" style="font-size: 11px;"></i>
                  </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div><!-- /#page-content -->

1'; ?>

