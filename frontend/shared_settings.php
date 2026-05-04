<?php
// =====================================================
// ISSD Management - Shared Settings Page
// =====================================================
define('PAGE_TITLE', 'Settings');
require_once dirname(__DIR__, 1) . '/backend/config.php';
require_once dirname(__DIR__, 1) . '/backend/db.php';
require_once dirname(__DIR__, 1) . '/includes/auth.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $source = $user['source'] ?? 'users';
        $table  = ($source === 'lecturers') ? 'lecturers' : 'users';

        // Fetch current pass
        $stmt = $pdo->prepare("SELECT password FROM $table WHERE id = ?");
        $stmt->execute([$userId]);
        $dbPass = $stmt->fetchColumn();

        if (!$dbPass || (!password_verify($current_password, $dbPass) && $current_password !== $dbPass)) {
            $error = "Current password is incorrect.";
        } else {
            // Update
            try {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE $table SET password = ? WHERE id = ?")->execute([$hash, $userId]);
                $success = "Password changed successfully.";
            } catch (PDOException $e) {
                $error = "Failed to update password.";
            }
        }
    }
}

require_once dirname(__DIR__, 1) . '/includes/header.php';
require_once dirname(__DIR__, 1) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Account Settings</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Home &rsaquo; <span>Settings</span></div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert-lms success auto-dismiss"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-6 mx-auto">
        <div class="card-lms">
            <div class="card-lms-header"><div class="card-lms-title"><i class="fas fa-lock"></i> Change Password</div></div>
            <div class="card-lms-body">
                <form method="POST">
                    <div class="form-group-lms">
                        <label>Current Password</label>
                        <div class="input-wrap position-relative">
                            <input type="password" name="current_password" id="curr_pass" class="form-control-lms" style="padding-right:40px;" required>
                            <i class="fas fa-eye toggle-password" data-target="#curr_pass" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#64748b;"></i>
                        </div>
                    </div>
                    <div class="form-group-lms">
                        <label>New Password</label>
                        <div class="input-wrap position-relative">
                            <input type="password" name="new_password" id="new_pass" class="form-control-lms" style="padding-right:40px;" required>
                            <i class="fas fa-eye toggle-password" data-target="#new_pass" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#64748b;"></i>
                        </div>
                    </div>
                    <div class="form-group-lms">
                        <label>Confirm New Password</label>
                        <div class="input-wrap position-relative">
                            <input type="password" name="confirm_password" id="conf_pass" class="form-control-lms" style="padding-right:40px;" required>
                            <i class="fas fa-eye toggle-password" data-target="#conf_pass" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-lms btn-primary mt-2 w-100"><i class="fas fa-key"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>

