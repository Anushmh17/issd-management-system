<?php
// =====================================================
// ISSD Management - Student: Assignments List (Premium UI)
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

<div id="page-content" style="background: transparent; box-shadow: none;">
  <div class="dark-layout-wrapper">
    
    <div class="welcome-header">
      <h1>Assignments</h1>
      <p>Keep track of your coursework and submission deadlines.</p>
    </div>

    <div class="glass-card" style="padding:0; padding-top:30px; overflow-x:auto;">
      <?php if (empty($assignments)): ?>
        <div style="padding:40px; text-align:center;">
          <div style="width:64px; height:64px; border-radius:16px; background:rgba(255,255,255,0.05); color:#94a3b8; margin:0 auto 16px; font-size:24px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-check-double"></i>
          </div>
          <h3 style="font-weight:700; font-size:18px; color:inherit; margin-bottom:8px;">All caught up!</h3>
          <p style="color:#94a3b8; font-size:14px; margin:0;">No pending assignments found.</p>
        </div>
      <?php else: ?>
        <table style="width:100%; border-collapse:collapse; text-align:left;">
          <thead>
            <tr>
              <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Task Details</th>
              <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Course</th>
              <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Deadline</th>
              <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Status</th>
              <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05);">Grade</th>
              <th style="padding: 0 20px 20px 20px; font-size: 13px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.05); text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $a): 
              $now = time();
              $dlTime = strtotime($a['due_date']);
              $isOverdue = $dlTime < $now;
              $status = 'Pending';
              $statusClass = 'db-gray';
              
              if ($a['submission_id']) {
                  if (strtotime($a['submitted_at']) > $dlTime) {
                      $status = 'Late';
                      $statusClass = 'db-purple';
                  } else {
                      $status = 'Submitted';
                      $statusClass = 'db-green';
                  }
              } elseif ($isOverdue) {
                  $status = 'Missing';
                  $statusClass = 'db-red';
              }
            ?>
            <tr>
              <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="display:flex; align-items:center; gap:16px;">
                  <div style="width:40px; height:40px; border-radius:10px; background:rgba(192, 132, 252, 0.1); color:#c084fc; display:flex; align-items:center; justify-content:center; font-size:16px;">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: inherit;"><?= htmlspecialchars($a['title']) ?></h4>
                    <p style="margin: 0; font-size: 12px; color: #94a3b8; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($a['description']) ?></p>
                  </div>
                </div>
              </td>
              <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span class="dark-badge db-blue"><?= htmlspecialchars($a['course_code']) ?></span>
              </td>
              <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="font-weight:600; font-size:13px; color:inherit;"><?= date('M d, Y', $dlTime) ?></div>
                <div style="font-size:11px; color:#94a3b8;"><?= date('h:i A', $dlTime) ?></div>
              </td>
              <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span class="dark-badge <?= $statusClass ?>">
                  <?= $status ?>
                </span>
              </td>
              <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <?php if ($a['marks'] !== null): ?>
                  <span style="font-weight:800; color:#4ade80; font-size:15px;"><?= rtrim(rtrim(number_format($a['marks'],2), '0'), '.') ?></span>
                <?php else: ?>
                  <span style="color:#d4d4d8;">--</span>
                <?php endif; ?>
              </td>
              <td style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align:right;">
                <a href="submit.php?id=<?= $a['id'] ?>" style="display:inline-block; padding:8px 16px; background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:inherit; font-size:13px; font-weight:600; text-decoration:none; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='#38bdf8'; this.style.color='#38bdf8';" onmouseout="this.style.background='transparent'; this.style.borderColor='rgba(255,255,255,0.1)'; this.style.color='inherit';">View Details</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div><!-- /#page-content -->

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
