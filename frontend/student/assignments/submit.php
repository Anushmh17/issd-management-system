<?php
// =====================================================
// ISSD Management - Student: Submit Assignment
// frontend/student/assignments/submit.php
// =====================================================
define('PAGE_TITLE', 'Submit Assignment');
require_once dirname(__DIR__, 3) . '/backend/config.php';
require_once dirname(__DIR__, 3) . '/backend/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/backend/assignment_controller.php';

requireRole(ROLE_STUDENT);

$user = currentUser();
$id = (int)($_GET['id'] ?? 0);

$assignment = getAssignmentForStudent($pdo, $id, $user['id']);
if (!$assignment) {
    setFlash('danger', 'Assignment not found or you are not enrolled.');
    header('Location: index.php'); exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = submitAssignment($pdo, $user['id'], $id, $_FILES['submission_file'] ?? null);
    if ($result['success']) {
        setFlash('success', 'Assignment submitted successfully.');
        header("Location: submit.php?id=$id"); exit;
    }
    $errors = $result['errors'];
}

$dlTime = strtotime($assignment['deadline']);
$isOverdue = time() > $dlTime;
$hasSubmitted = !empty($assignment['submission_id']);

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Assignment Details</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Student &rsaquo; 
        <a href="index.php" style="color:inherit;">Assignments</a> &rsaquo; 
        <span>Submit</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div>
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Left: Details -->
    <div class="col-lg-7">
      <div class="card-lms mb-20">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-file-invoice" style="color:#5b4efa;"></i> <?= htmlspecialchars($assignment['title']) ?>
          </div>
        </div>
        <div class="card-lms-body">
          <table class="table-lms" style="background:#f8fafc;border-radius:8px;">
            <tr>
              <td style="width:120px;color:#64748b;font-size:12px;border-bottom:none;">Course</td>
              <td style="border-bottom:none;font-weight:600;font-size:13px;"><?= htmlspecialchars($assignment['course_name']) ?></td>
            </tr>
            <tr>
              <td style="color:#64748b;font-size:12px;border-bottom:none;">Deadline</td>
              <td style="border-bottom:none;font-size:13px;color:#dc2626;font-weight:700;">
                <i class="fas fa-clock"></i> <?= date('d M Y, h:i A', $dlTime) ?>
              </td>
            </tr>
            <?php if ($assignment['file']): ?>
            <tr>
              <td style="color:#64748b;font-size:12px;border-bottom:none;">Attachment</td>
              <td style="border-bottom:none;">
                <a href="<?= ASSIGNMENT_URL . $assignment['file'] ?>" target="_blank" class="btn-lms btn-primary btn-sm">
                  <i class="fas fa-download"></i> Download Assignment
                </a>
              </td>
            </tr>
            <?php endif; ?>
          </table>
          
          <div style="margin-top:16px;">
            <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:8px;">Instructions</div>
            <div style="background:#f1f5f9;padding:12px;border-radius:8px;font-size:13px;line-height:1.6;color:#334155;">
              <?= nl2br(htmlspecialchars($assignment['description'] ?: 'No description provided.')) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Submission -->
    <div class="col-lg-5">
      <div class="card-lms mb-20">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-cloud-arrow-up" style="color:#10b981;"></i> Your Submission
          </div>
          <?php if ($hasSubmitted): ?>
            <span class="badge-lms" style="background:#d1fae5;color:#059669;">Submitted</span>
          <?php elseif ($isOverdue): ?>
            <span class="badge-lms" style="background:#fee2e2;color:#dc2626;">Missing</span>
          <?php else: ?>
            <span class="badge-lms" style="background:#f1f5f9;color:#64748b;">Pending</span>
          <?php endif; ?>
        </div>
        <div class="card-lms-body">
          
          <?php if ($hasSubmitted): ?>
            <div class="alert-lms success" style="margin-bottom:16px;">
              <i class="fas fa-check-circle"></i>
              <div>
                <strong>Submitted successfully!</strong><br>
                <div style="font-size:11px;margin-top:4px;">
                  On: <?= date('d M Y, h:i A', strtotime($assignment['submitted_at'])) ?>
                </div>
              </div>
            </div>
            
            <a href="<?= ASSIGNMENT_URL . $assignment['submission_file'] ?>" target="_blank" class="btn-lms btn-outline" style="width:100%;justify-content:center;margin-bottom:16px;">
              <i class="fas fa-file-pdf"></i> View Submitted File
            </a>

            <!-- Marks / Feedback -->
            <?php if ($assignment['marks'] !== null || $assignment['feedback']): ?>
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:16px;border-radius:8px;">
                <div style="font-size:12px;font-weight:700;color:#059669;text-transform:uppercase;margin-bottom:8px;">Lecturer Feedback</div>
                <?php if ($assignment['marks'] !== null): ?>
                <div style="font-size:24px;font-weight:800;color:#059669;margin-bottom:4px;">
                  <?= rtrim(rtrim(number_format($assignment['marks'],2), '0'), '.') ?> Marks
                </div>
                <?php endif; ?>
                <?php if ($assignment['feedback']): ?>
                <div style="font-size:13px;color:#334155;">
                  <?= nl2br(htmlspecialchars($assignment['feedback'])) ?>
                </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($assignment['marks'] === null && !$isOverdue): ?>
              <hr style="margin:20px 0;border-color:#e2e8f0;">
              <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:8px;">Resubmit Work</div>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($assignment['marks'] === null && (!$isOverdue || $hasSubmitted)): ?>
            <form method="POST" enctype="multipart/form-data">
              <div class="form-group-lms">
                <input type="file" name="submission_file" class="form-control-lms" required accept=".pdf,.doc,.docx,.zip,.rar">
                <small style="font-size:11px;color:#94a3b8;display:block;margin-top:6px;">Allowable: PDF, DOCX, ZIP, RAR (Max: 15MB)</small>
              </div>
              <button type="submit" class="btn-primary-grad" style="width:100%;justify-content:center;">
                <i class="fas fa-paper-plane"></i> <?= $hasSubmitted ? 'Upload New Revision' : 'Submit Assignment' ?>
              </button>
            </form>
          <?php elseif (!$hasSubmitted && $isOverdue): ?>
            <div class="alert-lms danger">
              <i class="fas fa-lock"></i>
              <div>The deadline has passed. Submissions are no longer accepted online.</div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>

