<?php
// =====================================================
// ISSD Management - Student: My Courses
// =====================================================
define('PAGE_TITLE', 'My Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);
$userId = currentUserId();

$sql = "SELECT c.*, e.status AS enrollment_status, e.enrolled_at,
               u.name AS lecturer_name, u.email AS lecturer_email
        FROM enrollments e
        JOIN courses c ON c.id = e.course_id
        LEFT JOIN users u ON u.id = e.lecturer_id
        WHERE e.student_id = ?
        ORDER BY e.enrolled_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$courses = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Courses</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Student &rsaquo; <span>Courses</span></div>
    </div>
  </div>

  <div class="row g-4">
    <?php if(empty($courses)): ?>
        <div class="col-12"><div class="card-lms"><div class="empty-state"><i class="fas fa-book"></i><p>You have not enrolled in any courses yet.</p></div></div></div>
    <?php else: ?>
        <?php foreach($courses as $c): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-lms h-100" style="border-top:4px solid var(--primary);transition:all 0.3s ease;">
                <div class="card-lms-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <span class="badge-lms primary"><?= htmlspecialchars($c['code']) ?></span>
                        <span class="badge-lms <?= $c['enrollment_status']==='active'?'success':($c['enrollment_status']==='completed'?'info':'danger') ?>">
                            <?= ucfirst($c['enrollment_status']) ?>
                        </span>
                    </div>
                    <h3 style="font-size:18px;font-weight:700;color:var(--text-main);margin-bottom:8px;"><?= htmlspecialchars($c['title']) ?></h3>
                    <div class="text-muted" style="font-size:13px;line-height:1.5;min-height:40px;margin-bottom:16px;">
                        <?= htmlspecialchars($c['description'] ?? 'No description.') ?>
                    </div>
                    
                    <div style="background:var(--bg-page);padding:12px;border-radius:var(--radius-sm);margin-bottom:16px;">
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">Assigned Lecturer</div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if($c['lecturer_name']): ?>
                                <div class="avatar-initials" style="width:28px;height:28px;font-size:11px;"><?= strtoupper(substr($c['lecturer_name'],0,1)) ?></div>
                                <div>
                                    <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($c['lecturer_name']) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($c['lecturer_email']) ?></div>
                                </div>
                            <?php else: ?>
                                <div class="text-muted fw-500">To be announced</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;border-top:1px solid var(--border-color);padding-top:16px;">
                        <div>
                            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;">Duration</div>
                            <div class="fw-600"><?= htmlspecialchars($c['duration']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;">Enrolled On</div>
                            <div class="fw-600"><?= date('M d, Y', strtotime($c['enrolled_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

