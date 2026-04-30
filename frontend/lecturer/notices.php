<?php
// =====================================================
// ISSD Management - Lecturer: Notices
// =====================================================
define('PAGE_TITLE', 'Notices');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_LECTURER);

$search = trim($_GET['q'] ?? '');
$sql = "SELECT n.*, u.name AS posted_by_name 
        FROM notices n 
        JOIN users u ON u.id = n.posted_by
        WHERE n.target_role IN ('all', 'lecturer')";
$params = [];

if ($search) {
    $sql .= " AND (n.title LIKE ? OR n.content LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " ORDER BY n.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notices = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Announcements & Notices</h1>
      <div class="breadcrumb-custom"><i class="fas fa-home"></i> Lecturer &rsaquo; <span>Notices</span></div>
    </div>
  </div>

  <div class="card-lms">
    <div class="card-lms-header">
        <div class="card-lms-title"><i class="fas fa-bell"></i> General Notices</div>
        <form method="GET" class="header-filter-form">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Search notices..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-lms btn-primary btn-sm">Search</button>
        </form>
    </div>
    <div class="card-lms-body">
        <?php if(empty($notices)): ?>
            <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notices available.</p></div>
        <?php else: ?>
            <div style="display:grid;gap:16px;">
                <?php foreach($notices as $n): ?>
                <div class="notice-card-clickable" 
                     data-real-id="<?= $n['id'] ?>"
                     data-title="<?= htmlspecialchars($n['title']) ?>"
                     data-content="<?= htmlspecialchars($n['content']) ?>"
                     data-author="<?= htmlspecialchars($n['posted_by_name']) ?>"
                     data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>"
                     style="background:var(--bg-page);border-radius:12px;border:1px solid var(--border-color);padding:20px;border-left:4px solid var(--primary);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                        <h4 style="margin:0;font-size:16px;font-weight:700;color:var(--text-main);"><?= htmlspecialchars($n['title']) ?></h4>
                        <span class="text-muted" style="font-size:12px;"><i class="fas fa-clock"></i> <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                    </div>
                    <div style="font-size:14px;color:var(--text-main);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars($n['content']) ?></div>
                    <div style="margin-top:16px;font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-user-circle"></i> Posted by: <strong><?= htmlspecialchars($n['posted_by_name']) ?></strong>
                        <span class="badge-lms secondary" style="margin-left:auto;"><?= ucfirst($n['target_role']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

