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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="login-page">
  <div class="login-card">

    <!-- Brand -->
    <div class="login-brand">
      <div class="brand-icon-lg"><i class="fas fa-graduation-cap"></i></div>
      <h1>Learn Management</h1>
      <p>Institute Management System</p>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
      <div class="login-alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="login-alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($info): ?>
      <div class="login-alert info"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($info) ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="" id="loginForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="mb-3">
        <label for="email">Email / Username</label>
        <div class="input-wrap">
          <i class="fas fa-user"></i>
          <input
            type="text"
            id="email"
            name="email"
            placeholder="Email or username"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
          >
        </div>
      </div>

      <div class="mb-3">
        <label for="password">Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
          >
          <i class="fas fa-eye toggle-password" data-target="#password"></i>
        </div>
      </div>

      <button type="submit" class="btn-login" id="loginBtn">
        <i class="fas fa-right-to-bracket"></i> Sign In
      </button>
    </form>

    <!-- Demo credentials -->
    <div class="login-footer-text" style="margin-top:20px;">
      <hr style="border-color:rgba(255,255,255,0.08);margin-bottom:14px;">
      <strong style="color:rgba(255,255,255,0.5);font-size:10px;letter-spacing:0.8px;">DEMO CREDENTIALS</strong>
      <div style="margin-top:8px;display:flex;flex-direction:column;gap:5px;font-size:12px;">
        <span>Admin: <code style="color:#a0f0d8;background:rgba(255,255,255,0.06);padding:2px 6px;border-radius:4px;">admin@learn.com</code></span>
        <span>Password: <code style="color:#a0f0d8;background:rgba(255,255,255,0.06);padding:2px 6px;border-radius:4px;">Admin@1234</code></span>
        <span style="color:rgba(255,255,255,0.35);margin-top:4px;">Lecturers can use email or username</span>
      </div>
    </div>

    <div class="login-footer-text" style="margin-top:14px;">
      &copy; <?= date('Y') ?> LEARN Management &bull; All rights reserved
    </div>

  </div>
</div>

<!-- Floating particles on background -->
<canvas id="particleCanvas" style="position:fixed;inset:0;z-index:0;pointer-events:none;"></canvas>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Particle canvas background
(function(){
  const canvas = document.getElementById('particleCanvas');
  const ctx = canvas.getContext('2d');
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;
  window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  });
  const particles = Array.from({length: 50}, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 2 + 0.5,
    dx: (Math.random() - 0.5) * 0.4,
    dy: (Math.random() - 0.5) * 0.4,
    alpha: Math.random() * 0.5 + 0.1
  }));
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(198,180,255,${p.alpha})`;
      ctx.fill();
      p.x += p.dx; p.y += p.dy;
      if (p.x < 0 || p.x > canvas.width)  p.dx *= -1;
      if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
    });
    requestAnimationFrame(draw);
  }
  draw();
})();

// Add loading state to login button
document.getElementById('loginForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
});
</script>
</body>
</html>
