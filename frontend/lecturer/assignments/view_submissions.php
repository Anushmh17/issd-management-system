<?php
// =====================================================
// ISSD Management - Lecturer: View Submissions
// frontend/lecturer/assignments/view_submissions.php
// =====================================================
define('PAGE_TITLE', 'Submissions');
require_once dirname(__DIR__, 3) . '/backend/config.php';
require_once dirname(__DIR__, 3) . '/backend/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/backend/assignment_controller.php';

requireRole(ROLE_LECTURER);

$user = currentUser();
$id = (int)($_GET['id'] ?? 0);

$assignment = getAssignmentByIdAndLecturer($pdo, $id, $user['id']);
if (!$assignment) {
    setFlash('danger', 'Assignment not found or unauthorized.');
    header('Location: index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $subId = (int)$_POST['submission_id'];
    $r = gradeSubmission($pdo, $subId, $_POST);
    if ($r['success']) setFlash('success', 'Graded successfully.');
    else setFlash('danger', 'Failed to save grade.');
    header("Location: view_submissions.php?id=$id"); exit;
}

$submissions = getAssignmentSubmissions($pdo, $id);

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Submissions: <?= htmlspecialchars($assignment['title']) ?></h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Lecturer &rsaquo; 
        <a href="index.php" style="color:inherit;">Assignments</a> &rsaquo; 
        <span>Reviews</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title">
        <i class="fas fa-users-viewfinder" style="color:#059669;"></i> Student Submissions
      </div>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($submissions)): ?>
        <div class="empty-state">
          <i class="fas fa-box-open"></i>
          <p>No submissions received yet.</p>
        </div>
      <?php else: ?>
        <table class="table-lms">
          <thead>
            <tr>
              <th>Student</th>
              <th>Submitted At</th>
              <th>File</th>
              <th>Status</th>
              <th>Grade & Feedback</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $s): 
              $subTime = strtotime($s['submitted_at']);
              $dlTime = strtotime($assignment['deadline']);
              $isLate = $subTime > $dlTime;
            ?>
            <tr>
              <td>
                <div class="fw-600"><?= htmlspecialchars($s['full_name']) ?></div>
                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($s['student_reg']) ?></div>
              </td>
              <td style="font-size:12px;">
                <?= date('d M Y, h:i A', $subTime) ?>
              </td>
              <td>
                <a href="<?= ASSIGNMENT_URL . $s['submission_file'] ?>" target="_blank" class="btn-lms btn-outline btn-sm">
                  <i class="fas fa-download"></i> View
                </a>
              </td>
              <td>
                <?php if ($isLate): ?>
                  <span class="badge-lms" style="background:#fee2e2;color:#dc2626;">Late</span>
                <?php else: ?>
                  <span class="badge-lms" style="background:#d1fae5;color:#059669;">On Time</span>
                <?php endif; ?>
              </td>
              <td style="min-width:300px;">
                <form method="POST" class="d-flex gap-10" style="align-items:flex-start;">
                  <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                  <div style="flex:1;">
                    <input type="number" step="0.01" name="marks" class="form-control-lms mb-10" 
                           placeholder="Marks" value="<?= htmlspecialchars($s['marks'] ?? '') ?>" style="padding:4px 8px;height:30px;font-size:12px;">
                    <input type="text" name="feedback" class="form-control-lms" 
                           placeholder="Feedback/Comments" value="<?= htmlspecialchars($s['feedback'] ?? '') ?>" style="padding:4px 8px;height:30px;font-size:12px;">
                  </div>
                  <button type="submit" class="btn-lms btn-primary btn-sm" style="height:68px;">Save</button>
                </form>
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

