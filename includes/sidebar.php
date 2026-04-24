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
$notifications = getRecentNotifications($pdo, (int)$user['id'], $role);
$unreadNotifs = array_filter($notifications, function($n) { return !($n['is_read'] ?? false); });
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
    <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
    <div class="brand-text">
      Learn Management
      <span>Institute Management System</span>
    </div>
  </div>

  <!-- User Info -->
  <div class="sidebar-user">
    <?php if ($avatar): ?>
      <img src="<?= htmlspecialchars(BASE_URL.'/assets/uploads/'.$avatar) ?>" class="user-avatar" alt="avatar" style="object-fit:cover;">
    <?php else: ?>
      <div class="user-avatar"><?= $initial ?></div>
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
        Learn Management
      </div>
    </div>
    <div class="navbar-right">
      <!-- Notifications -->
      <div class="dropdown">
        <button class="navbar-icon-btn position-relative" id="notifDropdown" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="Notifications">
          <i class="fas fa-bell"></i>
          <?php if ($notifCount > 0): ?>
            <span class="badge rounded-pill bg-danger position-absolute" style="font-size:8px;top:5px;right:5px;padding:2px 4px;"><?= $notifCount ?></span>
          <?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="notifDropdown" style="width:320px; max-width:calc(100vw - 24px); border:none; border-radius:12px; overflow:hidden; padding:0;">
          <div class="bg-primary text-white p-3 d-flex justify-between align-center">
            <span class="fw-700" style="font-size:14px;">Notifications</span>
            <span class="badge bg-white text-primary" style="font-size:10px;"><?= $notifCount ?> New</span>
          </div>
          <div style="max-height:350px;overflow-y:auto;">
            <?php if (empty($notifications)): ?>
              <div class="p-4 text-center text-muted">
                <i class="fas fa-bell-slash mb-2 d-block" style="font-size:24px;opacity:0.3;"></i>
                <div style="font-size:12px;">No new notifications</div>
              </div>
            <?php else: ?>
              <?php foreach ($notifications as $n): ?>
                <a href="<?= $n['link'] ?>" class="dropdown-item p-3 border-bottom d-flex gap-12 notice-card-clickable" 
                   style="white-space: normal;"
                   data-real-id="<?= $n['real_id'] ?? '' ?>"
                   data-title="<?= htmlspecialchars($n['title']) ?>"
                   data-content="<?= htmlspecialchars($n['body']) ?>"
                   data-author="System"
                   data-date="<?= date('M d, Y', strtotime($n['time'])) ?>">
                  <div class="btn-lms btn-sm p-0 d-flex align-center justify-center flex-shrink-0" 
                       style="width:35px;height:35px;border-radius:10px;background:<?= ($n['is_read'] ?? false) ? '#f1f5f9' : 'var(--primary-light)' ?>;color:<?= ($n['is_read'] ?? false) ? '#94a3b8' : 'var(--primary)' ?>;">
                    <i class="fas <?= $n['icon'] ?>"></i>
                  </div>
                  <div style="opacity: <?= ($n['is_read'] ?? false) ? '0.6' : '1' ?>;">
                    <div class="fw-700 text-main d-flex align-items-center" style="font-size:12.5px;line-height:1.2;margin-bottom:3px;">
                        <?= htmlspecialchars($n['title']) ?>
                        <?php if ($n['is_read'] ?? false): ?>
                            <span class="ms-2 badge bg-light text-muted border" style="font-size:9px; font-weight:400;">Read</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted" style="font-size:11.5px;line-height:1.4;"><?= htmlspecialchars($n['body']) ?></div>
                    <div style="font-size:10px;color:#aaa;margin-top:6px;"><i class="fas fa-clock me-1"></i><?= timeAgo($n['time']) ?></div>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php if ($role === 'admin'): ?>
            <a href="<?= BASE_URL ?>/frontend/admin/notices.php" class="dropdown-item text-center p-2 fw-600 text-primary" style="font-size:12px;background:#f8f9fa;">View All Announcements</a>
          <?php endif; ?>
        </div>
      </div>
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
    <?= htmlspecialchars($flash['message']) ?>
  </div>
</div>
<?php endif; ?>
