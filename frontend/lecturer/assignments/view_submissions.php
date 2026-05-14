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
    verifyCsrf();
    $subId = (int)$_POST['submission_id'];
    $r = gradeSubmission($pdo, $subId, $user['id'], $_POST);
    if ($r['success']) setFlash('success', 'Graded successfully.');
    else setFlash('danger', $r['error'] ?? 'Failed to save grade.');
    header("Location: view_submissions.php?id=$id"); exit;
}


$submissions = getAssignmentSubmissions($pdo, $id);

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';
?>

<style>
  .assignment-hero {
    background: #fff;
    border-radius: 32px;
    padding: 45px 50px;
    border: 1.5px solid rgba(30, 77, 77, 0.08);
    box-shadow: 0 20px 60px rgba(30, 77, 77, 0.05);
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
  }
  .assignment-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 500px;
    height: 100%;
    background: radial-gradient(circle at top right, rgba(52, 211, 153, 0.1), transparent);
    pointer-events: none;
  }
  .hero-tag {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--primary);
    opacity: 0.4;
    margin-bottom: 15px;
  }
  .hero-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 36px;
    color: var(--primary);
    line-height: 1.2;
    margin-bottom: 35px;
    letter-spacing: -0.5px;
  }
  .hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    padding-top: 30px;
    border-top: 1px solid rgba(0,0,0,0.05);
  }
  .meta-pill {
    padding: 12px 24px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
    font-weight: 600;
    color: #334155;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }
  .meta-pill i { font-size: 18px; color: var(--primary); opacity: 0.6; }
  .meta-pill:hover { transform: translateY(-3px) scale(1.02); border-color: var(--primary); background: #fff; box-shadow: 0 10px 25px rgba(30, 77, 77, 0.08); }
  
  .meta-pill.deadline { background: #fff1f2; border-color: #fecdd3; color: #e11d48; }
  .meta-pill.deadline i { color: #e11d48; }

  .stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
  }

  /* Student Avatar */
  .stu-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
  }

  .grade-input-group {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 15px;
    transition: all 0.2s;
  }
  .grade-input-group:focus-within {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(30, 77, 77, 0.08);
  }
</style>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Submissions Review</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Lecturer &rsaquo; 
        <a href="index.php">Assignments</a> &rsaquo; 
        <span>Grade Submissions</span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline">
      <i class="fas fa-arrow-left me-2"></i> Back to List
    </a>
  </div>

  <!-- Assignment Hero Card -->
  <div class="assignment-hero">
    <div class="hero-tag">Active Assignment</div>
    <div class="hero-title"><?= htmlspecialchars($assignment['title']) ?></div>
    <div class="hero-meta">
      <div class="meta-pill">
        <i class="fas fa-book"></i>
        <?= htmlspecialchars($assignment['course_name']) ?>
      </div>
      <div class="meta-pill deadline">
        <i class="fas fa-clock"></i>
        Due: <?= date('M d, Y | h:i A', strtotime($assignment['due_date'])) ?>
      </div>
      <div class="meta-pill">
        <i class="fas fa-star"></i>
        Max Marks: <?= (int)$assignment['max_marks'] ?>
      </div>
    </div>
  </div>

  <!-- Quick Stats -->
  <?php
    $totalCount = count($submissions);
    $gradedCount = count(array_filter($submissions, fn($s) => $s['marks'] !== null));
    $pendingCount = $totalCount - $gradedCount;
    $lateCount = 0;
    $dlTime = strtotime($assignment['due_date']);
    foreach($submissions as $s) if(strtotime($s['submitted_at']) > $dlTime) $lateCount++;
  ?>
  <div class="stat-grid">
    <div class="stat-card" style="--sc-color:#5b4efa;">
      <div class="stat-icon"><i class="fas fa-file-import"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $totalCount ?></div>
        <div class="stat-label">Total Received</div>
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
    <div class="stat-card" style="--sc-color:#ef4444;">
      <div class="stat-icon"><i class="fas fa-clock"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $lateCount ?></div>
        <div class="stat-label">Late Submissions</div>
      </div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header d-flex justify-content-between align-items-center">
      <div class="list-legend">
        <div class="list-legend-label">Review Panel</div>
        <div class="list-legend-title">Student Submissions</div>
      </div>
      <div class="count-badge"><?= $totalCount ?> Students</div>
    </div>
    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($submissions)): ?>
        <div class="empty-state py-5">
          <div class="mb-4" style="font-size:48px; color:var(--primary); opacity:0.15;">
            <i class="fas fa-box-open"></i>
          </div>
          <h3 class="fw-700 text-main mb-2">No Submissions Yet</h3>
          <p class="text-muted">Once students upload their files, they will appear here for grading.</p>
        </div>
      <?php else: ?>
        <table class="table-lms">
          <thead>
            <tr>
              <th style="padding-left:30px;">Student</th>
              <th>Submission Details</th>
              <th>Status</th>
              <th style="width:380px; padding-right:30px;">Grading & Feedback</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $s): 
              $subTime = strtotime($s['submitted_at']);
              $isLate = $subTime > $dlTime;
              $initials = strtoupper(substr($s['full_name'], 0, 1));
              $avatarColor = studentAvatarColor($s['full_name']);
            ?>
            <tr>
              <td style="padding-left:30px;">
                <div class="d-flex align-items-center gap-12">
                  <div class="stu-avatar" style="background:<?= $avatarColor ?>"><?= $initials ?></div>
                  <div>
                    <div class="fw-700 text-main" style="font-size:14px;"><?= htmlspecialchars($s['full_name']) ?></div>
                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['student_reg']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column gap-2">
                  <div style="font-size:12px; font-weight:600; color: #475569;">
                    <i class="fas fa-calendar-day me-1 opacity-50"></i>
                    <?= date('M d, Y | h:i A', $subTime) ?>
                  </div>
                  <a href="<?= ASSIGNMENT_URL . $s['submission_file'] ?>" target="_blank" 
                     class="btn-lms btn-sm btn-outline text-primary" style="width:fit-content;">
                    <i class="fas fa-file-pdf me-2"></i> View Document
                  </a>
                </div>
              </td>
              <td>
                <?php if ($isLate): ?>
                  <span class="badge-lms danger">
                    <i class="fas fa-circle-exclamation me-1"></i> Late
                  </span>
                <?php else: ?>
                  <span class="badge-lms success">
                    <i class="fas fa-circle-check me-1"></i> On Time
                  </span>
                <?php endif; ?>
              </td>
              <td style="padding-right:30px;">
                <form method="POST" class="grade-input-group">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                  
                  <div class="row g-2 mb-2">
                    <div class="col-4">
                      <label style="font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8; display:block; margin-bottom:4px;">Score</label>
                      <div class="input-group-lms">
                        <input type="number" step="0.01" name="marks" class="form-control-lms" 
                               placeholder="0.00" value="<?= htmlspecialchars($s['marks'] ?? '') ?>" 
                               max="<?= $assignment['max_marks'] ?>" required
                               style="height:38px; font-size:13px; font-weight:700;">
                      </div>
                    </div>
                    <div class="col-8">
                      <label style="font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8; display:block; margin-bottom:4px;">Feedback</label>
                      <input type="text" name="feedback" class="form-control-lms" 
                             placeholder="Good work! Keep it up..." value="<?= htmlspecialchars($s['feedback'] ?? '') ?>" 
                             style="height:38px; font-size:12px;">
                    </div>
                  </div>
                  
                  <button type="submit" class="btn-lms btn-primary w-100 py-2">
                    <i class="fas fa-save me-2"></i> <?= ($s['marks'] !== null) ? 'Update Grade' : 'Post Grade' ?>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div><!-- /#page-content-restored -->

<?php
function studentAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}

require_once dirname(__DIR__, 3) . '/includes/footer.php'; 
?>