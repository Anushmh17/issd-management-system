<?php
// =====================================================
// ISSD Management - Student: Assignments List
// frontend/student/assignments/index.php
// =====================================================
define('PAGE_TITLE', 'My Assignments');
require_once dirname(__DIR__, 3) . '/backend/config.php';
require_once dirname(__DIR__, 3) . '/backend/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/backend/assignment_controller.php';

requireRole(ROLE_STUDENT);

$user = currentUser();
$assignments = getStudentAssignments($pdo, $user['id']);

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Assignments</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Student &rsaquo; <span>Assignments</span>
      </div>
    </div>
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
          <i class="fas fa-box-open"></i>
          <p>No assignments found. You're fully caught up!</p>
        </div>
      <?php else: ?>
        <table class="table-lms">
          <thead>
            <tr>
              <th>Topic</th>
              <th>Course</th>
              <th>Deadline</th>
              <th>Status</th>
              <th>Marks</th>
              <th style="text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $a): 
              $now = time();
              $dlTime = strtotime($a['deadline']);
              $isOverdue = $dlTime < $now;
              $status = 'Not submitted';
              $badge = 'background:#f1f5f9;color:#64748b;';
              
              if ($a['submission_id']) {
                  if (strtotime($a['submitted_at']) > $dlTime) {
                      $status = 'Late';
                      $badge = 'background:#fee2e2;color:#dc2626;';
                  } else {
                      $status = 'Submitted';
                      $badge = 'background:#d1fae5;color:#059669;';
                  }
              } elseif ($isOverdue) {
                  $status = 'Missing';
                  $badge = 'background:#fee2e2;color:#dc2626;';
              }
            ?>
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
                  <?= date('d M Y, h:i A', $dlTime) ?>
                </div>
              </td>
              <td>
                <span class="badge-lms" style="<?= $badge ?>"><?= $status ?></span>
              </td>
              <td>
                <?php if ($a['marks'] !== null): ?>
                  <div class="fw-700" style="color:#059669;font-size:14px;"><?= rtrim(rtrim(number_format($a['marks'],2), '0'), '.') ?></div>
                <?php else: ?>
                  <span style="font-size:12px;color:#94a3b8;">""</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <a href="submit.php?id=<?= $a['id'] ?>" class="btn-lms btn-primary btn-sm">
                  <i class="fas fa-eye"></i> View & Submit
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

