<?php
// =====================================================
// LEARN Management - Admin: Certificates
// admin/certificates/index.php
// =====================================================
define('PAGE_TITLE', 'Certificates');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/certificate_controller.php';

requireRole(ROLE_ADMIN);

// Handle Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'toggle') {
    $cId = (int)($_POST['id'] ?? 0);
    if (toggleCertificateProvided($pdo, $cId)) {
        setFlash('success', 'Status updated.');
    } else {
        setFlash('danger', 'Failed to update status.');
    }
    header('Location: index.php'); exit;
}

$search = trim($_GET['search'] ?? '');
$provided = trim($_GET['is_provided'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'provided');
$result = getCertificatesList($pdo, $filters, $page, 15);
$certs = $result['certs'];
$total = $result['total'];
$pages = $result['pages'];

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Certificates & Completions</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Certificates</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad">
      <i class="fas fa-certificate"></i> Process Completion
    </a>
  </div>

  <div class="card-lms">
    <div class="card-lms-header students-filter-bar">
      <div class="card-lms-title">
        <i class="fas fa-award" style="color:#f59e0b;"></i> Completed Students
        <span class="badge-lms info" style="margin-left:6px;"><?= $total ?></span>
      </div>
      <form method="GET" class="students-filters">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Search ID or Cert #" value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="is_provided" class="form-control-lms filter-select" onchange="this.form.submit()">
          <option value="">Provided: All</option>
          <option value="yes" <?= $provided==='yes'?'selected':'' ?>>Yes (Delivered)</option>
          <option value="no"  <?= $provided==='no'?'selected':'' ?>>No (Pending)</option>
        </select>
        <button type="submit" style="display:none;"></button>
        <?php if ($search || $provided): ?>
          <a href="index.php" class="btn-lms btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($certs)): ?>
        <div class="empty-state">
          <i class="fas fa-user-graduate" style="color:#cbd5e1;"></i>
          <p>No completed students found.</p>
        </div>
      <?php else: ?>
        <table class="table-lms">
          <thead>
            <tr>
              <th>ID/Student</th>
              <th>Certificate #</th>
              <th>Issue Date</th>
              <th>Intern Document</th>
              <th>Provided?</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($certs as $c): ?>
            <tr>
              <td>
                <div class="fw-600"><?= htmlspecialchars($c['full_name']) ?></div>
                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($c['student_reg']) ?></div>
              </td>
              <td>
                <span class="badge-lms" style="background:#f1f5f9;color:#334155;font-family:monospace;letter-spacing:0.5px;">
                  <?= htmlspecialchars($c['certificate_number']) ?>
                </span>
              </td>
              <td><?= date('d M Y', strtotime($c['issue_date'])) ?></td>
              <td>
                <?php if ($c['intern_document']): ?>
                  <a href="<?= INTERN_DOCS_URL . $c['intern_document'] ?>" target="_blank" class="btn-lms btn-outline btn-sm">
                    <i class="fas fa-file-pdf"></i> View Doc
                  </a>
                <?php else: ?>
                  <span style="font-size:12px;color:#94a3b8;">None</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['is_provided'] === 'yes'): ?>
                  <span class="badge-lms" style="background:#d1fae5;color:#059669;"><i class="fas fa-check"></i> Yes</span>
                <?php else: ?>
                  <span class="badge-lms" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-times"></i> No</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <form method="POST" style="display:inline-block;">
                  <input type="hidden" name="act" value="toggle">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-lms btn-primary btn-sm" title="Toggle Provided Status">
                    <i class="fas fa-toggle-on"></i> Toggle
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div class="pagination-lms">
          <div class="pagination-info">
            Showing <?= (($page-1)*15)+1 ?>–<?= min($page*15,$total) ?> of <?= $total ?> records
          </div>
          <div class="pagination-controls">
            <?php if ($page>1): ?>
              <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page-1])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($p_nav=max(1,$page-2); $p_nav<=min($pages,$page+2); $p_nav++): ?>
              <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$p_nav])) ?>" class="page-btn <?= $p_nav===$page?'active':'' ?>"><?= $p_nav ?></a>
            <?php endfor; ?>
            <?php if ($page<$pages): ?>
              <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page+1])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
