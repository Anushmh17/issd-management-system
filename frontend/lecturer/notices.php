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
$userId = currentUserId();
$sql = "SELECT n.*, u.name AS posted_by_name,
               (SELECT COUNT(*) FROM read_notices rn WHERE rn.notice_id = n.id AND rn.user_id = ?) as is_read
        FROM notices n 
        JOIN users u ON u.id = n.posted_by
        WHERE n.target_role IN ('all', 'lecturer')";
$params = [$userId];

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
        <div class="card-lms-title"><i class="fas fa-bullhorn"></i> General Notices</div>
        <div class="header-actions">
            <form method="GET" class="premium-search-form">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Search announcements..." value="<?= htmlspecialchars($search) ?>">
                    <?php if($search): ?>
                        <a href="notices.php" class="search-clear"><i class="fas fa-times-circle"></i></a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn-search-premium">Search</button>
            </form>
        </div>
    </div>
    <div class="card-lms-body">
        <?php if(empty($notices)): ?>
            <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notices available.</p></div>
        <?php else: ?>
            <div class="notice-grid-premium">
                <?php foreach($notices as $n): ?>
                <div class="notice-premium-card <?= $n['is_read'] ? 'is-read' : 'is-unread' ?>" 
                     data-id="<?= $n['id'] ?>"
                     data-is-read="<?= $n['is_read'] ?>"
                     data-title="<?= htmlspecialchars($n['title']) ?>"
                     data-content="<?= htmlspecialchars($n['content']) ?>"
                     data-author="<?= htmlspecialchars($n['posted_by_name']) ?>"
                     data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>">
                    <div class="notice-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="notice-card-title"><?= htmlspecialchars($n['title']) ?></h4>
                            <?php if($n['is_read']): ?>
                                <span class="badge-lms success outline" style="font-size:9px; padding:2px 8px; border-radius:100px; font-weight:800; letter-spacing:0.5px;">READ</span>
                            <?php else: ?>
                                <span class="unread-indicator" title="New Notice"></span>
                            <?php endif; ?>
                        </div>
                        <div class="notice-card-date">
                            <i class="fas fa-calendar-alt"></i> <?= date('M d, Y', strtotime($n['created_at'])) ?>
                            <span class="notice-card-time"><?= date('h:i A', strtotime($n['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="notice-card-body">
                        <?= nl2br(htmlspecialchars($n['content'])) ?>
                    </div>
                    <div class="notice-card-footer">
                        <div class="notice-author">
                            <div class="author-avatar"><?= strtoupper(substr($n['posted_by_name'],0,1)) ?></div>
                            <div class="author-info">
                                <span class="author-label">Posted by</span>
                                <span class="author-name"><?= htmlspecialchars($n['posted_by_name']) ?></span>
                            </div>
                        </div>
                        <div class="notice-tags">
                            <span class="badge-lms info"><?= ucfirst($n['target_role']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div><!-- /#page-content -->

1'; ?>

