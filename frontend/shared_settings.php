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
    verifyCsrf(); // K2: CSRF protection
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

        // K1: use safe conditional queries instead of dynamic $table variable
        if ($source === 'lecturers') {
            $stmt = $pdo->prepare("SELECT password FROM lecturers WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        }
        $stmt->execute([$userId]);
        $dbPass = $stmt->fetchColumn();

        // K3: remove plaintext password fallback — only use password_verify()
        if (!$dbPass || !password_verify($current_password, $dbPass)) {
            $error = "Current password is incorrect.";
        } else {
            // Update
            try {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                if ($source === 'lecturers') {
                    $pdo->prepare("UPDATE lecturers SET password = ? WHERE id = ?")->execute([$hash, $userId]);
                } else {
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
                }
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

<style>
  /* --- PREMIUM BORDER REINFORCEMENT (FORCE APPLIED) --- */
  body.lms-dark-mode .card-lms,
  body.lms-dark-mode .stat-card,
  body.lms-dark-mode .bento-card,
  body.lms-dark-mode .glass-card {
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4), 0 0 15px rgba(255, 255, 255, 0.03) !important;
    border-radius: 24px !important;
    background: rgba(30, 41, 59, 0.5) !important;
    backdrop-filter: blur(20px) !important;
  }
</style>

<?php
// Fetch Activity Logs
$stmt = $pdo->prepare("SELECT action, details, ip_address, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$logs = $stmt->fetchAll();
?>

<div id="page-content">
  <div class="dark-layout-wrapper">
    <div class="welcome-header">
      <h1>Account Settings</h1>
      <p>Manage your security preferences and theme settings.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-lms danger auto-dismiss" style="margin-bottom:20px;"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert-lms success auto-dismiss" style="margin-bottom:20px;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="dark-grid-2" style="align-items: start;">
      
      <!-- Password Card -->
      <div class="glass-card">
        <div class="glass-card-title">
          <span><i class="fas fa-shield-alt" style="color:#22d3ee;"></i> Change Password</span>
        </div>
        
        <form method="POST" style="display:flex; flex-direction:column; gap:12px;">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="form-group-lms">
            <label style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.6; font-weight:700; margin-bottom:5px; display:block;">Current Password</label>
            <div class="position-relative">
              <input type="password" name="current_password" id="curr_pass" class="form-control-lms" style="height:42px; border-radius:12px; padding-right:45px;" required>
              <i class="fas fa-eye toggle-password" data-target="#curr_pass" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; opacity:0.5;"></i>
            </div>
          </div>
          
          <div class="form-group-lms">
            <label style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.6; font-weight:700; margin-bottom:5px; display:block;">New Password</label>
            <div class="position-relative">
              <input type="password" name="new_password" id="new_pass" class="form-control-lms" style="height:42px; border-radius:12px; padding-right:45px;" required>
              <i class="fas fa-eye toggle-password" data-target="#new_pass" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; opacity:0.5;"></i>
            </div>
          </div>
          
          <div class="form-group-lms">
            <label style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.6; font-weight:700; margin-bottom:5px; display:block;">Confirm New Password</label>
            <div class="position-relative">
              <input type="password" name="confirm_password" id="conf_pass" class="form-control-lms" style="height:42px; border-radius:12px; padding-right:45px;" required>
              <i class="fas fa-eye toggle-password" data-target="#conf_pass" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; opacity:0.5;"></i>
            </div>
          </div>
          
          <button type="submit" class="btn-primary-grad" style="padding:12px; border-radius:12px; font-weight:800; font-size:14px; margin-top:5px;">
            <i class="fas fa-key me-2"></i> Update Password
          </button>
        </form>
      </div>

      <!-- Right Column: Preferences & Activity -->
      <div style="display:flex; flex-direction:column; gap:24px;">
        
        <div class="glass-card" style="padding:30px;">
          <div style="display:flex; justify-content:between; align-items:center; gap:20px;">
            <div style="flex:1;">
              <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:inherit;">Appearance</h3>
              <p style="font-size:13px; opacity:0.6; margin:0;">Toggle between premium Dark and Light mode themes.</p>
            </div>
            <div class="form-check form-switch" style="font-size: 28px; padding:0; margin:0;">
                <input class="form-check-input" type="checkbox" id="themeToggle" style="cursor:pointer; margin:0;" <?= (isset($_COOKIE['lms_theme']) && $_COOKIE['lms_theme']==='dark') ? 'checked' : '' ?>>
            </div>
          </div>
        </div>
        
        <script>
        document.getElementById('themeToggle').addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('lms-dark-mode');
                document.cookie = "lms_theme=dark; path=/; max-age=31536000";
            } else {
                document.body.classList.remove('lms-dark-mode');
                document.cookie = "lms_theme=light; path=/; max-age=31536000";
            }
        });
        </script>

        <!-- Recent Activity Card -->
        <div class="glass-card" style="display:flex; flex-direction:column;">
          <div class="glass-card-title">
            <span><i class="fas fa-history" style="color:#c084fc;"></i> Recent Activity</span>
          </div>
          
          <div style="max-height: 320px; overflow-y: auto; padding: 10px 15px;" class="custom-scrollbar">
            <?php if (empty($logs)): ?>
              <p style="padding:20px; text-align:center; opacity:0.5; font-size:13px;">No recent activity found.</p>
            <?php else: ?>
              <div class="timeline-container">
                <?php foreach ($logs as $log): 
                   $icon = 'fa-info-circle'; $iconColor = '#94a3b8';
                   if (strpos($log['action'], 'login') !== false) { $icon = 'fa-sign-in-alt'; $iconColor = '#22d3ee'; }
                   elseif (strpos($log['action'], 'logout') !== false) { $icon = 'fa-sign-out-alt'; $iconColor = '#f87171'; }
                   elseif (strpos($log['action'], 'password') !== false) { $icon = 'fa-key'; $iconColor = '#c084fc'; }
                ?>
                <div class="timeline-item">
                  <div class="timeline-dot" style="background:<?= $iconColor ?>;">
                    <i class="fas <?= $icon ?>"></i>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-header">
                      <span class="timeline-title"><?= ucfirst(htmlspecialchars($log['action'])) ?></span>
                      <span class="timeline-time"><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
                    </div>
                    <div class="timeline-desc"><?= htmlspecialchars($log['details']) ?></div>
                    <div class="timeline-meta"><i class="fas fa-desktop"></i> <?= htmlspecialchars($log['ip_address']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <style>
          .timeline-container {
            position: relative;
            padding-left: 20px;
          }
          .timeline-container::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 10px;
            bottom: 10px;
            width: 1px;
            background: rgba(255,255,255,0.08);
          }
          body:not(.lms-dark-mode) .timeline-container::before { background: rgba(0,0,0,0.08); }
          
          .timeline-item {
            position: relative;
            margin-bottom: 24px;
            padding: 8px 12px;
            padding-left: 20px;
            border-radius: 12px;
            transition: all 0.2s ease;
          }
          .timeline-item:hover {
            background: rgba(255,255,255,0.03);
          }
          body:not(.lms-dark-mode) .timeline-item:hover {
            background: rgba(0,0,0,0.02);
          }
          .timeline-item:last-child { margin-bottom: 0; }
          
          .timeline-dot {
            position: absolute;
            left: -20px;
            top: 10px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 9px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            z-index: 1;
            border: 2px solid #fff;
          }
          body.lms-dark-mode .timeline-dot { border-color: #1e293b; }
          .timeline-dot i { transform: scale(1); }
          
          .timeline-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
          }
          .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
          }
          .timeline-title {
            font-size: 13px;
            font-weight: 700;
            color: inherit;
          }
          .timeline-time {
            font-size: 10px;
            font-weight: 600;
            opacity: 0.5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }
          .timeline-desc {
            font-size: 11px;
            opacity: 0.7;
            line-height: 1.4;
          }
          .timeline-meta {
            font-size: 9px;
            opacity: 0.4;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
          }
          
          .custom-scrollbar::-webkit-scrollbar { width: 4px; }
          .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
          .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
          body:not(.lms-dark-mode) .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); }
        </style>

      </div>

    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>

