<?php
// =====================================================
// ISSD Management - Admin: Certificates
// admin/certificates/index.php
// =====================================================
define('PAGE_TITLE', 'Certificates');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/certificate_controller.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['act'] ?? '') === 'toggle') {
        $cId = (int)($_POST['id'] ?? 0);
        if (toggleCertificateProvided($pdo, $cId)) {
            setFlash('success', 'Status updated.');
        } else {
            setFlash('danger', 'Failed to update status.');
        }
        header('Location: index.php'); exit;
    }
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

<style>
  /* --- PREMIUM BORDER REINFORCEMENT (FORCE APPLIED) --- */
  body.lms-dark-mode .card-lms,
  body.lms-dark-mode .stat-card,
  body.lms-dark-mode .bento-card {
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4), 0 0 15px rgba(255, 255, 255, 0.03) !important;
    border-radius: 24px !important;
    background: rgba(30, 41, 59, 0.5) !important;
    backdrop-filter: blur(20px) !important;
  }
</style>

<style>
/* Mobile Responsive Adjustments */
@media (max-width: 768px) {
  #page-content { padding: 15px 15px !important; }
  .page-header { padding: 0 !important; flex-direction: column; align-items: flex-start; gap: 12px; }
  .page-header h1 { font-size: 20px !important; }
  .btn-primary-grad { width: 100% !important; justify-content: center !important; height: 42px !important; display: flex !important; align-items: center !important; }
  
  .card-lms { border-radius: 18px !important; }
  .card-lms-header { padding: 20px !important; gap: 15px !important; }
  .list-legend-title { font-size: 18px !important; }
  .list-legend-label { font-size: 8px !important; }
  .count-badge { font-size: 11px !important; padding: 2px 8px !important; }

  .search-bar { min-width: 100% !important; }
  .search-bar input { padding: 8px 0 !important; font-size: 13px !important; }
  .students-filters { flex-direction: column !important; align-items: stretch !important; gap: 10px !important; }
  .filter-select { flex: 1 !important; height: 38px !important; font-size: 12px !important; min-width: 0 !important; }
  .btn-primary-grad.btn-sm { height: 38px !important; padding: 0 20px !important; font-size: 13px !important; width: 100% !important; }

  .card-lms-body { padding: 0 !important; overflow-x: auto !important; }
  .table-lms { 
    table-layout: fixed !important; 
    width: 100% !important; 
    margin: 0 !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    border: none !important;
  }
  .table-lms th, .table-lms td { 
    padding: 12px 10px !important; 
    border-radius: 0 !important; 
    border-left: none !important; 
    border-right: none !important; 
  }

  /* Column Visibility */
  .table-lms th:nth-child(2), .table-lms td:nth-child(2),
  .table-lms th:nth-child(3), .table-lms td:nth-child(3),
  .table-lms th:nth-child(4), .table-lms td:nth-child(4) { display: none !important; }

  .table-lms th:nth-child(1), .table-lms td:nth-child(1) { width: auto !important; }
  .table-lms th:nth-child(5), .table-lms td:nth-child(5) { width: 80px !important; text-align: center !important; }
  .table-lms th:nth-child(6), .table-lms td:nth-child(6) { width: 70px !important; text-align: center !important; padding-right: 15px !important; }

  .badge-lms { font-size: 9px !important; padding: 3px 6px !important; }
}
</style>

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
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Certification Management</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>Completed Students</span>
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 300px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search by ID or Cert #..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="is_provided" class="form-control-lms filter-select"
                  style="min-width: 180px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="this.form.submit()">
            <option value="">Status: All</option>
            <option value="yes" <?= $provided==='yes'?'selected':'' ?>>Yes (Delivered)</option>
            <option value="no"  <?= $provided==='no'?'selected':'' ?>>No (Pending)</option>
          </select>
        </div>

        <div class="filter-actions d-flex gap-2">
          <button type="submit" class="btn-primary-grad btn-sm px-4" style="height: 40px;">
            <i class="fas fa-filter me-2"></i> Filter
          </button>
          <?php if ($search || $provided): ?>
            <a href="index.php" class="btn-lms btn-outline px-3 rounded-3 d-flex align-items-center justify-content-center" style="height: 46px; width: 46px;" title="Clear Filters">
              <i class="fas fa-xmark"></i>
            </a>
          <?php endif; ?>
        </div>
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
                <span class="badge-lms" style="font-family:monospace;letter-spacing:0.5px;">
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
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="act" value="toggle">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-primary-grad btn-sm" title="Toggle Provided Status">
                    <i class="fas fa-toggle-on me-1"></i> Toggle
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
            Showing <?= (($page-1)*15)+1 ?>""<?= min($page*15,$total) ?> of <?= $total ?> records
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
</div><!-- /#page-content -->

1'; ?>

