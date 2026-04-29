<?php
// =====================================================
// LEARN Management - Login Page
// =====================================================
require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? redirect
if (isLoggedIn()) {
    $role = currentRole();
    redirect(BASE_URL . '/frontend/' . $role . '/dashboard.php');
}

$error   = '';
$success = '';
$info    = '';

// Handle query messages
if (isset($_GET['timeout']))    $info    = 'Your session expired. Please log in again.';
if (isset($_GET['forbidden']))  $error   = 'Access denied. You don\'t have permission.';
if (isset($_GET['logged_out'])) $success = 'You have been logged out successfully.';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            $role = $result['role'];
            redirect(BASE_URL . '/frontend/' . $role . '/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | LEARN Management</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="modern-login">

<div class="login-wrapper">
  <div class="login-container">
    
    <!-- Left Panel: Illustration & Branding -->
    <div class="login-visual">
      <div class="visual-content">
        <div class="brand-badge">
          <i class="fas fa-graduation-cap"></i>
          <span>Learn Management</span>
        </div>
        <img src="<?= BASE_URL ?>/assets/images/login-bg.png" alt="Login Illustration" class="illustration">
        <div class="visual-footer">
          <h3>Empowering Education</h3>
          <p>Login to your account to get access Premium features.</p>
        </div>
      </div>
      <div class="visual-overlay"></div>
    </div>

    <!-- Right Panel: Login Form -->
    <div class="login-form-panel">
      <div class="form-header">
        <span class="greeting">Hello!</span>
        <h2 class="welcome-text">Good Morning</h2>
      </div>

      <div class="form-body">
        <p class="form-title">Log in your account</p>

        <!-- Alerts -->
        <?php if ($error): ?>
          <div class="modern-alert error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="modern-alert success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($info): ?>
          <div class="modern-alert info"><i class="fas fa-circle-info"></i> <?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="input-group-modern">
            <label for="email">Username</label>
            <input
              type="text"
              id="email"
              name="email"
              placeholder="Enter your email or username"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required
            >
            <span class="input-line"></span>
          </div>

          <div class="input-group-modern">
            <label for="password">Password</label>
            <div class="password-field">
              <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                required
              >
              <i class="fas fa-eye toggle-password" data-target="#password"></i>
            </div>
            <span class="input-line"></span>
          </div>

          <div class="form-options">
            <a href="#" class="forgot-link">forget password?</a>
          </div>

          <button type="submit" class="btn-modern-login" id="loginBtn">
            Log In
          </button>
        </form>

        <div class="form-footer">
          <p>Don't have an account? <a href="#" class="create-link">Create Account</a></p>
        </div>

      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle Password
document.querySelectorAll('.toggle-password').forEach(icon => {
  icon.addEventListener('click', function() {
    const target = document.querySelector(this.getAttribute('data-target'));
    if (target.type === 'password') {
      target.type = 'text';
      this.classList.remove('fa-eye');
      this.classList.add('fa-eye-slash');
    } else {
      target.type = 'password';
      this.classList.remove('fa-eye-slash');
      this.classList.add('fa-eye');
    }
  });
});

// Login loading state
document.getElementById('loginForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.style.opacity = '0.8';
    btn.style.pointerEvents = 'none';
  }
});
</script>
</body>
</html>

