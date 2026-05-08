<?php
// =====================================================
// ISSD Management - Shared Profile Page Template
// =====================================================
define('PAGE_TITLE', 'My Profile');
require_once dirname(__DIR__, 1) . '/backend/config.php';
require_once dirname(__DIR__, 1) . '/backend/db.php';
require_once dirname(__DIR__, 1) . '/includes/auth.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];
$role = $user['role'];
$error = '';
$success = '';
$source = $user['source'] ?? 'users';
$table = ($source === 'lecturers') ? 'lecturers' : 'users';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name) {
        $error = "Name is required.";
    } else {
        try {
            $pdo->prepare("UPDATE $table SET name = ?, phone = ? WHERE id = ?")
                ->execute([$name, $phone, $userId]);
            
            // Update session
            $_SESSION['user']['name'] = $name;
            $user['name'] = $name;
            $success = "Profile updated successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update profile.";
        }
    }
}

// Fetch full user details
$userDetails = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
$userDetails->execute([$userId]);
$userDetails = $userDetails->fetch();

// Ensure role matches for display
if (!isset($userDetails['role'])) {
    $userDetails['role'] = $role;
}

require_once dirname(__DIR__, 1) . '/includes/header.php';
require_once dirname(__DIR__, 1) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Profile</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Home &rsaquo; <span>Profile</span></div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert-lms success auto-dismiss"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

<style>
  .profile-premium-card {
    background: var(--bg-card);
    backdrop-filter: blur(15px);
    border: 1px solid var(--border-color);
    border-radius: 28px;
    overflow: hidden;
    transition: all 0.3s ease;
  }
  .profile-banner {
    height: 100px;
    background: linear-gradient(135deg, #1e4d4d 0%, #34d399 100%);
    position: relative;
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
  
  .input-icon-wrap {
    position: relative;
  }
  .input-icon-wrap i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
    opacity: 0.5;
    font-size: 14px;
  }
  .input-icon-wrap .form-control-lms {
    padding-left: 42px !important;
  }
  
  .form-label-lms {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: block;
  }
</style>

<div class="card-lms overflow-hidden shadow-premium" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px);">
  <div class="row g-0">
    <!-- Profile Info Column -->
    <div class="col-lg-4" style="background: rgba(255, 255, 255, 0.02); border-right: 1px solid rgba(255, 255, 255, 0.1);">
      <div class="text-center py-5 px-4 h-100 position-relative">
        <!-- Banner using background instead of absolute div to prevent edge line issues -->
        <div class="profile-header-bg" style="position: absolute; top:0; left:0; right:0; height:120px; background: var(--grad-primary); opacity: 0.8; z-index:0;"></div>
        
        <div class="profile-avatar-wrap" style="position: relative; z-index: 5; margin-top: 30px; display: inline-block;">
          <div class="profile-avatar" style="width: 130px; height: 130px; font-size: 56px; border: 8px solid #141b1b; background: var(--grad-emerald); box-shadow: 0 20px 40px rgba(0,0,0,0.4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800;">
            <?= strtoupper(substr($userDetails['name'], 0, 1)) ?>
          </div>
        </div>
        
        <div class="profile-info mt-4" style="position: relative; z-index: 5;">
          <h3 class="fw-800 m-0" style="font-size:28px; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"><?= htmlspecialchars($userDetails['name']) ?></h3>
          <div class="mt-2 fw-500" style="font-size:15px; color: rgba(255,255,255,0.6) !important;"><?= htmlspecialchars($userDetails['email']) ?></div>
          <div class="profile-role-badge mt-4" style="background: var(--accent); color: #000; padding: 8px 24px; font-weight: 900; box-shadow: 0 10px 20px rgba(52, 211, 153, 0.2);">
            <i class="fas fa-shield-halved me-1"></i> <?= strtoupper($userDetails['role']) ?>
          </div>
          
          <div class="mt-5 p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
            <div class="d-flex align-items-center gap-3 text-start">
               <div class="stat-icon emerald" style="width: 40px; height: 40px; border-radius: 12px; font-size: 16px; background: rgba(52, 211, 153, 0.2); color: #34d399; display: flex; align-items: center; justify-content: center;">
                 <i class="fas fa-clock-rotate-left"></i>
               </div>
               <div>
                 <div class="fw-800" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5) !important;">Member Since</div>
                 <div class="fw-800" style="font-size: 14px; color: #fff;"><?= date('M d, Y', strtotime($userDetails['created_at'])) ?></div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Settings Form Column -->
    <div class="col-lg-8">
      <div class="p-5">
        <div class="d-flex align-items-center gap-4 mb-5">
          <div class="stat-icon indigo" style="width: 55px; height: 55px; border-radius: 18px; font-size: 24px; background: rgba(99, 102, 241, 0.15); color: #818cf8; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-user-gear"></i>
          </div>
          <div>
            <h2 class="fw-900 m-0" style="font-size: 26px; color: #fff; letter-spacing: -0.5px;">Account Settings</h2>
            <p class="m-0 fw-500" style="font-size: 14px; color: rgba(255,255,255,0.5) !important;">Manage your personal profile and contact information</p>
          </div>
        </div>

        <form method="POST">
          <div class="row g-4">
            <div class="col-md-12">
              <div class="form-group-lms">
                <label class="form-label-lms" style="color: rgba(255,255,255,0.4);">Full Name</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-user" style="color: var(--accent);"></i>
                  <input type="text" name="name" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['name']) ?>" 
                         style="background: rgba(255,255,255,0.03); height: 55px; border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms" style="color: rgba(255,255,255,0.4);">Email Address</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-envelope" style="color: var(--accent);"></i>
                  <input type="email" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['email']) ?>" 
                         style="background: rgba(255,255,255,0.05); height: 55px; border: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.4); cursor: not-allowed;" disabled>
                </div>
                <small class="mt-2 d-block" style="font-size: 11px; color: rgba(255,255,255,0.3) !important;">Email cannot be changed manually. Contact admin if needed.</small>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms" style="color: rgba(255,255,255,0.4);">Phone Number</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-phone" style="color: var(--accent);"></i>
                  <input type="text" name="phone" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>" 
                         placeholder="e.g. +94 77 123 4567"
                         style="background: rgba(255,255,255,0.03); height: 55px; border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-5 pt-5 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
              <p class="m-0" style="font-size: 13px; max-width: 350px; color: rgba(255,255,255,0.4) !important;">
                Updating your profile will reflect across all modules. Keep your contact details current for important notifications.
              </p>
              <button type="submit" class="btn-primary-grad px-5 py-3 rounded-pill fw-900 d-flex align-items-center gap-2 shadow-lg" style="font-size: 16px;">
                <i class="fas fa-save"></i> Save Profile Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>

<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>

