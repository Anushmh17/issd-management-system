<?php
// =====================================================
// ISSD Management - Student Profile
// frontend/student/profile.php
// =====================================================
define('PAGE_TITLE', 'My Profile');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);

$user = currentUser();
$userId = (int)$user['id'];

// Get more details
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.avatar
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ?
");
$stmt->execute([$userId]);
$student = $stmt->fetch();
$studentId = (int)$student['id'];

// Fetch enrollments for progress
$stmt = $pdo->prepare("
    SELECT c.course_name, e.status, e.enrolled_at
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
");
$stmt->execute([$studentId]);
$enrollments = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="dark-layout-wrapper">
    
    <div class="welcome-header">
      <h1>Student Profile</h1>
      <p>View your personal records and academic enrollment status.</p>
    </div>

    <div class="dark-grid-2">
      
      <!-- Left Column: Basic Info -->
      <div style="display:flex; flex-direction:column; gap:24px;">
        <div class="glass-card" style="text-align:center; padding:50px 24px;">
          <div style="position:relative; display:inline-block; margin-bottom:24px;">
            <?php if ($student['avatar']): ?>
              <div style="width:140px; height:140px; border-radius:50%; padding:4px; background:linear-gradient(135deg, #22d3ee, #c084fc); margin:0 auto;">
                <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($student['avatar']) ?>" 
                     style="width:100%; height:100%; border-radius:50%; object-fit:cover; border:4px solid transparent; background-clip:padding-box; background:rgba(255,255,255,0.1);">
              </div>
            <?php else: ?>
              <div style="width:130px; height:130px; border-radius:50%; background:linear-gradient(135deg, #22d3ee, #c084fc); color:#fff; display:flex; align-items:center; justify-content:center; font-size:48px; font-weight:800; margin:0 auto; box-shadow:0 15px 35px rgba(34,211,238,0.2);">
                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
          </div>
          
          <h2 style="font-size:24px; font-weight:800; margin-bottom:6px; color:inherit;"><?= htmlspecialchars($student['full_name']) ?></h2>
          <div style="font-size:14px; opacity:0.6; margin-bottom:24px; font-weight:600;">System ID: <span style="color:#22d3ee;"><?= htmlspecialchars($student['student_id']) ?></span></div>
          
          <div style="display:flex; flex-direction:column; gap:10px; align-items:center;">
            <div style="font-size:12px; font-weight:700; color:#c084fc; text-transform:uppercase; letter-spacing:1px; background:rgba(192, 132, 252, 0.1); padding:6px 20px; border-radius:100px;">
              <?= htmlspecialchars($student['batch_number'] ?? 'No Batch') ?> Intake
            </div>
            <div style="font-size:11px; opacity:0.5; font-weight:600;">Joined On: <?= date('M d, Y', strtotime($student['join_date'])) ?></div>
          </div>

          <div style="margin-top:40px; padding-top:30px; border-top:1px solid rgba(255,255,255,0.05);">
            <a href="settings.php" class="btn-primary-grad" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; border-radius:10px; text-decoration:none; font-size:13px; font-weight:700;">
              <i class="fas fa-user-edit"></i> Edit Account
            </a>
          </div>
        </div>
      </div>

      <!-- Right Column: Details -->
      <div style="display:flex; flex-direction:column; gap:24px;">
        
        <div class="glass-card">
          <div class="glass-card-title">
            <span><i class="fas fa-info-circle" style="color:#22d3ee;"></i> Personal & Contact Information</span>
          </div>
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
            <div>
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">Personal Email</div>
              <div style="font-size:15px; font-weight:700;"><?= htmlspecialchars($student['personal_email'] ?? $student['email']) ?></div>
            </div>
            <div>
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">Phone Number</div>
              <div style="font-size:15px; font-weight:700;"><?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></div>
            </div>
            <div>
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">National ID / NIC</div>
              <div style="font-size:15px; font-weight:700;"><?= htmlspecialchars($student['nic_number'] ?? 'N/A') ?></div>
            </div>
            <div>
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">Office Email</div>
              <div style="font-size:14px; font-weight:700; color:#22d3ee;"><?= htmlspecialchars($student['office_email'] ?? 'Not Assigned') ?></div>
            </div>
          </div>
          
          <div style="margin-top:30px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.05); display:grid; grid-template-columns:1fr 1fr; gap:30px;">
            <div>
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">Guardian Name</div>
              <div style="font-size:15px; font-weight:700;"><?= htmlspecialchars($student['guardian_name'] ?? 'N/A') ?></div>
            </div>
            <div>
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">Guardian Phone</div>
              <div style="font-size:15px; font-weight:700;"><?= htmlspecialchars($student['guardian_phone'] ?? 'N/A') ?></div>
            </div>
          </div>

          <div style="margin-top:30px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.05);">
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px;">Mailing Address</div>
            <div style="font-size:14px; font-weight:600; line-height:1.6;"><?= htmlspecialchars($student['house_address'] ?? 'No address on file.') ?></div>
          </div>
        </div>

        <div class="glass-card">
          <div class="glass-card-title">
            <span><i class="fas fa-graduation-cap" style="color:#c084fc;"></i> Academic Summary</span>
          </div>
          
          <?php if (empty($enrollments)): ?>
            <div class="empty-box-lms">
              <i class="fas fa-university" style="font-size:24px; color:#475569; margin-bottom:12px;"></i>
              <div class="title" style="font-size:14px; font-weight:600;">No enrollments found.</div>
              <div style="font-size:12px; opacity:0.6; margin-top:4px;">You are not currently enrolled in any courses.</div>
            </div>
          <?php else: ?>
            <ul class="dark-list">
              <?php foreach ($enrollments as $e): ?>
              <li class="dark-list-item">
                <div class="item-left">
                  <div class="item-icon" style="background:rgba(192, 132, 252, 0.1); color:#c084fc;"><i class="fas fa-bookmark"></i></div>
                  <div>
                    <div class="item-title"><?= htmlspecialchars($e['course_name']) ?></div>
                    <div class="item-sub" style="font-size:11px;">Enrolled on <?= date('M d, Y', strtotime($e['enrolled_at'])) ?></div>
                  </div>
                </div>
                <div class="item-right">
                  <span class="dark-badge <?= $e['status']==='active'?'db-green':'db-blue' ?>" style="font-size:9px;">
                    <?= strtoupper($e['status']) ?>
                  </span>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

      </div>

    </div>
  </div>
</div>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

