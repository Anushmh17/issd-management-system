<?php
// =====================================================
// ISSD Management - Admin: Notices Management (Enhanced)
// =====================================================
define('PAGE_TITLE', 'Manage Notices');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

$action = $_GET['action'] ?? 'list';
$error = '';
$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $target_role = $_POST['target_role'] ?? 'all';

        if (!$title || !$content) {
            $error = 'Title and Content are required.';
        } else {
            try {
                if ($act === 'add') {
                    $pdo->prepare("INSERT INTO notices (title, content, target_role, posted_by) VALUES (?, ?, ?, ?)")
                        ->execute([$title, $content, $target_role, $userId]);
                    setFlash('success', 'Notice published successfully.');
                } else {
                    $id = (int)$_POST['id'];
                    $pdo->prepare("UPDATE notices SET title=?, content=?, target_role=? WHERE id=?")
                        ->execute([$title, $content, $target_role, $id]);
                    setFlash('success', 'Notice updated successfully.');
                }
                header('Location: notices.php'); exit;
            } catch (PDOException $e) {
                $error = 'Failed to save notice. Error: ' . $e->getMessage();
            }
        }
    }

    if ($act === 'delete') {
        $pdo->prepare("DELETE FROM notices WHERE id=?")->execute([(int)$_POST['id']]);
        setFlash('success', 'Notice removed successfully.');
        header('Location: notices.php'); exit;
    }
}

$editNotice = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editNotice = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT n.*, u.name AS posted_by_name,
               (SELECT COUNT(*) FROM read_notices rn WHERE rn.notice_id = n.id AND rn.user_id LIKE 'L%') as lecturer_read_count,
               (SELECT COUNT(*) FROM read_notices rn WHERE rn.notice_id = n.id AND rn.user_id NOT LIKE 'L%') as student_read_count,
               (SELECT COUNT(*) FROM read_notices rn WHERE rn.notice_id = n.id) as read_count,
               (CASE 
                   WHEN n.target_role = 'all' THEN (SELECT COUNT(*) FROM users WHERE role != 'admin')
                   ELSE (SELECT COUNT(*) FROM users u2 WHERE u2.role = n.target_role)
               END) as total_target
        FROM notices n 
        JOIN users u ON u.id = n.posted_by";
$params = [];
if ($search) {
    $sql .= " WHERE n.title LIKE ? OR n.content LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notices = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<style>
  :root {
    --admin-notice-radius: var(--radius-lg);
    --admin-notice-shadow: var(--shadow-sm);
  }

  /* Admin Hero Section */
  .admin-notices-hero {
    background: var(--grad-primary);
    border-radius: var(--admin-notice-radius);
    padding: 40px;
    margin-bottom: 32px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-premium);
  }
  .admin-notices-hero::before {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M0 38.59V40h1.41l37.18-37.18V1.41L1.41 38.59H0zm0-2.82V37.18l35.77-35.77V0h-1.41L0 34.36v1.41zM0 31.54V32.95l32.95-32.95V0h-1.41L0 31.54zM0 28.71V30.12l30.12-30.12V0h-1.41L0 28.71zM0 25.89V27.3L27.3-27.3V0h-1.41L0 25.89zM0 23.06V24.47L24.47-24.47V0h-1.41L0 23.06zM0 20.24V21.65L21.65-21.65V0h-1.41L0 20.24z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.8;
  }
  .hero-content { position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
  .hero-text h1 { font-size: 32px; font-weight: 800; margin-bottom: 6px; font-family: 'Poppins', sans-serif; letter-spacing: -0.5px; }
  .hero-text p { font-size: 14.5px; opacity: 0.9; margin: 0; }

  /* Premium Form */
  .notice-form-card {
    border: 1.5px solid var(--border-color);
    border-radius: var(--admin-notice-radius);
    padding: 32px;
    margin-bottom: 32px;
  }
  .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light); }
  .form-header h3 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px; font-family: 'Poppins', sans-serif; }

  /* Table Styles */
  .premium-table-card {
    border: 1.5px solid var(--border-color);
    border-radius: var(--admin-notice-radius);
    overflow: hidden;
  }
  .table-header { padding: 24px 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; border-bottom: 1px solid var(--border-light); }
  .table-header h3 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px; }

  .notice-table { width: 100%; border-collapse: separate; border-spacing: 0; }
  .notice-table th { padding: 14px 24px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border-light); }
  .notice-table td { padding: 18px 24px; font-size: 13.5px; vertical-align: middle; border-bottom: 1px solid var(--border-light); }
  .notice-table tr:last-child td { border-bottom: none; }
  .notice-table tr:hover td { }

  .title-cell { display: flex; flex-direction: column; gap: 4px; }
  .title-cell strong { font-size: 15px; color: #0f172a; }
  .title-cell small { font-size: 12px; color: #64748b; font-weight: 500; display: block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

  /* Audience Badge */
  .aud-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
  .aud-all { background: var(--primary-light); color: var(--primary); }
  .aud-student { background: var(--accent-light); color: var(--accent-dark); }
  .aud-lecturer { background: #fffbeb; color: #d97706; }
  .aud-admin { background: #fef2f2; color: #dc2626; }

  /* Dark Mode Badges */
  body.lms-dark-mode .aud-all { background: rgba(34, 211, 238, 0.15) !important; color: #22d3ee !important; }
  body.lms-dark-mode .aud-student { background: rgba(52, 211, 153, 0.15) !important; color: #34d399 !important; }
  body.lms-dark-mode .aud-lecturer { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }
  body.lms-dark-mode .aud-admin { background: rgba(239, 68, 68, 0.15) !important; color: #f87171 !important; }

  .action-btns { display: flex; gap: 8px; }
  .action-btn { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all 0.3s; border: 1.5px solid var(--border-color); background: transparent; }
  
  .action-btn.view { color: var(--primary); border-color: rgba(30, 77, 77, 0.2); }
  .action-btn.edit { color: var(--warning); border-color: rgba(245, 158, 11, 0.2); }
  .action-btn.del { color: var(--danger); border-color: rgba(239, 68, 68, 0.2); }

  .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  .action-btn.view:hover { background: var(--primary-light); border-color: var(--primary); }
  .action-btn.edit:hover { background: var(--warning-light); border-color: var(--warning); }
  .action-btn.del:hover { background: var(--danger-light); border-color: var(--danger); }

  /* Dark Mode Overrides */
  body.lms-dark-mode .title-cell strong { color: #fff !important; }
  body.lms-dark-mode .title-cell small { color: #94a3b8 !important; }
  body.lms-dark-mode .table-header h3 { color: #fff !important; }
  body.lms-dark-mode .notice-table th { color: #94a3b8 !important; border-bottom-color: rgba(255,255,255,0.05); }
  body.lms-dark-mode .notice-table td { border-bottom-color: rgba(255,255,255,0.05); color: #e2e8f0; }
  body.lms-dark-mode .notice-table td span { color: #94a3b8 !important; }
  body.lms-dark-mode .notice-table .fw-600 { color: #e2e8f0 !important; }
  
  body.lms-dark-mode .action-btn { border-color: rgba(255,255,255,0.15); }
  body.lms-dark-mode .action-btn.view { color: #22d3ee !important; border-color: rgba(34, 211, 238, 0.3); }
  body.lms-dark-mode .action-btn.edit { color: #fbbf24 !important; border-color: rgba(251, 191, 36, 0.3); }
  body.lms-dark-mode .action-btn.del { color: #f87171 !important; border-color: rgba(248, 113, 113, 0.3); }
  
  body.lms-dark-mode .action-btn i { color: inherit !important; }
  body.lms-dark-mode .action-btn:hover { background: rgba(255,255,255,0.08); border-color: currentColor; }



  @media (max-width: 768px) {
    .hero-content { flex-direction: column; align-items: flex-start; }
    .table-header { flex-direction: column; align-items: stretch; }
    .premium-search-box { max-width: none; }
  }
</style>

<div id="page-content">
  
  <!-- Hero Section -->
  <div class="admin-notices-hero">
    <div class="hero-content">
      <div class="hero-text">
        <div class="breadcrumb-custom mb-2" style="color:rgba(255,255,255,0.8);"><i class="fas fa-home"></i> Admin &rsaquo; <span>Communication</span></div>
        <h1>Campus Announcements</h1>
        <p>Post and manage official updates for students, lecturers, and staff.</p>
      </div>
      <?php if ($action === 'list'): ?>
      <a href="?action=add" class="btn btn-light rounded-pill px-4 fw-800" style="color:var(--primary); height:48px; display:flex; align-items:center;">
        <i class="fas fa-plus-circle me-2"></i> Create New Notice
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert-lms danger auto-dismiss mb-20" style="border-radius:16px;">
      <i class="fas fa-times-circle me-2"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Add/Edit Form -->
  <?php if ($action === 'add' || $action === 'edit'): ?>
  <div class="notice-form-card">
    <div class="form-header">
      <h3><i class="fas <?= $action==='add'?'fa-plus-circle':'fa-edit' ?>"></i> <?= $action==='add'?'Compose New Notice':'Edit Notice Content' ?></h3>
      <a href="notices.php" class="text-muted fw-800 text-decoration-none" style="font-size:12px;"><i class="fas fa-times me-1"></i> DISCARD</a>
    </div>
    
    <form method="POST" action="notices.php">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="act" value="<?= $action ?>">
      <?php if ($action==='edit'): ?><input type="hidden" name="id" value="<?= $editNotice['id'] ?>"><?php endif; ?>

      <div class="row g-4">
        <div class="col-md-8">
          <div class="form-group-lms">
            <label class="fw-800 text-uppercase mb-2" style="font-size:11px; letter-spacing:1px; color:#64748b;">Notice Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control-lms" placeholder="e.g. System Maintenance Scheduled" value="<?= htmlspecialchars($editNotice['title']??'') ?>" required style="height:50px; font-size:15px; font-weight:600; border-radius:12px;">
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="form-group-lms">
            <label class="fw-800 text-uppercase mb-2" style="font-size:11px; letter-spacing:1px; color:#64748b;">Target Audience</label>
            <select name="target_role" class="form-control-lms" style="height:50px; border-radius:12px; font-weight:600;">
              <option value="all" <?= ($editNotice['target_role']??'')==='all'?'selected':'' ?>>🌍 Everyone</option>
              <option value="student" <?= ($editNotice['target_role']??'')==='student'?'selected':'' ?>>🎓 Students Only</option>
              <option value="lecturer" <?= ($editNotice['target_role']??'')==='lecturer'?'selected':'' ?>>👨‍🏫 Lecturers Only</option>
              <option value="admin" <?= ($editNotice['target_role']??'')==='admin'?'selected':'' ?>>🛡️ Admins Only</option>
            </select>
          </div>
        </div>

        <div class="col-12">
          <div class="form-group-lms">
            <label class="fw-800 text-uppercase mb-2" style="font-size:11px; letter-spacing:1px; color:#64748b;">Detailed Announcement <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control-lms" rows="8" placeholder="Type your message here..." required style="border-radius:16px; padding:20px; font-size:14.5px;"><?= htmlspecialchars($editNotice['content']??'') ?></textarea>
          </div>
        </div>
      </div>

      <div style="margin-top:24px; display:flex; gap:12px; padding-top:24px; border-top:1px solid var(--border-light);">
        <button type="submit" class="btn-primary-grad px-5 fw-800">
          <i class="fas fa-paper-plane me-2"></i> <?= $action==='add'?'Publish Announcement':'Update Announcement' ?>
        </button>
        <a href="notices.php" class="btn btn-outline-secondary rounded-pill px-4 fw-800" style="display:flex; align-items:center;">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- Notices List -->
  <div class="premium-table-card">
    <div class="table-header">
      <h3><i class="fas fa-list-ul" style="color:var(--primary);"></i> Announcement History</h3>
      
      <form method="GET" class="premium-search-box" style="display:flex; gap:10px; max-width:400px; width:100%;">
        <div style="position:relative; flex:1;">
          <i class="fas fa-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
          <input type="text" name="q" class="form-control-lms" placeholder="Filter notices..." value="<?= htmlspecialchars($search) ?>" style="padding-left:44px; height:42px; border-radius:100px;">
        </div>
        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-800" style="background:var(--primary); border:none;">Filter</button>
      </form>
    </div>

    <div style="overflow-x:auto;">
      <?php if (empty($notices)): ?>
        <div class="empty-state-bento" style="padding:60px;">
          <div class="stat-icon-circle bg-mint-soft" style="width:80px; height:80px; margin:0 auto 20px; font-size:32px;"><i class="fas fa-bell-slash"></i></div>
          <h3 class="fw-800">No announcements found</h3>
          <p style="color:#64748b; max-width:400px; margin:0 auto;">There are no active notices matching your current search criteria.</p>
        </div>
      <?php else: ?>
      <table class="notice-table">
        <thead>
          <tr>
            <th>Published At</th>
            <th>Announcement Details</th>
            <th>Audience</th>
            <th>Read Status</th>
            <th>Author</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notices as $n): ?>
          <tr>
            <td style="color:#64748b; font-weight:600; font-size:12px;">
              <?= date('M d, Y', strtotime($n['created_at'])) ?><br>
              <span style="font-weight:400; font-size:11px;"><?= date('h:i A', strtotime($n['created_at'])) ?></span>
            </td>
            <td>
              <div class="title-cell">
                <strong><?= htmlspecialchars($n['title']) ?></strong>
                <small><?= htmlspecialchars($n['content']) ?></small>
              </div>
            </td>
            <td>
              <?php
                $audClass = match($n['target_role']) {
                    'student' => 'aud-student',
                    'lecturer' => 'aud-lecturer',
                    'admin' => 'aud-admin',
                    default => 'aud-all'
                };
                $audLabel = match($n['target_role']) {
                    'student' => 'Students',
                    'lecturer' => 'Lecturers',
                    'admin' => 'Admins',
                    default => 'Everyone'
                };
              ?>
              <span class="aud-badge <?= $audClass ?>"><?= $audLabel ?></span>
            </td>
            <td>
              <div style="font-weight:700; color:#0f172a; font-size:14px; cursor:pointer;" onclick="viewNoticeReaders(<?= $n['id'] ?>, '<?= addslashes($n['title']) ?>')">
                <span class="text-primary" title="Lecturers Read"><i class="fas fa-chalkboard-user"></i> <?= $n['lecturer_read_count'] ?></span>
                <span style="color:#94a3b8; font-weight:400; margin:0 4px;">|</span>
                <span class="text-success" title="Students Read"><i class="fas fa-user-graduate"></i> <?= $n['student_read_count'] ?></span>
                <span style="color:#94a3b8; font-weight:400; margin:0 4px;">/</span>
                <span style="color:#ef4444;" title="Total Target"><i class="fas fa-bullseye"></i> <?= $n['total_target'] ?></span>
              </div>
              <div class="progress" style="height:4px; width:80px; margin-top:6px; background:#f1f5f9; border-radius:10px;">
                <div class="progress-bar bg-success" style="width: <?= ($n['total_target'] > 0) ? ($n['read_count'] / $n['total_target'] * 100) : 0 ?>%; border-radius:10px;"></div>
              </div>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="avatar-initials" style="width:28px; height:28px; font-size:11px;"><?= strtoupper(substr($n['posted_by_name'], 0, 1)) ?></div>
                <span class="fw-600" style="color:#475569;"><?= htmlspecialchars($n['posted_by_name']) ?></span>
              </div>
            </td>
            <td>
              <div class="action-btns" style="justify-content:flex-end;">
                <button type="button" class="action-btn view notice-card-clickable" title="Quick View"
                        data-real-id="<?= $n['id'] ?>"
                        data-title="<?= htmlspecialchars($n['title']) ?>"
                        data-content="<?= htmlspecialchars($n['content']) ?>"
                        data-author="<?= htmlspecialchars($n['posted_by_name']) ?>"
                        data-date="<?= date('M d, Y', strtotime($n['created_at'])) ?>">
                  <i class="fas fa-eye"></i>
                </button>
                <a href="?action=edit&id=<?= $n['id'] ?>" class="action-btn edit" title="Edit Notice"><i class="fas fa-pen"></i></a>
                <form method="POST" action="notices.php" style="display:inline;" onsubmit="return confirm('Archive this notice?')">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id" value="<?= $n['id'] ?>">
                  <button type="submit" class="action-btn del" title="Delete Permanent"><i class="fas fa-trash-alt"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

<!-- Readers List Modal -->
<div class="modal fade" id="readersListModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content lms-modal" style="border:none; border-radius:24px; overflow:hidden;">
        <div class="modal-header border-0 p-4 pb-0">
            <div>
                <h4 class="fw-800 m-0" style="font-size:20px; color:var(--text-main);">Notice Engagement</h4>
                <p id="readers-modal-subtitle" class="text-muted m-0" style="font-size:12px;">Detailed breakdown of who has read this notice.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div id="readers-loading" class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 text-muted">Fetching reader data...</p>
            </div>
            <div id="readers-content" style="display:none;">
                <div class="row g-4">
                    <!-- Lecturers Column -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 h-100">
                            <h6 class="fw-800 mb-3 d-flex align-items-center gap-2" style="color:var(--primary);">
                                <i class="fas fa-chalkboard-user"></i> Lecturers
                                <span id="lecturer-read-badge" class="badge bg-primary rounded-pill" style="font-size:10px;">0</span>
                            </h6>
                            <div id="lecturer-readers-list" class="readers-scroll-list" style="max-height:300px; overflow-y:auto;">
                                <!-- List goes here -->
                            </div>
                        </div>
                    </div>
                    <!-- Students Column -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 h-100">
                            <h6 class="fw-800 mb-3 d-flex align-items-center gap-2" style="color:var(--accent-dark);">
                                <i class="fas fa-user-graduate"></i> Students
                                <span id="student-read-badge" class="badge bg-success rounded-pill" style="font-size:10px;">0</span>
                            </h6>
                            <div id="student-readers-list" class="readers-scroll-list" style="max-height:300px; overflow-y:auto;">
                                <!-- List goes here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<style>
.reader-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.03);
}
.reader-item:last-child { border: none; }
.reader-info .r-name { font-weight: 700; font-size: 13px; color: var(--text-main); display: block; line-height: 1.2; }
.reader-info .r-time { font-size: 10px; color: var(--text-muted); }

body.lms-dark-mode .bg-light { background: rgba(255,255,255,0.02) !important; }
body.lms-dark-mode .reader-item { border-bottom-color: rgba(255,255,255,0.05); }
</style>

<script>
function viewNoticeReaders(id, title) {
    const modal = new bootstrap.Modal(document.getElementById('readersListModal'));
    document.getElementById('readers-modal-subtitle').innerText = 'Breakdown for: ' + title;
    document.getElementById('readers-loading').style.display = 'block';
    document.getElementById('readers-content').style.display = 'none';
    
    // Clear lists
    document.getElementById('lecturer-readers-list').innerHTML = '';
    document.getElementById('student-readers-list').innerHTML = '';
    
    modal.show();
    
    fetch(`../../api/notices.php?action=readers&id=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('readers-loading').style.display = 'none';
            document.getElementById('readers-content').style.display = 'block';
            
            if(data.success) {
                let lCount = 0;
                let sCount = 0;
                
                data.readers.forEach(r => {
                    const item = `
                        <div class="reader-item">
                            <div class="avatar-initials" style="width:30px; height:30px; font-size:11px;">${r.name.charAt(0).toUpperCase()}</div>
                            <div class="reader-info">
                                <span class="r-name">${r.name}</span>
                                <span class="r-time">${r.read_at}</span>
                            </div>
                        </div>
                    `;
                    
                    if(r.role === 'lecturer') {
                        document.getElementById('lecturer-readers-list').innerHTML += item;
                        lCount++;
                    } else if(r.role === 'student') {
                        document.getElementById('student-readers-list').innerHTML += item;
                        sCount++;
                    }
                });
                
                document.getElementById('lecturer-read-badge').innerText = lCount;
                document.getElementById('student-read-badge').innerText = sCount;
                
                if(lCount === 0) document.getElementById('lecturer-readers-list').innerHTML = '<p class="text-center text-muted py-3" style="font-size:12px;">No lecturers read yet.</p>';
                if(sCount === 0) document.getElementById('student-readers-list').innerHTML = '<p class="text-center text-muted py-3" style="font-size:12px;">No students read yet.</p>';
                
            } else {
                alert('Error: ' + data.message);
            }
        });
}
</script>
