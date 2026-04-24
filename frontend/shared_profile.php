<?php
// =====================================================
// LEARN Management - Shared Profile Page Template
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

  <div class="row g-4">
    <div class="col-md-4">
        <div class="card-lms text-center p-4">
            <div class="avatar-initials mb-3" style="width:100px;height:100px;font-size:36px;margin:0 auto;">
                <?= strtoupper(substr($userDetails['name'], 0, 1)) ?>
            </div>
            <h3 class="fw-700 m-0" style="font-size:20px;"><?= htmlspecialchars($userDetails['name']) ?></h3>
            <div class="text-muted mt-1 mb-3" style="font-size:13px;"><?= htmlspecialchars($userDetails['email']) ?></div>
            <span class="badge-lms primary" style="font-size:12px;padding:6px 12px;"><?= ucfirst($userDetails['role']) ?></span>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card-lms">
            <div class="card-lms-header"><div class="card-lms-title"><i class="fas fa-user-edit"></i> Edit Profile</div></div>
            <div class="card-lms-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group-lms">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control-lms" value="<?= htmlspecialchars($userDetails['name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-lms">
                                <label>Email Address (Cannot change)</label>
                                <input type="email" class="form-control-lms" value="<?= htmlspecialchars($userDetails['email']) ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-lms">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control-lms" value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-lms btn-primary mt-3"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>
