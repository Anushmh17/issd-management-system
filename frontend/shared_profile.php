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
    verifyCsrf(); // K2: CSRF protection
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name) {
        $error = "Name is required.";
    } else {
        try {
            // K1: use safe table resolver instead of dynamic $table interpolation
            if ($source === 'lecturers') {
                $pdo->prepare("UPDATE lecturers SET name = ?, phone = ? WHERE id = ?")
                    ->execute([$name, $phone, $userId]);
            } else {
                $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")
                    ->execute([$name, $phone, $userId]);
            }
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

<<div class="card-lms overflow-hidden shadow-premium" style="background: var(--bg-card); border: 1px solid var(--border-color); backdrop-filter: blur(20px);">
  <div class="row g-0">
    <!-- Profile Info Column -->
    <div class="col-lg-4" style="background: rgba(var(--primary-rgb), 0.02); border-right: 1px solid var(--border-color);">
      <div class="text-center py-5 px-4 h-100 position-relative">
        <!-- Banner -->
        <div class="profile-header-bg" style="position: absolute; top:0; left:0; right:0; height:120px; background: var(--grad-primary); opacity: 0.9; z-index:0;"></div>
        
        <div class="profile-avatar-wrap" style="position: relative; z-index: 5; margin-top: 30px; display: inline-block;">
          <div class="profile-avatar" style="width: 130px; height: 130px; font-size: 56px; border: 8px solid var(--bg-card); background: var(--grad-emerald); box-shadow: var(--shadow-md); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800;">
            <?= strtoupper(substr($userDetails['name'] ?? 'U', 0, 1)) ?>
          </div>
        </div>
        
        <div class="profile-info mt-4" style="position: relative; z-index: 5;">
          <h3 class="fw-800 m-0" style="font-size:28px; color: var(--text-main);"><?= htmlspecialchars($userDetails['name'] ?? 'User') ?></h3>
          <div class="mt-2 fw-500" style="font-size:15px; color: var(--text-muted);"><?= htmlspecialchars($userDetails['email'] ?? '') ?></div>
          <div class="profile-role-badge mt-4" style="background: var(--accent); color: #000; padding: 8px 24px; font-weight: 900; box-shadow: 0 10px 20px rgba(52, 211, 153, 0.2); border-radius: 30px; display: inline-block;">
            <i class="fas fa-shield-halved me-1"></i> <?= strtoupper($userDetails['role'] ?? 'Member') ?>
          </div>
          
          <div class="mt-5 p-4 rounded-4" style="background: var(--border-light); border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
            <div class="d-flex align-items-center gap-3 text-start">
               <div class="stat-icon emerald" style="width: 40px; height: 40px; border-radius: 12px; font-size: 16px; background: var(--accent-light); color: var(--accent-dark); display: flex; align-items: center; justify-content: center;">
                 <i class="fas fa-clock-rotate-left"></i>
               </div>
               <div>
                 <div class="fw-800" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Member Since</div>
                 <div class="fw-800" style="font-size: 14px; color: var(--text-main);"><?= date('M d, Y', strtotime($userDetails['created_at'] ?? 'now')) ?></div>
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
          <div class="stat-icon indigo" style="width: 55px; height: 55px; border-radius: 18px; font-size: 24px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-user-gear"></i>
          </div>
          <div>
            <h2 class="fw-900 m-0" style="font-size: 26px; color: var(--text-main); letter-spacing: -0.5px;">Account Settings</h2>
            <p class="m-0 fw-500" style="font-size: 14px; color: var(--text-muted);">Manage your personal profile and contact information</p>
          </div>
        </div>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="row g-4">
            <div class="col-md-12">
              <div class="form-group-lms">
                <label class="form-label-lms" style="color: var(--text-muted); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Full Name</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-user" style="color: var(--accent); position: absolute; left: 15px; top: 50%; transform: translateY(-50%);"></i>
                  <input type="text" name="name" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['name'] ?? '') ?>" 
                         style="background: var(--bg-page); height: 55px; border: 1px solid var(--border-color); color: var(--text-main); padding-left: 45px;" required>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms" style="color: var(--text-muted); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Email Address</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-envelope" style="color: var(--accent); position: absolute; left: 15px; top: 50%; transform: translateY(-50%);"></i>
                  <input type="email" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['email'] ?? '') ?>" 
                         style="background: var(--border-light); height: 55px; border: 1px solid var(--border-light); color: var(--text-muted); cursor: not-allowed; padding-left: 45px;" disabled>
                </div>
                <small class="mt-2 d-block" style="font-size: 11px; color: var(--text-muted);">Email cannot be changed manually. Contact admin if needed.</small>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms" style="color: var(--text-muted); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Phone Number</label>
                <div class="phone-input-group" style="height:55px; background: var(--bg-page); border: 1px solid var(--border-color); display: flex; align-items: center; border-radius: var(--radius-md); overflow: hidden;">
                  <span class="phone-prefix" style="padding: 0 15px; background: var(--border-light); height: 100%; display: flex; align-items: center; font-weight: 700; color: var(--text-muted);">+94</span>
                  <input type="tel" name="phone" 
                         value="<?= htmlspecialchars(stripSriLankanCountryCode($userDetails['phone'] ?? '')) ?>" 
                         placeholder="7XXXXXXXX" maxlength="9" pattern="[0-9]{9}"
                         oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.startsWith('0')) this.value = this.value.substring(1);"
                         style="background: transparent !important; border: none !important; color: var(--text-main); flex: 1; padding: 0 15px; height: 100%; outline: none;">
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-5 pt-5 border-top" style="border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
              <p class="m-0" style="font-size: 13px; max-width: 350px; color: var(--text-muted);">
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

