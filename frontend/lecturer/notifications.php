<?php
// =====================================================
// ISSD Management - Lecturer: Notifications History
// frontend/lecturer/notifications.php
// =====================================================
define('PAGE_TITLE', 'Notification History');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/notification_controller.php';

requireRole(ROLE_LECTURER);

$user = currentUser();
$userId = currentUserId();
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';

// Lecturers use the 'L' prefix in the notifications table
$notifications = getRecentNotifications($pdo, 'L' . $userId, 'lecturer', $category, 50, false, true);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1><i class="fas fa-history text-primary me-2"></i>Notification History</h1>
      <p class="text-muted">Review all your previous alerts and system updates.</p>
    </div>
    <div class="page-header-right">
      <div class="d-flex gap-2">
        <button class="btn-lms btn-outline-danger me-2" onclick="clearRead()">
          <i class="fas fa-trash-can me-1"></i> Clear All Read
        </button>
        <a href="?category=all" class="btn-lms <?= $category === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
        <a href="?category=payment" class="btn-lms <?= $category === 'payment' ? 'btn-primary' : 'btn-outline' ?>">Payments</a>
        <a href="?category=system" class="btn-lms <?= $category === 'system' ? 'btn-primary' : 'btn-outline' ?>">System</a>
      </div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-body p-0">
      <div class="table-responsive">
        <table class="table-lms">
          <thead>
            <tr>
              <th style="width: 50px;"></th>
              <th>Notification</th>
              <th>Category</th>
              <th>Date & Time</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($notifications)): ?>
              <tr>
                <td colspan="6" class="p-5 text-center text-muted">
                  <i class="fas fa-bell-slash d-block mb-3" style="font-size: 40px; opacity: 0.2;"></i>
                  No notifications found in this category.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($notifications as $n): 
                $catColors = [
                    'payment' => ['bg' => 'rgba(34, 197, 94, 0.1)', 'text' => '#4ade80'],
                    'system' => ['bg' => 'rgba(124, 58, 237, 0.1)', 'text' => '#a78bfa'],
                    'enrollment' => ['bg' => 'rgba(59, 130, 246, 0.1)', 'text' => '#60a5fa']
                ];
                $c = $catColors[$n['type']] ?? ['bg' => 'rgba(255,255,255,0.05)', 'text' => '#94a3b8'];
              ?>
                <tr class="<?= !$n['is_read'] ? 'fw-bold' : '' ?>" style="background: <?= !$n['is_read'] ? 'rgba(34, 211, 238, 0.03)' : 'transparent' ?>;">
                  <td>
                    <div class="d-flex align-items-center justify-content-center" 
                         style="width:40px;height:40px;border-radius:12px;background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>;font-size:16px;">
                      <i class="fas <?= $n['icon'] ?>"></i>
                    </div>
                  </td>
                  <td>
                    <div class="text-main" style="font-size: 14px;"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($n['body']) ?></div>
                  </td>
                  <td><span class="dark-badge <?= $n['type'] === 'payment' ? 'db-green' : ($n['type'] === 'enrollment' ? 'db-blue' : 'db-purple') ?>"><?= ucfirst($n['type']) ?></span></td>
                  <td class="text-muted" style="font-size: 13px;"><?= date('M d, Y h:i A', strtotime($n['time'])) ?></td>
                  <td>
                    <span class="dark-badge <?= $n['is_read'] ? 'db-gray' : 'db-red' ?>">
                      <?= $n['is_read'] ? 'READ' : 'NEW' ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <?php if (!$n['is_read']): ?>
                        <button class="btn-lms btn-sm btn-outline" onclick="markRead(<?= $n['id'] ?>)">Mark Read</button>
                      <?php endif; ?>
                      <a href="<?= $n['link'] ?>" class="btn-lms btn-sm btn-primary">View</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function markRead(id) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', '<?= csrfToken() ?>');
    fetch('<?= BASE_URL ?>/api/notifications.php?action=read', {
        method: 'POST',
        body: formData
    }).then(() => window.location.reload());
}

function clearRead() {
    if (!confirm('Are you sure you want to clear all read notifications from history?')) return;
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrfToken() ?>');
    fetch('<?= BASE_URL ?>/api/notifications.php?action=clear', {
        method: 'POST',
        body: formData
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) window.location.reload();
    });
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
