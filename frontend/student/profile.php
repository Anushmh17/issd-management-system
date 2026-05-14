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

<style>
  .profile-premium-card {
    background: var(--bg-card);
    backdrop-filter: blur(15px);
    border: 1px solid var(--border-color);
    border-radius: 28px;
    overflow: hidden;
    transition: all 0.3s ease;
  }
  .profile-avatar-wrap {
    margin-top: -50px;
    position: relative;
    z-index: 5;
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
  }
  .profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    border: 5px solid var(--bg-card);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    font-weight: 800;
    color: #fff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  }
  .profile-info {
    padding: 0 30px 30px 30px;
  }
  .profile-role-badge {
    display: inline-block;
    padding: 4px 14px;
    background: rgba(52, 211, 153, 0.1);
    color: #34d399;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 10px;
  }
</style>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Student Profile</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Student &rsaquo; <span>Profile</span></div>
    </div>
  </div>

  <div class="card-lms overflow-hidden shadow-premium" style="max-width: 950px; margin: 0 auto; background: var(--bg-card); border: 1px solid var(--border-color); backdrop-filter: blur(20px);">
    <div class="row g-0">
      <!-- Profile Info Column -->
      <div class="col-lg-4" style="background: rgba(var(--primary-rgb), 0.02); border-right: 1px solid var(--border-color);">
        <div class="text-center py-5 px-4 h-100 position-relative">
          <div class="profile-header-bg" style="position: absolute; top:0; left:0; right:0; height:120px; background: var(--grad-primary); opacity: 0.9; z-index:0;"></div>
          
          <div class="profile-avatar-wrap" style="position: relative; z-index: 5; margin-top: 30px; display: inline-block;">
            <?php if ($student['avatar']): ?>
                <div class="profile-avatar" style="width: 130px; height: 130px; border: 8px solid var(--bg-card); box-shadow: var(--shadow-md); overflow:hidden;">
                  <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($student['avatar']) ?>" style="width:100%; height:100%; object-fit:cover;">
                </div>
            <?php else: ?>
                <div class="profile-avatar" style="width: 130px; height: 130px; font-size: 56px; border: 8px solid var(--bg-card); background: var(--grad-emerald); box-shadow: var(--shadow-md); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800;">
                  <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
          </div>
          
          <div class="profile-info mt-4" style="position: relative; z-index: 5;">
            <h3 class="fw-800 m-0" style="font-size:28px; color: var(--text-main);"><?= htmlspecialchars($student['full_name']) ?></h3>
            <div class="mt-2 fw-500" style="font-size:15px; color: var(--text-muted);"><?= htmlspecialchars($student['personal_email'] ?? $student['email']) ?></div>
            <div class="profile-role-badge mt-4" style="background: var(--accent); color: #000; padding: 8px 24px; font-weight: 900; box-shadow: 0 10px 20px rgba(52, 211, 153, 0.2); border-radius: 30px; display: inline-block;">
              <i class="fas fa-shield-halved me-1"></i> STUDENT
            </div>
            
            <div class="mt-5 p-4 rounded-4 text-start" style="background: var(--border-light); border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
              
              <div class="d-flex align-items-center gap-3 mb-3">
                 <div class="stat-icon emerald" style="width: 40px; height: 40px; border-radius: 12px; font-size: 16px; background: var(--accent-light); color: var(--accent-dark); display: flex; align-items: center; justify-content: center;">
                   <i class="fas fa-id-card"></i>
                 </div>
                 <div>
                   <div class="fw-800" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">System ID</div>
                   <div class="fw-800" style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($student['student_id']) ?></div>
                 </div>
              </div>

              <div class="d-flex align-items-center gap-3">
                 <div class="stat-icon indigo" style="width: 40px; height: 40px; border-radius: 12px; font-size: 16px; background: rgba(91,78,250,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center;">
                   <i class="fas fa-calendar-check"></i>
                 </div>
                 <div>
                   <div class="fw-800" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Joined On</div>
                   <div class="fw-800" style="font-size: 14px; color: var(--text-main);"><?= date('M d, Y', strtotime($student['join_date'])) ?></div>
                 </div>
              </div>

            </div>

            <div class="mt-4 pt-4 text-center">
              <a href="settings.php" class="btn-primary-grad px-4 py-2 rounded-pill fw-800 d-inline-flex align-items-center gap-2" style="font-size:13px; text-decoration:none;">
                <i class="fas fa-user-edit"></i> Edit Settings
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Settings & Details Column -->
      <div class="col-lg-8">
        <div class="p-5">
          <div class="d-flex align-items-center gap-4 mb-5">
            <div class="stat-icon indigo" style="width: 55px; height: 55px; border-radius: 18px; font-size: 24px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-user-circle"></i>
            </div>
            <div>
              <h2 class="fw-900 m-0" style="font-size: 26px; color: var(--text-main); letter-spacing: -0.5px;">Personal Information</h2>
              <p class="m-0 fw-500" style="font-size: 14px; color: var(--text-muted);">To update these records, please contact the administration.</p>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-md-6">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">Phone Number</div>
              <div style="font-size:15px; font-weight:700; color:var(--text-main);"><?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></div>
            </div>
            <div class="col-md-6">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">National ID / NIC</div>
              <div style="font-size:15px; font-weight:700; color:var(--text-main);"><?= htmlspecialchars($student['nic_number'] ?? 'N/A') ?></div>
            </div>
            <div class="col-md-6">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">Office Email</div>
              <div style="font-size:14px; font-weight:700; color:var(--primary);"><?= htmlspecialchars($student['office_email'] ?? 'Not Assigned') ?></div>
            </div>
            <div class="col-md-6">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">Intake Batch</div>
              <div style="font-size:15px; font-weight:700; color:var(--text-main);"><?= htmlspecialchars($student['batch_number'] ?? 'No Batch') ?></div>
            </div>
            <div class="col-md-6">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">Guardian Name</div>
              <div style="font-size:15px; font-weight:700; color:var(--text-main);"><?= htmlspecialchars($student['guardian_name'] ?? 'N/A') ?></div>
            </div>
            <div class="col-md-6">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">Guardian Phone</div>
              <div style="font-size:15px; font-weight:700; color:var(--text-main);"><?= htmlspecialchars($student['guardian_phone'] ?? 'N/A') ?></div>
            </div>
            <div class="col-md-12">
              <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:6px; font-weight:800;">Mailing Address</div>
              <div style="font-size:14px; font-weight:600; line-height:1.6; color:var(--text-main);"><?= htmlspecialchars($student['house_address'] ?? 'No address on file.') ?></div>
            </div>
          </div>

          <div class="mt-5 pt-5 border-top" style="border-color: var(--border-color) !important;">
            <h4 class="fw-800 mb-4" style="color:var(--text-main);"><i class="fas fa-graduation-cap me-2 text-primary"></i> Academic Summary</h4>
            
            <?php if (empty($enrollments)): ?>
              <div class="p-4 rounded-4" style="background:var(--border-light); text-align:center;">
                <i class="fas fa-university" style="font-size:24px; color:#475569; margin-bottom:12px;"></i>
                <div style="font-size:14px; font-weight:600; color:var(--text-main);">No enrollments found.</div>
                <div style="font-size:12px; opacity:0.6; margin-top:4px;">You are not currently enrolled in any courses.</div>
              </div>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($enrollments as $e): ?>
                <div class="d-flex align-items-center justify-content-between p-3 rounded-4" style="background:var(--border-light); border: 1px solid var(--border-color);">
                  <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(91,78,250,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center;">
                      <i class="fas fa-bookmark"></i>
                    </div>
                    <div>
                      <div class="fw-800" style="color:var(--text-main); font-size:15px;"><?= htmlspecialchars($e['course_name']) ?></div>
                      <div style="font-size:11px; color:var(--text-muted); font-weight:600;">Enrolled on <?= date('M d, Y', strtotime($e['enrolled_at'])) ?></div>
                    </div>
                  </div>
                  <div>
                    <?php if($e['status'] === 'active'): ?>
                      <span style="padding:4px 12px; border-radius:100px; background:rgba(16,185,129,0.1); color:#10b981; font-size:10px; font-weight:800; letter-spacing:0.5px;">ACTIVE</span>
                    <?php else: ?>
                      <span style="padding:4px 12px; border-radius:100px; background:rgba(59,130,246,0.1); color:#3b82f6; font-size:10px; font-weight:800; letter-spacing:0.5px;"><?= strtoupper($e['status']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div><!-- /#page-content-restored -->
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
