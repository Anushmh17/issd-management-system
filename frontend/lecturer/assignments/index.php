<?php
// =====================================================
// ISSD Management - Lecturer: Assignments List
// frontend/lecturer/assignments/index.php
// =====================================================
define('PAGE_TITLE', 'Assignments');
require_once dirname(__DIR__, 3) . '/backend/config.php';
require_once dirname(__DIR__, 3) . '/backend/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/backend/assignment_controller.php';

requireRole(ROLE_LECTURER);

$user = currentUser();
$assignments = getLecturerAssignments($pdo, $user['id']);

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Assignments</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Lecturer &rsaquo; <span>Assignments</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad">
      <i class="fas fa-plus"></i> Add Assignment
    </a>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title">
        <i class="fas fa-file-alt" style="color:#5b4efa;"></i> Assigned Work
        <span class="badge-lms info" style="margin-left:6px;"><?= count($assignments) ?></span>
      </div>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($assignments)): ?>
        <div class="empty-state">
          <i class="fas fa-folder-open"></i>
          <p>No assignments created yet.</p>
          <a href="add.php" class="btn-lms btn-primary mt-10">Create First Assignment</a>
        </div>
      <?php else: ?>
        <table class="table-lms">
          <thead>
            <tr>
              <th>Title</th>
              <th>Course</th>
              <th>Deadline</th>
              <th>Submissions</th>
              <th>File</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
            <tr>
              <td>
                <div class="fw-600"><?= htmlspecialchars($a['title']) ?></div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                  <?= htmlspecialchars($a['description']) ?>
                </div>
              </td>
              <td>
                <div class="fw-600" style="font-size:12px;"><?= htmlspecialchars($a['course_code']) ?></div>
                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($a['course_name']) ?></div>
              </td>
              <td>
                <div style="font-size:13px;color:#dc2626;font-weight:600;">
                  <i class="fas fa-clock"></i> <?= date('d M Y, h:i A', strtotime($a['deadline'])) ?>
                </div>
              </td>
              <td>
                <span class="badge-lms" style="background:#fef3c7;color:#d97706;">
                  <?= $a['submission_count'] ?> submitted
                </span>
              </td>
              <td>
                <?php if ($a['file']): ?>
                  <a href="<?= ASSIGNMENT_URL . $a['file'] ?>" target="_blank" class="btn-lms btn-outline btn-sm">
                    <i class="fas fa-download"></i> Download
                  </a>
                <?php else: ?>
                  <span style="font-size:12px;color:#94a3b8;">No file</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <a href="view_submissions.php?id=<?= $a['id'] ?>" class="btn-lms btn-primary btn-sm">
                  <i class="fas fa-users-viewfinder"></i> Reviews
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>

