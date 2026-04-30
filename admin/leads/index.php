<?php
// =====================================================
// ISSD Management - Admin: Leads List
// admin/leads/index.php
// =====================================================
define('PAGE_TITLE', 'Leads Management');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/leads_controller.php';

requireRole(ROLE_ADMIN);

// ---- Handle DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'delete') {
    $lid = (int)($_POST['id'] ?? 0);
    if (deleteLead($pdo, $lid)) {
        setFlash('success', 'Lead deleted successfully.');
    } else {
        setFlash('danger', 'Failed to delete lead.');
    }
    header('Location: index.php'); exit;
}

// ---- Filters ----
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$source = trim($_GET['source'] ?? '');
$date   = trim($_GET['date'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = compact('search', 'status', 'source', 'date');
$result  = getLeadsList($pdo, $filters, $page, 15);
$leads = $result['leads'];
$total = $result['total'];
$pages = $result['pages'];

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Leads Management</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Leads</span>
      </div>
    </div>
    <a href="add.php" class="btn-primary-grad" id="btn-add-lead">
      <i class="fas fa-user-plus"></i> Add Lead
    </a>
  </div>

  <!-- Table Card -->
  <div class="card-lms">
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend" style="align-items: flex-start; text-align: left;">
          <div class="list-legend-label">Lead Generation</div>
          <div class="list-legend-title" style="font-size: 24px;">
            <span>All Leads</span>
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= $total ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" id="filterForm" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap; width: 100%;">
        <div class="search-bar" style="flex: 1; min-width: 280px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search Name or Phone..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="status" class="form-control-lms filter-select"
                  style="min-width: 160px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="this.form.submit()">
            <option value="">Status: All</option>
            <option value="new" <?= $status==='new' ? 'selected' : '' ?>>New</option>
            <option value="talking" <?= $status==='talking' ? 'selected' : '' ?>>Talking</option>
            <option value="converted" <?= $status==='converted' ? 'selected' : '' ?>>Converted</option>
            <option value="not_interested" <?= $status==='not_interested' ? 'selected' : '' ?>>Not Interested</option>
          </select>

          <select name="source" class="form-control-lms filter-select"
                  style="min-width: 140px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="this.form.submit()">
            <option value="">Source: All</option>
            <option value="Facebook" <?= $source==='Facebook' ? 'selected' : '' ?>>Facebook</option>
            <option value="WhatsApp" <?= $source==='WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
            <option value="Walk-in" <?= $source==='Walk-in' ? 'selected' : '' ?>>Walk-in</option>
            <option value="Other" <?= $source==='Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>

        <div class="filter-actions d-flex gap-2">
          <button type="submit" class="btn-lms btn-primary px-4 rounded-3 shadow-sm" style="height: 46px; padding: 0 25px;">
            <i class="fas fa-filter me-1"></i> Filter
          </button>
          <?php if ($search || $status || $source): ?>
            <a href="index.php" class="btn-lms btn-outline px-3 rounded-3 d-flex align-items-center justify-content-center" style="height: 46px; width: 46px;" title="Clear Filters">
              <i class="fas fa-xmark"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($leads)): ?>
        <div class="empty-state">
          <i class="fas fa-users-slash"></i>
          <p>No leads found<?= ($search||$status||$source)?' matching your filters.':'.'; ?></p>
          <?php if (!$search && !$status && !$source): ?>
            <a href="add.php" class="btn-lms btn-primary mt-10">
              <i class="fas fa-user-plus"></i> Add First Lead
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="table-lms" id="leadsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Source</th>
            <th>Status</th>
            <th>Next Follow-up</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $i => $l): 
              $isHighlighted = (isset($_GET['highlight_id']) && (int)$_GET['highlight_id'] === (int)$l['id']);
              $followupTime = $l['next_followup_datetime'] ? strtotime($l['next_followup_datetime']) : null;
              $isOverdue = ($followupTime && $followupTime < time() && $l['status'] !== 'converted');
          ?>
          <tr id="row-<?= $l['id'] ?>" class="<?= $isHighlighted ? 'row-highlight' : '' ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page-1)*15)+$i+1 ?></td>
            <td>
              <div class="fw-600" style="font-size:14px;"><?= htmlspecialchars($l['name']) ?></div>
            </td>
            <td style="font-size:13px;">
              <a href="tel:<?= htmlspecialchars($l['phone']) ?>" style="text-decoration:none;color:#3b82f6;">
                <i class="fas fa-phone fa-sm"></i> <?= htmlspecialchars($l['phone']) ?>
              </a>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($l['source']) ?></td>
            <td>
              <?php if ($l['status'] === 'new'): ?>
                <span class="badge-lms" style="background:#dbeafe;color:#2563eb;">New</span>
              <?php elseif ($l['status'] === 'talking'): ?>
                <span class="badge-lms" style="background:#fef3c7;color:#d97706;">Talking</span>
              <?php elseif ($l['status'] === 'converted'): ?>
                <span class="badge-lms" style="background:#d1fae5;color:#059669;">Converted</span>
              <?php elseif ($l['status'] === 'not_interested'): ?>
                <span class="badge-lms" style="background:#fee2e2;color:#dc2626;">Not Interested</span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px; <?= $isOverdue ? 'color:#dc2626;font-weight:700;' : 'color:#64748b;' ?>">
              <?php if ($l['next_followup_datetime']): ?>
                <i class="fas <?= $isOverdue ? 'fa-exclamation-circle' : 'fa-clock' ?>"></i>
                <?= date('d M Y, h:i A', $followupTime) ?>
              <?php else: ?>
                ""
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-6" style="justify-content:center;">
                <?php if ($l['status'] !== 'converted'): ?>
                <a href="<?= BASE_URL ?>/admin/students/add.php?name=<?= urlencode($l['name']) ?>&phone=<?= urlencode($l['phone']) ?>&lead_id=<?= $l['id'] ?>" class="btn-lms btn-success btn-sm" title="Convert to Student">
                  <i class="fas fa-user-graduate"></i>
                </a>
                <?php endif; ?>
                <a href="edit.php?id=<?= $l['id'] ?>"
                   class="btn-lms btn-outline btn-sm"
                   title="Edit Lead">
                  <i class="fas fa-pen-to-square"></i>
                </a>
                <form method="POST" action="index.php" style="display:inline;">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="id"  value="<?= $l['id'] ?>">
                  <button type="submit"
                          class="btn-lms btn-danger btn-sm"
                          title="Delete"
                          data-confirm="Delete lead '<?= htmlspecialchars($l['name']) ?>'?">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($pages > 1): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page-1)*15)+1 ?>""<?= min($page*15,$total) ?> of <?= $total ?> leads
        </div>
        <div class="pagination-controls">
          <?php if ($page>1): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page-1])) ?>" class="page-btn">
              <i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($p=max(1,$page-2); $p<=min($pages,$page+2); $p++): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$p])) ?>"
               class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page<$pages): ?>
            <a href="index.php?<?= http_build_query(array_merge($filters,['page'=>$page+1])) ?>" class="page-btn">
              <i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const highlightId = urlParams.get('highlight_id');
  if (highlightId) {
    const targetRow = document.getElementById('row-' + highlightId);
    if (targetRow) {
      setTimeout(() => {
        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetRow.classList.add('highlight-row');
        setTimeout(() => targetRow.classList.remove('highlight-row'), 4500);
      }, 500);
    }
  }
});
</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

