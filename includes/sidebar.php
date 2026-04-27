<?php
// =====================================================
// LEARN Management - Shared Sidebar
// =====================================================
if (!isset($user)) $user = currentUser();
$role   = $user['role']   ?? 'student';
$uname  = $user['name']   ?? 'User';
$avatar = $user['avatar'] ?? null;
$initial = strtoupper(substr($uname, 0, 1));

// Notifications Initialization
require_once dirname(__DIR__) . '/backend/notification_controller.php';
$recentNotifs = getRecentNotifications($pdo, (int)$user['id'], $role);
$unreadNotifs = array_filter($recentNotifs, function($n) { return !($n['is_read'] ?? false); });
$notifCount    = count($unreadNotifs);


// Build nav based on role
$adminNav = [
  ['icon'=>'fa-gauge',        'label'=>'Dashboard',   'href'=>BASE_URL.'/frontend/admin/dashboard.php'],
  ['icon'=>'fa-bullseye',     'label'=>'Leads',       'href'=>BASE_URL.'/admin/leads/index.php'],
  ['icon'=>'fa-users',        'label'=>'Students',    'href'=>BASE_URL.'/admin/students/index.php'],
  ['icon'=>'fa-folder-open',  'label'=>'Documents',   'href'=>BASE_URL.'/admin/documents/index.php'],
  ['icon'=>'fa-award',        'label'=>'Certificates','href'=>BASE_URL.'/admin/certificates/index.php'],
  ['icon'=>'fa-chalkboard-user','label'=>'Lecturers', 'href'=>BASE_URL.'/admin/lecturers/index.php'],
  ['icon'=>'fa-book-open',    'label'=>'Courses',     'href'=>BASE_URL.'/admin/courses/index.php'],
  ['icon'=>'fa-list-check',   'label'=>'Enrollments', 'href'=>BASE_URL.'/frontend/admin/enrollments.php'],
  ['icon'=>'fa-money-bill-wave','label'=>'Student Payments',  'href'=>BASE_URL.'/admin/payments/index.php'],
  ['icon'=>'fa-sack-dollar',  'label'=>'Lecturer Pays','href'=>BASE_URL.'/admin/lecturer_payments/index.php'],
  ['icon'=>'fa-bell',         'label'=>'Notices',     'href'=>BASE_URL.'/frontend/admin/notices.php'],
  ['icon'=>'fa-chart-line',   'label'=>'Reports',     'href'=>BASE_URL.'/frontend/admin/reports.php'],
];
$lecturerNav = [
  ['icon'=>'fa-gauge',        'label'=>'Dashboard',   'href'=>BASE_URL.'/frontend/lecturer/dashboard.php'],
  ['icon'=>'fa-users',        'label'=>'My Students',  'href'=>BASE_URL.'/frontend/lecturer/students.php'],
  ['icon'=>'fa-book-open',    'label'=>'My Courses',   'href'=>BASE_URL.'/frontend/lecturer/courses.php'],
  ['icon'=>'fa-file-alt',     'label'=>'Assignments',  'href'=>BASE_URL.'/frontend/lecturer/assignments/index.php'],
  ['icon'=>'fa-bell',         'label'=>'Notices',      'href'=>BASE_URL.'/frontend/lecturer/notices.php'],
];
$studentNav = [
  ['icon'=>'fa-gauge',        'label'=>'Dashboard',   'href'=>BASE_URL.'/frontend/student/dashboard.php'],
  ['icon'=>'fa-book-open',    'label'=>'My Courses',  'href'=>BASE_URL.'/frontend/student/courses.php'],
  ['icon'=>'fa-file-alt',     'label'=>'Assignments', 'href'=>BASE_URL.'/frontend/student/assignments/index.php'],
  ['icon'=>'fa-money-bill-wave','label'=>'Payments',  'href'=>BASE_URL.'/frontend/student/payments.php'],
  ['icon'=>'fa-bell',         'label'=>'Notices',     'href'=>BASE_URL.'/frontend/student/notices.php'],
];

$navItems = match($role) {
  'admin'    => $adminNav,
  'lecturer' => $lecturerNav,
  default    => $studentNav,
};

$roleLabels = ['admin'=>'Administrator','lecturer'=>'Lecturer','student'=>'Student'];
?>

<!-- Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== SIDEBAR ===== -->
<nav id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon">
      <i class="fas fa-graduation-cap"></i>
    </div>
    <div class="brand-text">
      Learn <span>Management</span>
      <span class="brand-sub">INSTITUTE SYSTEM</span>
    </div>
    <button id="sidebarClose" class="d-md-none sidebar-close-btn" title="Close Sidebar">
      <i class="fas fa-arrow-left"></i>
    </button>
  </div>

  <!-- User Info -->
  <div class="sidebar-user">
    <?php if ($avatar): ?>
      <img src="<?= htmlspecialchars(BASE_URL.'/assets/uploads/'.$avatar) ?>" class="user-avatar" alt="avatar" style="object-fit:cover; border: 2px solid rgba(255,255,255,0.1);">
    <?php else: ?>
      <div class="user-avatar" style="background: linear-gradient(135deg, var(--primary), var(--accent));"><?= $initial ?></div>
    <?php endif; ?>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($uname) ?></div>
      <div class="user-role"><?= $roleLabels[$role] ?? $role ?></div>
    </div>
  </div>

  <!-- Navigation -->
  <div class="sidebar-nav">
    <div class="nav-section-label">Navigation</div>
    <?php foreach ($navItems as $item): ?>
      <div class="nav-item">
        <a href="<?= $item['href'] ?>" class="nav-link">
          <i class="fas <?= $item['icon'] ?>"></i>
          <?= $item['label'] ?>
          <?php if (!empty($item['badge'])): ?>
            <span class="nav-badge"><?= $item['badge'] ?></span>
          <?php endif; ?>
        </a>
      </div>
    <?php endforeach; ?>

    <div class="nav-section-label" style="margin-top:10px;">Account</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/frontend/<?= $role ?>/profile.php" class="nav-link">
        <i class="fas fa-user-circle"></i> My Profile
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/frontend/<?= $role ?>/settings.php" class="nav-link">
        <i class="fas fa-gear"></i> Settings
      </a>
    </div>
  </div>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer">
    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#logoutModal">
      <i class="fas fa-right-from-bracket"></i> Logout
    </a>
  </div>

</nav>
<!-- ===== END SIDEBAR ===== -->

<!-- ===== MAIN CONTENT WRAPPER ===== -->
<div id="main-content">

  <!-- ===== TOP NAVBAR ===== -->
  <header id="top-navbar">
    <div class="navbar-left">
      <button id="sidebarToggle" title="Toggle Sidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="navbar-title">
        Learn <span>Management</span>
      </div>
    </div>
    <div class="navbar-right">
      <!-- Notifications -->
      <div class="dropdown">
        <button class="navbar-icon-btn" id="notifDropdown" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="Notifications">
          <i class="fas fa-bell"></i>
          <span class="badge rounded-pill bg-danger position-absolute" id="notif-badge" style="font-size:8px;top:5px;right:5px;padding:2px 4px; display:none;">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end notif-dropdown shadow-lg" aria-labelledby="notifDropdown">
          <div class="notif-header d-flex justify-content-between align-items-center">
            <span class="fw-700">Notifications</span>
            <span class="badge bg-white text-primary" id="notif-count-text" style="font-size:10px;">0 New</span>
          </div>
          
          <!-- Categorization Tabs -->
          <div class="notif-tabs">
            <div class="notif-tab active" data-category="all">All</div>
            <div class="notif-tab" data-category="call">Calls</div>
            <div class="notif-tab" data-category="payment">Payments</div>
            <div class="notif-tab" data-category="system">System</div>
          </div>

          <!-- Notification List -->
          <div class="notif-list" id="notif-items-list">
            <div class="p-4 text-center text-muted">
                <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                <div style="font-size:11px;">Loading notifications...</div>
            </div>
          </div>

          <div class="p-2 text-center" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
            <a href="<?= BASE_URL ?>/frontend/<?= $role ?>/notifications.php" class="fw-700 text-primary" style="font-size:11px;">View Full History</a>
          </div>
        </div>
      </div>

      <!-- Real-time Notifications Script -->
      <script>const BASE_URL = '<?= BASE_URL ?>';</script>
      <script src="<?= BASE_URL ?>/assets/js/notifications.js"></script>
      <!-- Help -->
      <button class="navbar-icon-btn" title="Help" data-bs-toggle="modal" data-bs-target="#helpGuideModal">
        <i class="fas fa-circle-question"></i>
      </button>
      <!-- User dropdown -->
      <div class="navbar-user dropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="nav-avatar"><?= $initial ?></div>
        <span class="nav-uname d-none d-md-block"><?= htmlspecialchars($uname) ?></span>
        <i class="fas fa-chevron-down" style="font-size:10px;color:#aaa;margin-left:4px;"></i>
      </div>
      <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;border-radius:12px;border:1.5px solid #e8e4ff;box-shadow:0 8px 30px rgba(91,78,250,0.15);">
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/frontend/<?= $role ?>/profile.php"><i class="fas fa-user me-2 text-primary"></i>Profile</a></li>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/frontend/<?= $role ?>/settings.php"><i class="fas fa-gear me-2 text-primary"></i>Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </header>
  <!-- ===== END TOP NAVBAR ===== -->

<?php
// Sidebar footer logout also
$sidebarFooterLogout = 'javascript:void(0)" data-bs-toggle="modal" data-bs-target="#logoutModal"';
?>


<?php
// Flash message display
$flash = getFlash();
if ($flash): ?>
<div id="page-content" style="padding-bottom:0;">
  <div class="alert-lms <?= htmlspecialchars($flash['type']) ?> auto-dismiss">
    <i class="fas <?= $flash['type']==='success'?'fa-check-circle':($flash['type']==='danger'?'fa-times-circle':'fa-info-circle') ?>"></i>
    <?= $flash['message'] ?>
  </div>
</div>
<?php endif; ?>
