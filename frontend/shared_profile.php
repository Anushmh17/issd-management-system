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

<div class="row g-4">
  <div class="col-lg-4">
    <div class="profile-premium-card text-center">
      <div class="profile-banner"></div>
      <div class="profile-avatar-wrap">
        <div class="profile-avatar">
          <?= strtoupper(substr($userDetails['name'], 0, 1)) ?>
        </div>
      </div>
      <div class="profile-info">
        <h3 class="fw-800 m-0" style="font-size:22px; color: var(--text-main);"><?= htmlspecialchars($userDetails['name']) ?></h3>
        <div class="text-muted mt-1" style="font-size:14px;"><?= htmlspecialchars($userDetails['email']) ?></div>
        <div class="profile-role-badge">
          <i class="fas fa-shield-halved me-1"></i> <?= ucfirst($userDetails['role']) ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-8">
    <div class="card-lms">
      <div class="card-lms-header">
        <div class="card-lms-title">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon indigo" style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
              <i class="fas fa-user-gear"></i>
            </div>
            <div>
              <div style="font-size: 18px; font-weight: 800;">Account Settings</div>
              <div style="font-size: 12px; font-weight: 500; color: var(--text-muted);">Manage your personal information</div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-lms-body">
        <form method="POST">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms">Full Name</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-user"></i>
                  <input type="text" name="name" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['name']) ?>" required>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms">Email Address</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-envelope"></i>
                  <input type="email" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['email']) ?>" disabled>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group-lms">
                <label class="form-label-lms">Phone Number</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-phone"></i>
                  <input type="text" name="phone" class="form-control-lms" 
                         value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>" placeholder="e.g. +94 77 123 4567">
                </div>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn-primary-grad px-4 py-2 rounded-3 fw-800 d-flex align-items-center gap-2">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>

<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>

