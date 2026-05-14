<?php
// =====================================================
// ISSD Management - Lecturer: View Submissions
// =====================================================
define('PAGE_TITLE', 'Submissions');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);
$lecturerId = $_SESSION['lecturer_id'] ?? currentUserId();
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
$stmt->execute([$assignment_id, $lecturerId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    die("Assignment not found or access denied.");
}

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $submission_id = (int)($_POST['submission_id'] ?? 0);
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

<<style>
  .assignment-hero {
    background: #fff;
    border-radius: 24px;
    padding: 25px 30px;
    border: 1.5px solid rgba(30, 77, 77, 0.08);
    box-shadow: 0 10px 40px rgba(30, 77, 77, 0.03);
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
  }
  .assignment-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: radial-gradient(circle at top right, rgba(52, 211, 153, 0.05), transparent);
    pointer-events: none;
  }
  .hero-tag {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--primary);
    opacity: 0.5;
    margin-bottom: 8px;
  }
  .hero-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 24px;
    color: var(--primary);
    line-height: 1.2;
    margin-bottom: 18px;
    letter-spacing: -0.3px;
  }
  .hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    padding-top: 18px;
    border-top: 1px solid rgba(0,0,0,0.04);
  }
  .meta-pill {
    padding: 8px 16px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1.2px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    transition: all 0.3s;
  }
  .meta-pill i { font-size: 14px; color: var(--primary); opacity: 0.6; }
  .meta-pill:hover { transform: translateY(-2px); border-color: var(--primary); background: #fff; }
  
  .meta-pill.deadline { background: #fff1f2; border-color: #fecdd3; color: #e11d48; }
  .meta-pill.deadline i { color: #e11d48; }

  .stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  /* Student Avatar */
  .stu-avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }

  .grade-input-group {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px 18px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .grade-input-group:focus-within {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 10px 30px rgba(30, 77, 77, 0.08);
  }
</style>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Submissions Review</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Lecturer &rsaquo; 
        <a href="assignments/index.php">Assignments</a> &rsaquo; 
        <span>Grade Submissions</span>
      </div>
    </div>
    <a href="assignments/index.php" class="btn-lms btn-outline">
      <i class="fas fa-arrow-left me-2"></i> Back to List
    </a>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Quick Stats Logic -->
  <?php
    $totalEnrolled = count($students);
    $submittedCount = count(array_filter($students, fn($s) => !empty($s['submission_id'])));
    $gradedSubmissions = array_filter($students, fn($s) => !empty($s['submission_id']) && $s['marks'] !== null);
    $gradedCount = count($gradedSubmissions);
    $pendingCount = $submittedCount - $gradedCount;
    
    // Calculate Average Score
    $avgScore = 0;
    if ($gradedCount > 0) {
        $totalMarks = array_sum(array_column($gradedSubmissions, 'marks'));
        $avgScore = round($totalMarks / $gradedCount, 1);
    }
  ?>

  <!-- Assignment Hero Card -->
  <div class="assignment-hero">
    <div class="hero-tag">Active Assignment</div>
    <div class="hero-title"><?= htmlspecialchars($assignment['title']) ?></div>
    <div class="hero-meta">
      <div class="meta-pill">
        <i class="fas fa-book"></i>
        <?= htmlspecialchars($assignment['course_code']) ?> - <?= htmlspecialchars($assignment['course_name']) ?>
      </div>
      <div class="meta-pill deadline">
        <i class="fas fa-clock"></i>
        Due: <?= $assignment['due_date'] ? date('M d, Y | h:i A', strtotime($assignment['due_date'])) : 'N/A' ?>
      </div>
      <div class="meta-pill" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
        <i class="fas fa-chart-line" style="color:#16a34a;"></i>
        Avg Score: <?= $avgScore ?> / <?= (int)$assignment['max_marks'] ?>
      </div>
    </div>
  </div>
  <div class="stat-grid">
    <div class="stat-card" style="--sc-color:#5b4efa;">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $totalEnrolled ?></div>
        <div class="stat-label">Total Students</div>
      </div>
    </div>
    <div class="stat-card" style="--sc-color:#06b6d4;">
      <div class="stat-icon"><i class="fas fa-file-import"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $submittedCount ?></div>
        <div class="stat-label">Submissions Received</div>
      </div>
    </div>
    <div class="stat-card" style="--sc-color:#10b981;">
      <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $gradedCount ?></div>
        <div class="stat-label">Graded</div>
      </div>
    </div>
    <div class="stat-card" style="--sc-color:#f59e0b;">
      <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header d-flex justify-content-between align-items-center">
      <div class="list-legend">
        <div class="list-legend-label">Review Panel</div>
        <div class="list-legend-title">Submission Tracking</div>
      </div>
      <div class="count-badge"><?= $totalEnrolled ?> Enrolled</div>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <table class="table-lms">
        <thead>
          <tr>
            <th style="padding-left:30px;">Student</th>
            <th>Submission Status</th>
            <th>File / Remarks</th>
            <th style="width:300px; padding-right:30px;">Grading</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): 
                $hasSubmitted = !empty($s['submission_id']);
                $isGraded = $hasSubmitted && $s['marks'] !== null;
                $initials = strtoupper(substr($s['student_name'], 0, 1));
                $avatarColor = studentAvatarColor($s['student_name']);
          ?>
          <tr>
            <td style="padding-left:30px;">
              <div class="d-flex align-items-center gap-12">
                <div class="stu-avatar" style="background:<?= $avatarColor ?>"><?= $initials ?></div>
                <div>
                  <div class="fw-700 text-main" style="font-size:14px;"><?= htmlspecialchars($s['student_name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['s_id'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if($hasSubmitted): ?>
                  <span class="badge-lms success">
                    <i class="fas fa-check-circle me-1"></i> Submitted
                  </span>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
                    <i class="fas fa-clock me-1"></i> <?= date('M d, Y h:i A', strtotime($s['submitted_at'])) ?>
                  </div>
              <?php else: ?>
                  <span class="badge-lms danger">
                    <i class="fas fa-times-circle me-1"></i> Not Submitted
                  </span>
              <?php endif; ?>
            </td>
            <td>
                <?php if($hasSubmitted): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php if($s['file_path']): ?>
                            <a href="<?= BASE_URL ?>/assets/uploads/submissions/<?= htmlspecialchars($s['file_path']) ?>" 
                               target="_blank" class="btn-lms btn-sm btn-outline text-primary" style="width:fit-content;">
                               <i class="fas fa-file-pdf me-2"></i> Download File
                            </a>
                        <?php endif; ?>
                        <?php if($s['remarks']): ?>
                            <div style="font-size:12px; color:#64748b; line-height:1.4; background:#f1f5f9; padding:8px 12px; border-radius:8px; border-left:3px solid var(--primary);">
                              <?= nl2br(htmlspecialchars($s['remarks'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td style="padding-right:30px;">
                <?php if($hasSubmitted): ?>
                    <form method="POST" class="grade-input-group">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="submission_id" value="<?= $s['submission_id'] ?>">
                        <div style="flex:1;">
                          <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8; margin-bottom:4px;">Marks</div>
                          <input type="number" name="marks" class="form-control-lms" 
                                 style="height:38px; font-weight:700; font-size:14px;" 
                                 min="0" max="<?= $assignment['max_marks'] ?>" 
                                 value="<?= $s['marks'] ?? '' ?>" required>
                        </div>
                        <button type="submit" class="btn-lms btn-primary" style="height:38px; margin-top:14px;">
                          <i class="fas fa-check"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
function studentAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}

require_once dirname(__DIR__, 2) . '/includes/footer.php'; 
?>