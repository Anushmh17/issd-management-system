<?php
// =====================================================
// LEARN Management - Admin: Documents - Select Student
// admin/documents/index.php
// =====================================================
define('PAGE_TITLE', 'Document Tracking');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/document_controller.php';
require_once dirname(__DIR__, 2) . '/backend/student_controller.php';

requireRole(ROLE_ADMIN);

// ---- Filters ----
$search = trim($_GET['search'] ?? '');
$batch  = trim($_GET['batch']  ?? '');
$docStatus = trim($_GET['doc_status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$filters = ['search' => $search, 'batch' => $batch];
$result  = getStudentsList($pdo, $filters, $page, 20);
$students = $result['students'];
$total    = $result['total'];
$pages    = $result['pages'];
$batches  = getAllBatches($pdo);

// ---- Bulk doc status ----
$studentIds = array_column($students, 'id');
$docStatuses = getBulkDocStatus($pdo, array_map('intval', $studentIds));

// ---- Filter by doc_status if chosen ----
if ($docStatus !== '') {
    $students = array_values(array_filter($students, function($s) use ($docStatuses, $docStatus) {
        return ($docStatuses[(int)$s['id']] ?? 'missing') === $docStatus;
    }));
}

// ---- Aggregate counts ----
$allIds = array_map('intval', array_column($result['students'], 'id'));
$allDocStatuses = getBulkDocStatus($pdo, $allIds);
$cCompleted = count(array_filter($allDocStatuses, fn($s) => $s === 'completed'));
$cPending   = count(array_filter($allDocStatuses, fn($s) => $s === 'pending'));
$cMissing   = count(array_filter($allDocStatuses, fn($s) => $s === 'missing'));

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Document Tracking</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo; <span>Documents</span>
      </div>
    </div>
    <button type="button" class="btn-primary-grad" data-bs-toggle="modal" data-bs-target="#quickUploadModal">
      <i class="fas fa-file-upload"></i> Quick Upload
    </button>
  </div>

  <!-- Stats Row -->
  <div class="row g-3 mb-20">
    <div class="col-6 col-md-3">
      <a href="index.php" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#5b4efa;">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $result['total'] ?></div>
            <div class="stat-label">Total Students</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?doc_status=completed" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#10b981;">
          <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $cCompleted ?></div>
            <div class="stat-label">Docs Complete</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?doc_status=pending" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#f59e0b;">
          <div class="stat-icon"><i class="fas fa-clock"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $cPending ?></div>
            <div class="stat-label">Docs Pending</div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="index.php?doc_status=missing" class="text-decoration-none">
        <div class="stat-card" style="--sc-color:#ef4444;">
          <div class="stat-icon"><i class="fas fa-circle-xmark"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= $cMissing ?></div>
            <div class="stat-label">Docs Missing</div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Students Card -->
  <div class="card-lms">
    <div class="card-lms-header" style="display: flex; flex-direction: column; padding: 25px 30px; gap: 20px;">
      <!-- Title Row -->
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="list-legend">
          <div class="list-legend-label" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); font-weight: 700; margin-bottom: 2px;">Document Tracking</div>
          <div class="list-legend-title" style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 24px; display: flex; align-items: center; gap: 12px;">
            <span>Select Student</span>
            <span class="count-badge" style="background: var(--primary-light); color: var(--primary); padding: 4px 14px; border-radius: 30px; font-size: 14px;"><?= count($students) ?></span>
          </div>
        </div>
      </div>

      <!-- Filters Row -->
      <form method="GET" id="filterForm" class="students-filters" style="display: flex; align-items: center; gap: 15px; margin: 0; flex-wrap: wrap;">
        <div class="search-bar" style="flex: 1; min-width: 300px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0 15px; display: flex; align-items: center;">
          <i class="fas fa-search" style="color: var(--primary); opacity: 0.6; margin-right: 10px;"></i>
          <input type="text" name="search" placeholder="Search by Name, ID or NIC…"
                 style="font-size: 14px; font-weight: 500; border: none; outline: none; padding: 12px 0; width: 100%;"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="d-flex gap-2">
          <select name="batch" class="form-control-lms filter-select"
                  style="min-width: 140px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="document.getElementById('filterForm').submit()">
            <option value="">All Batches</option>
            <?php foreach ($batches as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>" <?= $batch === $b ? 'selected' : '' ?>>
                Batch <?= htmlspecialchars($b) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="doc_status" class="form-control-lms filter-select"
                  style="min-width: 160px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-weight: 600; padding: 10px 15px;"
                  onchange="document.getElementById('filterForm').submit()">
            <option value="">All Status</option>
            <option value="completed" <?= $docStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="pending"   <?= $docStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
            <option value="missing"   <?= $docStatus === 'missing'   ? 'selected' : '' ?>>Missing</option>
          </select>
        </div>

        <button type="submit" class="btn-lms btn-primary px-4 rounded-3 shadow-sm" style="padding: 12px 25px; height: 46px;">
          <i class="fas fa-filter me-1"></i> Filter
        </button>
      </form>
    </div>

    <div class="card-lms-body" style="padding:0;overflow-x:auto;">
      <?php if (empty($students)): ?>
        <div class="empty-state">
          <i class="fas fa-folder-open"></i>
          <p>No students found<?= ($search || $batch || $docStatus) ? ' matching your filters.' : '.' ?></p>
        </div>
      <?php else: ?>
      <table class="table-lms" id="docStudentsTable">
        <thead>
          <tr>
            <th>#</th>
            <th style="min-width:140px;">Student ID</th>
            <th>Full Name</th>
            <th style="min-width:100px;">Batch</th>
            <th>Phone</th>
            <th>Doc Status</th>
            <th style="min-width:150px;">Progress</th>
            <th style="text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s): ?>
          <?php
            $docSt  = $docStatuses[(int)$s['id']] ?? 'missing';
            // Get per-student doc counts for progress bar
            $docRow = $pdo->prepare("SELECT * FROM student_documents WHERE student_id = ?");
            $docRow->execute([$s['id']]);
            $docRow = $docRow->fetch() ?: [];
            $defs   = getDocumentDefinitions();
            $reqKeys  = array_keys(array_filter($defs, fn($d) => $d['required']));
            $reqTotal = count($reqKeys);
            $reqDone  = 0;
            foreach ($reqKeys as $k) {
                if (!empty($docRow[$k . '_status'])) $reqDone++;
            }
            $pct = $reqTotal > 0 ? round(($reqDone / $reqTotal) * 100) : 0;
          ?>
          <tr class="doc-row-<?= $docSt ?>">
            <td style="color:#94a3b8;font-size:13px;"><?= (($page-1)*20)+$i+1 ?></td>
            <td>
              <span class="id-badge-lms">
                <?= htmlspecialchars($s['student_id']) ?>
              </span>
            </td>
            <td>
              <div class="d-flex align-center gap-10">
                <div class="avatar-initials" style="background:<?= studentAvatarColor($s['full_name']) ?>;flex-shrink:0;">
                  <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                </div>
                <div>
                  <div class="fw-600" style="font-size:14px;"><?= htmlspecialchars($s['full_name']) ?></div>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['nic_number']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="batch-badge-lms">
                <?= htmlspecialchars($s['batch_number']) ?>
              </span>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($s['phone_number']) ?></td>
            <td><?= renderDocStatusBadge($docSt) ?></td>
            <td style="min-width:140px;">
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="doc-progress-track">
                  <div class="doc-progress-fill <?= $docSt ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span style="font-size:11px;font-weight:700;color:<?= $docSt==='completed'?'#059669':($docSt==='pending'?'#d97706':'#dc2626') ?>">
                  <?= $reqDone ?>/<?= $reqTotal ?>
                </span>
              </div>
            </td>
            <td style="text-align:center;">
              <a href="manage.php?student_id=<?= $s['id'] ?>"
                 class="btn-lms btn-primary btn-sm"
                 id="btn-manage-docs-<?= $s['id'] ?>">
                <i class="fas fa-folder-open"></i> Manage Docs
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($pages > 1 && !$docStatus): ?>
      <div class="pagination-lms">
        <div class="pagination-info">
          Showing <?= (($page-1)*20)+1 ?>–<?= min($page*20, $total) ?> of <?= $total ?> students
        </div>
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
            <a href="index.php?<?= http_build_query(['search'=>$search,'batch'=>$batch,'page'=>$page-1]) ?>"
               class="page-btn"><i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($p = max(1,$page-2); $p <= min($pages,$page+2); $p++): ?>
            <a href="index.php?<?= http_build_query(['search'=>$search,'batch'=>$batch,'page'=>$p]) ?>"
               class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page < $pages): ?>
            <a href="index.php?<?= http_build_query(['search'=>$search,'batch'=>$batch,'page'=>$page+1]) ?>"
               class="page-btn"><i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /#page-content -->

<!-- Quick Upload Modal -->
<div class="modal fade" id="quickUploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content lms-modal">
      <div class="modal-header border-0 pb-0 px-4 mt-2">
        <h5 class="modal-title fw-700" style="color:var(--primary);"><i class="fas fa-cloud-arrow-up me-2"></i> Quick Document Upload</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="manage.php" method="POST" enctype="multipart/form-data" id="quickUploadForm">
        <div class="modal-body p-4">
            <div class="form-group-lms mb-20">
                <label>Select Student <span class="req">*</span></label>
                <select name="student_id" class="form-control-lms" required>
                    <option value="">— Select a student —</option>
                    <?php 
                      $allStudents = $pdo->query("SELECT id, full_name, student_id FROM students ORDER BY full_name")->fetchAll();
                      foreach ($allStudents as $as): 
                    ?>
                        <option value="<?= $as['id'] ?>"><?= htmlspecialchars($as['full_name']) ?> (<?= $as['student_id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-lms mb-20">
                <label>Document Type <span class="req">*</span></label>
                <select name="doc_key" id="quick_doc_key" class="form-control-lms" required onchange="toggleQuickLabel()">
                    <option value="">— Select document type —</option>
                    <optgroup label="Standard Documents">
                        <?php 
                          $defs = getDocumentDefinitions();
                          foreach ($defs as $key => $def): 
                        ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($def['label']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Extra">
                        <option value="other">Other Supporting Document</option>
                    </optgroup>
                </select>
            </div>

            <div class="form-group-lms mb-20" id="quick_label_wrap" style="display:none;">
                <label>Custom Document Label <span class="req">*</span></label>
                <input type="text" name="other_label" id="quick_other_label" class="form-control-lms" placeholder="e.g. Birth Certificate, Sports Cert...">
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <div class="form-group-lms mb-20">
                        <label>Collected Office</label>
                        <select name="other_collected_by" class="form-control-lms">
                            <option value="">—</option>
                            <option value="W1">W1</option><option value="W2">W2</option>
                            <option value="H1">H1</option><option value="H2">H2</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-lms mb-20">
                        <label>File (PDF/Image) <span class="req">*</span></label>
                        <input type="file" name="doc_file" class="form-control-lms" required>
                    </div>
                </div>
            </div>
            
            <div id="quick-upload-info" class="text-muted mt-2" style="font-size:11px; line-height:1.4;">
                <i class="fas fa-info-circle me-1"></i> Please select a document type.
            </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-0">
          <input type="hidden" name="quick_upload" value="1">
          <button type="button" class="btn-lms btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-grad px-4">Upload Now</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleQuickLabel() {
    const keySelect = document.getElementById('quick_doc_key');
    const labelWrap = document.getElementById('quick_label_wrap');
    const labelInput = document.getElementById('quick_other_label');
    const infoText = document.getElementById('quick-upload-info');
    
    if (keySelect.value === 'other') {
        labelWrap.style.display = 'block';
        labelInput.required = true;
        infoText.innerHTML = '<i class="fas fa-info-circle me-1"></i> This will be added to <strong>Other Supporting Documents</strong>.';
    } else {
        labelWrap.style.display = 'none';
        labelInput.required = false;
        if (keySelect.value) {
            infoText.innerHTML = '<i class="fas fa-info-circle me-1"></i> This will update the <strong>standard checklist</strong> for the student.';
        } else {
            infoText.innerHTML = '<i class="fas fa-info-circle me-1"></i> Please select a document type.';
        }
    }
}
</script>

<?php
function studentAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
