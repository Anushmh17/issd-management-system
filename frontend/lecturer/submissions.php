<?php
// =====================================================
// ISSD Management - Lecturer: View Submissions
// =====================================================
define('PAGE_TITLE', 'Submissions');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);
$userId = currentUserId();
$error = '';

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if (!$assignment_id) {
    die("Invalid Assignment ID.");
}

// Verify Assignment belongs to lecturer
$stmt = $pdo->prepare("SELECT a.*, c.course_name AS course_name, c.course_code AS course_code 
                       FROM assignments a 
                       JOIN courses c ON c.id=a.course_id 
                       WHERE a.id = ? AND a.lecturer_id = ?");
$stmt->execute([$assignment_id, $userId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    die("Assignment not found or access denied.");
}

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'grade') {
    $submission_id = (int)$_POST['submission_id'];
    $marks = (int)$_POST['marks'];
    
    if ($marks < 0 || $marks > $assignment['max_marks']) {
        $error = "Marks must be between 0 and " . $assignment['max_marks'];
    } else {
        try {
            $pdo->prepare("UPDATE submissions SET marks = ? WHERE id = ? AND assignment_id = ?")
                ->execute([$marks, $submission_id, $assignment_id]);
            setFlash('success', 'Submission graded successfully.');
            header("Location: submissions.php?assignment_id=$assignment_id"); exit;
        } catch(PDOException $e) {
            $error = "Failed to grade submission.";
        }
    }
}

// Fetch submissions (including students who haven't submitted)
$sql = "SELECT u.id AS student_id, u.name AS student_name, sp.student_id AS s_id,
               s.id AS submission_id, s.file_path, s.remarks, s.marks, s.submitted_at
        FROM enrollments e
        JOIN users u ON u.id = e.student_id
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        LEFT JOIN submissions s ON s.student_id = u.id AND s.assignment_id = ?
        WHERE e.course_id = ?
        ORDER BY u.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$assignment_id, $assignment['course_id']]);
$students = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>View Submissions</h1>
      <div class="breadcrumb-custom">
          <i class="fas fa-home"></i> Lecturer &rsaquo; 
          <a href="assignments.php">Assignments</a> &rsaquo; 
          <span>Submissions</span>
      </div>
    </div>
    <a href="assignments.php" class="btn-lms btn-outline"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card-lms mb-4">
      <div class="card-lms-body" style="background:var(--primary-light);border-radius:var(--radius-lg);">
          <div class="d-flex justify-between align-center mb-2">
              <h2 class="fw-700 m-0" style="color:var(--primary);font-size:20px;"><?= htmlspecialchars($assignment['title']) ?></h2>
              <span class="badge-lms primary"><?= htmlspecialchars($assignment['course_code']) ?> - <?= htmlspecialchars($assignment['course_name']) ?></span>
          </div>
          <div class="d-flex justify-between align-center" style="font-size:13px;">
              <div><strong>Due:</strong> <?= $assignment['due_date'] ? date('M d, Y h:i A', strtotime($assignment['due_date'])) : 'N/A' ?></div>
              <div><strong>Max Marks:</strong> <span class="badge-lms info"><?= $assignment['max_marks'] ?></span></div>
          </div>
      </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-check-square"></i> Student Submissions (<?= count($students) ?>)</div>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <table class="table-lms">
        <thead>
          <tr>
            <th>Student</th>
            <th>Submission Status</th>
            <th>File / Remarks</th>
            <th>Marks</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): 
                $hasSubmitted = !empty($s['submission_id']);
                $isGraded = $hasSubmitted && $s['marks'] !== null;
          ?>
          <tr>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="avatar-initials" style="width:30px;height:30px;font-size:12px;"><?= strtoupper(substr($s['student_name'],0,1)) ?></div>
                <div>
                  <div class="fw-600"><?= htmlspecialchars($s['student_name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['s_id'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if($hasSubmitted): ?>
                  <span class="badge-lms success">Submitted</span>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= date('M d, y h:iA', strtotime($s['submitted_at'])) ?></div>
              <?php else: ?>
                  <span class="badge-lms danger">Not Submitted</span>
              <?php endif; ?>
            </td>
            <td>
                <?php if($hasSubmitted): ?>
                    <?php if($s['file_path']): ?>
                        <a href="<?= BASE_URL ?>/assets/uploads/submissions/<?= htmlspecialchars($s['file_path']) ?>" target="_blank" class="btn-lms btn-outline btn-sm mb-1" title="Download File"><i class="fas fa-download"></i> Download File</a>
                    <?php endif; ?>
                    <?php if($s['remarks']): ?>
                        <div style="font-size:12px;background:var(--bg-page);padding:6px;border-radius:4px;border:1px solid var(--border-color);max-height:80px;overflow-y:auto;"><?= nl2br(htmlspecialchars($s['remarks'])) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">""</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if($isGraded): ?>
                    <span class="fw-700" style="color:var(--accent);font-size:15px;"><?= $s['marks'] ?></span> <span class="text-muted">/ <?= $assignment['max_marks'] ?></span>
                <?php elseif($hasSubmitted): ?>
                    <span class="badge-lms warning">Needs Grading</span>
                <?php else: ?>
                    <span class="text-muted">""</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if($hasSubmitted): ?>
                    <button class="btn-lms btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#gradeForm<?= $s['submission_id'] ?>">
                        <i class="fas fa-star"></i> <?= $isGraded ? 'Edit Grade' : 'Grade' ?>
                    </button>
                    <!-- Inline Grading Form -->
                    <div class="collapse mt-2" id="gradeForm<?= $s['submission_id'] ?>">
                        <form method="POST" style="display:flex;gap:5px;align-items:center;">
                            <input type="hidden" name="act" value="grade">
                            <input type="hidden" name="submission_id" value="<?= $s['submission_id'] ?>">
                            <input type="number" name="marks" class="form-control-lms" style="width:70px;padding:5px;" min="0" max="<?= $assignment['max_marks'] ?>" value="<?= $s['marks'] ?? '' ?>" required>
                            <button type="submit" class="btn-lms btn-success btn-sm"><i class="fas fa-check"></i></button>
                        </form>
                    </div>
                <?php else: ?>
                    <span class="text-muted">""</span>
                <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

