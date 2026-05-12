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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['act'] ?? '') === 'delete') {
        $lid = (int)($_POST['id'] ?? 0);
        if (deleteLead($pdo, $lid)) {
            setFlash('success', 'Lead deleted successfully.');
        } else {
            setFlash('danger', 'Failed to delete lead.');
        }
        header('Location: index.php'); exit;
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
$extraCSS = <<<CSS
<style>
  #leadsTable th, #leadsTable td { padding: 16px 20px; }
  .lead-row { cursor: pointer; transition: background 0.2s; }
  .lead-row:hover { background: rgba(34, 211, 238, 0.05) !important; }
  
  .btn-action-dots {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: rgba(148, 163, 184, 0.1);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 14px;
  }
  .btn-action-dots:hover {
    background: var(--primary-light);
    color: var(--primary);
  }
  
  /* Modal Menu Styles */
  .action-menu-list { list-style: none; padding: 0; margin: 0; }
  .action-menu-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px 20px;
    border-radius: 14px;
    color: var(--text-main);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    border: 1px solid transparent;
    cursor: pointer;
    background: none;
    width: 100%;
    text-align: left;
  }
  .action-menu-item:hover {
    background: var(--primary-light);
    color: var(--primary);
    border-color: rgba(34, 211, 238, 0.2);
  }
  .action-menu-item i { width: 20px; font-size: 18px; text-align: center; }
  .action-menu-item.danger:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }

  /* Info Grid in Modal */
  .lead-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    background: #f8fafc;
    padding: 24px;
    border-radius: 20px;
    margin-bottom: 20px;
    border: 1px solid rgba(0,0,0,0.05);
  }
  body.lms-dark-mode .lead-info-grid { 
    background: rgba(255, 255, 255, 0.05) !important; 
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
  }
  .info-item label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; letter-spacing: 0.8px; }
  .info-item span { display: block; font-size: 15px; font-weight: 700; color: var(--text-main); }
  
  .btn-back-actions {
    background: #f1f5f9 !important;
    color: #475569 !important;
    justify-content: center !important;
    font-weight: 700 !important;
    border: 1px solid #e2e8f0 !important;
  }
  body.lms-dark-mode .btn-back-actions {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #cbd5e1 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  body.lms-dark-mode .btn-back-actions:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
  }
  
  body.lms-dark-mode .modal-content {
    background: #0f172a !important;
    border: 1.5px solid rgba(255, 255, 255, 0.4) !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5) !important;
  }

  body.lms-dark-mode .card-lms {
    border: 1.5px solid #ffffff !important;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.7), 0 0 30px rgba(255, 255, 255, 0.1) !important;
  }
  
  .btn-action-dots {
    width: 30px !important; height: 30px !important; border-radius: 8px !important;
    background: rgba(91, 78, 250, 0.1) !important; color: var(--primary) !important;
    display: flex !important; align-items: center; justify-content: center; border: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
  }
  .btn-action-dots:hover {
    background: var(--primary) !important;
    color: #fff !important;
    transform: translateY(-1px);
  }

  /* Mobile-Specific Leads Table Overrides */
  @media (max-width: 768px) {
    #page-content { padding: 15px 10px !important; overflow-x: hidden !important; width: 100% !important; }
    .page-header { padding: 0 10px !important; flex-direction: column; align-items: flex-start; gap: 12px; }
    .page-header h1 { font-size: 20px !important; }
    .btn-primary-grad { width: 100% !important; justify-content: center !important; }

    .card-lms { margin: 10px 0 !important; border-radius: 24px !important; border: 1.5px solid rgba(255, 255, 255, 0.4) !important; }
    .card-lms-body { padding: 0 !important; overflow-x: hidden !important; }
    .card-lms-header { padding: 15px 20px !important; gap: 10px !important; }

    .search-bar { min-width: 100% !important; }
    .search-bar input { padding: 8px 0 !important; font-size: 13px !important; }
    .filter-select { height: 38px !important; font-size: 12px !important; min-width: 120px !important; }
    .btn-lms.btn-primary { height: 38px !important; padding: 0 20px !important; font-size: 13px !important; }
    .filter-actions a.btn-lms { height: 38px !important; width: 38px !important; }

    #leadsTable { table-layout: fixed !important; border-collapse: collapse !important; border-spacing: 0 !important; border: none !important; }
    #leadsTable th, #leadsTable td { 
      padding: 12px 10px !important; 
      border-radius: 0 !important; 
      border-left: none !important; 
      border-right: none !important; 
    }

    .col-id { width: 35px !important; padding-left: 10px !important; text-align: center !important; }
    .col-name { width: auto !important; }
    .col-actions-mobile { width: 45px !important; padding-right: 12px !important; text-align: right !important; display: table-cell !important; }
    
    /* Hide extra columns on mobile */
    .col-phone, .col-source, .col-status, .col-followup, .col-actions-desktop { display: none !important; }
  }


    /* Modal Mobile Compact Tweaks */
    .modal-dialog { margin: 12px !important; max-width: none !important; }
    .modal-header { padding: 14px 20px !important; }
    .modal-title { font-size: 16px !important; }
    .lead-info-grid { padding: 14px !important; gap: 10px !important; margin-bottom: 12px !important; border-radius: 15px !important; }
    .action-menu-item { padding: 10px 15px !important; border-radius: 10px !important; }
    .info-item label { font-size: 9px !important; margin-bottom: 2px !important; }
    .info-item span { font-size: 13px !important; }
  }

  /* Dark Mode Specific Overrides for Badges */
  body.lms-dark-mode .badge.bg-light,
  body.lms-dark-mode .badge.bg-light.text-dark {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #cbd5e1 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  body.lms-dark-mode .source-badge {
    background: rgba(15, 23, 42, 0.8) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    backdrop-filter: blur(8px);
  }
  body.lms-dark-mode .bg-info-light { background: rgba(14, 165, 233, 0.15) !important; color: #38bdf8 !important; }
  body.lms-dark-mode .bg-warning-light { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }
  body.lms-dark-mode .bg-success-light { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
  body.lms-dark-mode .bg-danger-light { background: rgba(239, 68, 68, 0.15) !important; color: #f87171 !important; }
</style>
CSS;

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

<div id="page-content">

  <div class="page-header">
    <div class="page-header-left">
      <h1>Leads Management</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Leads</span>
      </div>
    </div>
    <a href="add.php" class="btn-lms btn-primary-grad shadow-lg" id="btn-add-lead">
      <i class="fas fa-plus-circle me-1"></i> Add New Lead
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
        <div class="search-bar" style="flex: 1; min-width: 280px; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search Name or Phone..."
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="status" class="form-control-lms filter-select"
                  style="min-width: 160px; border-radius: 12px; font-weight: 600; padding: 10px 15px;"
                  onchange="this.form.submit()">
            <option value="">Status: All</option>
            <option value="new" <?= $status==='new' ? 'selected' : '' ?>>New</option>
            <option value="talking" <?= $status==='talking' ? 'selected' : '' ?>>Talking</option>
            <option value="converted" <?= $status==='converted' ? 'selected' : '' ?>>Converted</option>
            <option value="not_interested" <?= $status==='not_interested' ? 'selected' : '' ?>>Not Interested</option>
          </select>

          <select name="source" class="form-control-lms filter-select"
                  style="min-width: 140px; border-radius: 12px; font-weight: 600; padding: 10px 15px;"
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

    <div class="card-lms-body p-0" style="padding:0;">
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
      <table class="table-lms no-sticky" id="leadsTable">
        <thead>
          <tr>
            <th class="col-id" style="width: 60px; padding-left: 20px;">#</th>
            <th class="col-name">Lead Name</th>
            <th class="col-phone d-none d-md-table-cell" style="width: 150px;">Phone</th>
            <th class="col-source d-none d-lg-table-cell" style="width: 120px;">Source</th>
            <th class="col-status d-none d-lg-table-cell" style="width: 130px;">Status</th>
            <th class="col-followup d-none d-xl-table-cell" style="width: 180px;">Next Follow-up</th>
            <th class="col-actions-desktop d-none d-md-table-cell" style="width: 100px; text-align:center;">Actions</th>
            <th class="col-actions-mobile d-table-cell d-md-none" style="width: 60px; text-align:center;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $i => $l): 
              $isHighlighted = (isset($_GET['highlight_id']) && (int)$_GET['highlight_id'] === (int)$l['id']);
              $followupTime = $l['next_followup_datetime'] ? strtotime($l['next_followup_datetime']) : null;
              $isOverdue = ($followupTime && $followupTime < time() && $l['status'] !== 'converted');
              
              // Prepare JSON for JS
              $lJson = json_encode([
                'id' => $l['id'],
                'name' => $l['name'],
                'phone' => $l['phone'],
                'source' => $l['source'],
                'status' => $l['status'],
                'followup' => $l['next_followup_datetime'] ? date('d M Y, h:i A', $followupTime) : 'N/A',
                'notes' => $l['notes'] ?? 'No notes available.'
              ]);
          ?>
          <tr id="row-<?= $l['id'] ?>" 
              class="lead-row <?= $isHighlighted ? 'row-highlight' : '' ?>"
              onclick="openLeadMenu(<?= htmlspecialchars($lJson) ?>, event)">
            <td class="col-id" style="color:#94a3b8;font-size:13px; padding-left: 20px;"><?= (($page-1)*15)+$i+1 ?></td>
            <td class="col-name">
              <div class="fw-700" style="font-size:14px; color: var(--text-main);"><?= htmlspecialchars($l['name']) ?></div>
              <div class="d-md-none text-muted" style="font-size: 11px;"><?= htmlspecialchars($l['phone']) ?></div>
            </td>
            <td class="col-phone d-none d-md-table-cell" style="font-size:13px; font-weight: 600; color: var(--text-muted);">
              <?= htmlspecialchars($l['phone']) ?>
            </td>
            <td class="col-source d-none d-lg-table-cell">
               <span class="badge bg-light text-dark border px-2 py-1 source-badge" style="font-size: 11px;"><?= htmlspecialchars($l['source']) ?></span>
            </td>
            <td class="col-status d-none d-lg-table-cell">
                <?php
                $s = $l['status'];
                $sClass = match($s) {
                    'new' => 'bg-info-light text-info',
                    'talking' => 'bg-warning-light text-warning',
                    'converted' => 'bg-success-light text-success',
                    'not_interested' => 'bg-danger-light text-danger',
                    default => 'bg-light text-dark'
                };
                ?>
                <span class="badge <?= $sClass ?> px-2 py-1" style="font-size: 11px; text-transform: capitalize;"><?= $s ?></span>
            </td>
            <td class="col-followup d-none d-xl-table-cell" style="font-size: 12px; color: <?= $isOverdue ? '#ef4444' : 'var(--text-muted)' ?>;">
                <i class="fas fa-calendar-day me-1 opacity-50"></i>
                <?= $l['next_followup_datetime'] ? date('M d, g:i A', $followupTime) : 'Not set' ?>
            </td>
            <td class="col-actions-desktop d-none d-md-table-cell">
              <div class="d-flex justify-content-center gap-2">
                <a href="edit.php?id=<?= $l['id'] ?>" class="btn-lms btn-outline btn-sm" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <a href="index.php?act=delete&id=<?= $l['id'] ?>" 
                   class="btn-lms btn-danger btn-sm" 
                   title="Delete"
                   onclick="return confirm('Are you sure you want to delete this lead?')">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </td>
            <td class="col-actions-mobile d-table-cell d-md-none">
              <div class="d-flex justify-content-center">
                <button class="btn-action-dots">
                  <i class="fas fa-ellipsis-vertical"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($pages > 1): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page-1)*15)+1 ?> - <?= min($page*15,$total) ?> of <?= $total ?> leads
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

  <!-- Actions Modal -->
  <div class="modal fade" id="leadActionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius: 28px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background: var(--grad-primary); border: none; padding: 25px; color: #fff; position: relative;">
          <div>
            <h5 class="modal-title fw-800" id="modalLeadName" style="font-family: 'Poppins', sans-serif;">Lead Name</h5>
            <div id="modalLeadPhone" style="font-size: 14px; opacity: 0.9; font-weight: 600;">Phone Number</div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
          
          <div id="leadDetailView" style="display: none;" class="animate__animated animate__fadeIn">
             <div class="lead-info-grid">
                <div class="info-item">
                  <label>Source</label>
                  <span id="infoSource">Facebook</span>
                </div>
                <div class="info-item">
                  <label>Status</label>
                  <span id="infoStatus">New</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                  <label>Next Follow-up</label>
                  <span id="infoFollowup">24 May 2024</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                  <label>Admin Notes</label>
                  <span id="infoNotes" style="font-weight: 500; font-style: italic; opacity: 0.8;"></span>
                </div>
             </div>
             <div class="d-flex flex-column gap-2">
               <button class="action-menu-item btn-primary-grad text-white" onclick="toggleDetailView(false)" style="background: var(--grad-primary) !important; color: white !important;">
                  <i class="fas fa-list-check"></i> Lead Actions
               </button>
               <button class="action-menu-item btn-back-actions" data-bs-dismiss="modal">
                  <i class="fas fa-times"></i> Close Details
               </button>
             </div>
          </div>

          <div id="leadActionList" class="action-menu-list">
            <button class="action-menu-item" onclick="toggleDetailView(true)">
              <i class="fas fa-arrow-left" style="color: var(--primary);"></i>
              <div>
                Back to Details
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">View source, notes & history</div>
              </div>
            </button>
            
            <a href="#" id="actionEnroll" class="action-menu-item">
              <i class="fas fa-user-graduate" style="color: #10b981;"></i>
              <div>
                Enroll as Student
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Convert lead to registered student</div>
              </div>
            </a>
            
            <a href="#" id="actionEdit" class="action-menu-item">
              <i class="fas fa-pen-to-square" style="color: #f59e0b;"></i>
              <div>
                Edit Lead Information
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Update contact or status</div>
              </div>
            </a>
            
            <button class="action-menu-item danger" onclick="confirmDeleteLead()">
              <i class="fas fa-trash-can" style="color: #ef4444;"></i>
              <div>
                Delete Lead
                <div style="font-size: 11px; font-weight: 500; opacity: 0.7;">Permanently remove record</div>
              </div>
            </button>
          </div>

          <!-- Hidden delete form -->
          <form id="deleteForm" method="POST" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="id" id="deleteLeadId">
          </form>
        </div>
      </div>
    </div>
  </div>

<script>
let currentLead = null;

function openLeadMenu(lead, event) {
  // Don't open if clicking a button inside the row specifically (if any)
  if (event.target.closest('.btn-lms')) return;
  
  currentLead = lead;
  document.getElementById('modalLeadName').textContent = lead.name;
  document.getElementById('modalLeadPhone').textContent = lead.phone;
  
  // Update links
  document.getElementById('actionEnroll').href = `<?= BASE_URL ?>/admin/students/add.php?name=${encodeURIComponent(lead.name)}&phone=${encodeURIComponent(lead.phone)}&lead_id=${lead.id}`;
  document.getElementById('actionEdit').href = `edit.php?id=${lead.id}`;
  document.getElementById('deleteLeadId').value = lead.id;
  
  // Open details view directly
  toggleDetailView(true);
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('leadActionsModal'));
  modal.show();
}

function toggleDetailView(show) {
  const list = document.getElementById('leadActionList');
  const details = document.getElementById('leadDetailView');
  
  if (show) {
    // Populate details
    document.getElementById('infoSource').textContent = currentLead.source;
    document.getElementById('infoStatus').textContent = currentLead.status.charAt(0).toUpperCase() + currentLead.status.slice(1);
    document.getElementById('infoFollowup').textContent = currentLead.followup;
    document.getElementById('infoNotes').textContent = currentLead.notes;
    
    list.style.display = 'none';
    details.style.display = 'block';
  } else {
    list.style.display = 'block';
    details.style.display = 'none';
  }
}

function confirmDeleteLead() {
  if (confirm(`Are you sure you want to delete lead '${currentLead.name}'?`)) {
    document.getElementById('deleteForm').submit();
  }
}

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

