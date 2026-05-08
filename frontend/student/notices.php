<?php
// =====================================================
// ISSD Management - Student: Notices (Premium UI)
// =====================================================
define('PAGE_TITLE', 'Campus Notices');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);

$search = trim($_GET['q'] ?? '');
$user = currentUser();
$userId = currentUserId();
$sql = "SELECT n.*, u.name AS posted_by_name,
               (SELECT COUNT(*) FROM read_notices rn WHERE rn.notice_id = n.id AND rn.user_id = ?) as is_read
        FROM notices n 
        JOIN users u ON u.id = n.posted_by
        WHERE n.target_role IN ('all', 'student')";
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

<div id="page-content" style="background: transparent; box-shadow: none;">
  <div class="dark-layout-wrapper">
    
    <div style="margin-bottom:40px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:20px;">
      <div>
        <h1 style="font-size:28px; font-weight:800; color:inherit; margin:0 0 8px 0; letter-spacing:-0.5px;">Campus Announcements</h1>
        <p style="color:#94a3b8; margin:0; font-size:15px;">Stay informed with official updates from ISSD.</p>
      </div>
      <form method="GET">
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:8px 16px; display:flex; align-items:center; gap:12px; width:300px;">
          <i class="fas fa-search" style="color:#94a3b8;"></i>
          <input type="text" name="q" placeholder="Search notices..." value="<?= htmlspecialchars($search) ?>" style="border:none; outline:none; width:100%; font-size:14px; background:transparent; color:inherit;">
        </div>
      </form>
    </div>

    <div style="display:flex; flex-direction:column; gap:20px;">
      <?php if(empty($notices)): ?>
        <div class="glass-card" style="text-align:center; padding:60px;">
          <div style="width:64px; height:64px; border-radius:16px; background:rgba(255,255,255,0.05); color:#94a3b8; margin:0 auto 16px; font-size:24px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-bell-slash"></i>
          </div>
          <h3 style="font-weight:700; font-size:18px; color:inherit; margin-bottom:8px;">No notices found</h3>
          <p style="color:#94a3b8; font-size:14px; margin:0;">We couldn't find any announcements matching your criteria.</p>
        </div>
      <?php else: ?>
        <?php foreach($notices as $n): 
            $isUrgent = stripos($n['title'], 'urgent') !== false || stripos($n['content'], 'urgent') !== false;
        ?>
        <div class="glass-card notice-card-clickable <?= $n['is_read'] ? 'is-read' : '' ?>" 
             style="cursor:pointer; <?= $n['is_read'] ? 'opacity:0.6;' : '' ?>"
             data-real-id="<?= $n['id'] ?>"
             data-title="<?= htmlspecialchars($n['title']) ?>"
             data-content="<?= htmlspecialchars($n['content']) ?>"
             data-author="<?= htmlspecialchars($n['posted_by_name']) ?>"
             data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>">
          
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; font-size:12px; font-weight:600;">
            <span class="dark-badge db-blue"><?= date('M d, Y', strtotime($n['created_at'])) ?></span>
            <?php if($n['is_read']): ?>
              <span class="dark-badge db-green" style="background:rgba(34,197,94,0.1); color:#4ade80;"><i class="fas fa-check-circle"></i> READ</span>
            <?php else: ?>
              <span class="unread-indicator" title="New Notice"></span>
              <span class="dark-badge db-red" style="background:rgba(239,68,68,0.1); color:#fca5a5;">NEW</span>
            <?php endif; ?>
            <span style="color:#94a3b8;">Posted by <?= htmlspecialchars($n['posted_by_name']) ?></span>
            <?php if($isUrgent): ?>
              <span class="dark-badge db-red"><i class="fas fa-exclamation-circle"></i> URGENT</span>
            <?php endif; ?>
          </div>
          
          <h2 style="font-size:20px; font-weight:700; color:inherit; margin:0 0 12px 0; line-height:1.4;"><?= htmlspecialchars($n['title']) ?></h2>
          <p style="font-size:15px; color:#94a3b8; line-height:1.6; margin:0;"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
          
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
