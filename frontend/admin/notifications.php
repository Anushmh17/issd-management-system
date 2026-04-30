<?php
// =====================================================
// ISSD Management - Admin: Notifications History
// frontend/admin/notifications.php
// =====================================================
define('PAGE_TITLE', 'Notification History');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/notification_controller.php';

requireRole(ROLE_ADMIN);

$user = currentUser();
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';

// Debug check (temporary)
// echo "<!-- Current Category: $category -->";

$notifications = getRecentNotifications($pdo, $user['id'], 'admin', $category, 50);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<style>
  .notif-history-row {
    transition: all 0.2s;
  }
  .notif-history-row:hover {
    background: #f1f5f9 !important;
    transform: scale(1.002);
  }
  .category-pill {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
  }
  .cat-call { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
  .cat-payment { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
  .cat-system { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
</style>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1><i class="fas fa-history text-primary me-2"></i>Notification History</h1>
      <p class="text-muted">Manage and review all your alerts and reminders.</p>
    </div>
    <div class="page-header-right">
      <div class="d-flex gap-2">
        <button class="btn-lms btn-outline-danger me-2" onclick="clearRead()">
          <i class="fas fa-trash-can me-1"></i> Clear All Read
        </button>
        <a href="<?= BASE_URL ?>/frontend/admin/notifications.php?category=all" class="btn-lms <?= $category === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
        <a href="<?= BASE_URL ?>/frontend/admin/notifications.php?category=call" class="btn-lms <?= $category === 'call' ? 'btn-primary' : 'btn-outline' ?>">Calls</a>
        <a href="<?= BASE_URL ?>/frontend/admin/notifications.php?category=payment" class="btn-lms <?= $category === 'payment' ? 'btn-primary' : 'btn-outline' ?>">Payments</a>
        <a href="<?= BASE_URL ?>/frontend/admin/notifications.php?category=system" class="btn-lms <?= $category === 'system' ? 'btn-primary' : 'btn-outline' ?>">System</a>
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
                    'call' => ['bg' => '#fff1f2', 'text' => '#e11d48'],
                    'payment' => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                    'system' => ['bg' => '#f5f3ff', 'text' => '#7c3aed'],
                    'enrollment' => ['bg' => '#eff6ff', 'text' => '#2563eb']
                ];
                $c = $catColors[$n['type']] ?? ['bg' => '#f1f5f9', 'text' => '#64748b'];
              ?>
                <tr class="notif-history-row <?= !$n['is_read'] ? 'fw-bold' : '' ?>" style="background: <?= !$n['is_read'] ? '#f8faff' : 'transparent' ?>;">
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
                  <td><span class="category-pill cat-<?= $n['type'] ?>"><?= ucfirst($n['type']) ?></span></td>
                  <td class="text-muted" style="font-size: 13px;"><?= date('M d, Y h:i A', strtotime($n['time'])) ?></td>
                  <td>
                    <span class="badge-lms <?= $n['is_read'] ? 'secondary' : 'warning' ?>" style="font-size: 10px;">
                      <?= $n['is_read'] ? 'READ' : 'NEW' ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!$n['is_read']): ?>
                      <button class="btn-lms btn-sm btn-outline" onclick="markRead(<?= $n['id'] ?>)">Mark as Read</button>
                    <?php endif; ?>
                    <a href="<?= $n['link'] ?>" class="btn-lms btn-sm btn-primary">View</a>
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
    fetch('<?= BASE_URL ?>/api/notifications.php?action=read', {
        method: 'POST',
        body: formData
    }).then(() => window.location.reload());
}

function clearRead() {
    if (!confirm('Are you sure you want to delete all read notifications?')) return;
    fetch('<?= BASE_URL ?>/api/notifications.php?action=clear')
        .then(resp => resp.json())
        .then(data => {
            if (data.success) window.location.reload();
        });
}
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

