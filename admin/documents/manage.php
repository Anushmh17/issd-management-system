<?php
// =====================================================
// LEARN Management - Admin: Document Manager (per student)
// admin/documents/manage.php
// =====================================================
define('PAGE_TITLE', 'Manage Documents');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/backend/document_controller.php';

requireRole(ROLE_ADMIN);

// ---- Load student ----
$studentId = (int)($_GET['student_id'] ?? 0);
if (!$studentId) {
    setFlash('danger', 'Invalid student.');
    header('Location: index.php'); exit;
}

$stmtS = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmtS->execute([$studentId]);
$student = $stmtS->fetch();
if (!$student) {
    setFlash('danger', 'Student not found.');
    header('Location: index.php'); exit;
}

$defs   = getDocumentDefinitions();
$docRow = getOrCreateDocRecord($pdo, $studentId);

$errors   = [];
$messages = [];

// =====================================================
// Handle POST — process one document at a time
// =====================================================
    // =====================================================
    // Handle Quick Upload (from index.php)
    // =====================================================
    if (isset($_POST['quick_upload'])) {
        $docKey = $_POST['doc_key'] ?? '';
        
        if ($docKey === 'other') {
            // Handle as "Other Supporting Doc"
            $label = trim($_POST['other_label'] ?? 'Other Document');
            if (empty($_FILES['doc_file']['name'])) {
                $errors[] = 'Please select a file.';
            } else {
                $upload = uploadDocumentFile($_FILES['doc_file'], 'other', $studentId);
                if (!$upload['success']) {
                    $errors[] = $upload['error'];
                } else {
                    saveOtherDoc($pdo, [
                        'student_id' => $studentId,
                        'label' => $label,
                        'file_path' => $upload['path'],
                        'collected_by' => $_POST['other_collected_by'] ?? null,
                        'collected_date' => date('Y-m-d')
                    ]);
                    setFlash('success', 'Additional document added for ' . htmlspecialchars($student['full_name']));
                    header('Location: index.php'); exit;
                }
            }
        } else {
            // Handle as "Standard Checklist Doc"
            if (!array_key_exists($docKey, $defs)) {
                $errors[] = 'Invalid document type.';
            } elseif (empty($_FILES['doc_file']['name'])) {
                $errors[] = 'Please select a file.';
            } else {
                $uploadResult = uploadDocumentFile($_FILES['doc_file'], $docKey, $studentId);
                if (!$uploadResult['success']) {
                    $errors[] = $uploadResult['error'];
                } else {
                    // Update standard tracking
                    $trackData = [
                        'status'       => 1,
                        'collected_by' => $_POST['other_collected_by'] ?? null,
                        'date'         => date('Y-m-d'),
                        'file_path'    => $uploadResult['path']
                    ];
                    if (saveDocTracking($pdo, $studentId, $docKey, $trackData)) {
                        setFlash('success', htmlspecialchars($defs[$docKey]['label']) . ' uploaded for ' . htmlspecialchars($student['full_name']));
                        header('Location: index.php'); exit;
                    } else {
                        $errors[] = 'Failed to save tracking info.';
                    }
                }
            }
        }
        if ($errors) {
            // Stay on page to show errors
            goto renderPage;
        }
    }

    // =====================================================
    // Handle NEW "Other Supporting Doc" (from manage.php itself)
    // =====================================================
    if (isset($_POST['add_other'])) {
        $label = trim($_POST['other_label'] ?? 'Other Document');
        if (empty($_FILES['other_file']['name'])) {
            $errors[] = 'Please select a file to upload.';
        } else {
            $upload = uploadDocumentFile($_FILES['other_file'], 'other', $studentId);
            if (!$upload['success']) {
                $errors[] = $upload['error'];
            } else {
                saveOtherDoc($pdo, [
                    'student_id' => $studentId,
                    'label' => $label,
                    'file_path' => $upload['path'],
                    'collected_by' => $_POST['other_collected_by'] ?? null,
                    'collected_date' => !empty($_POST['other_date']) ? $_POST['other_date'] : null
                ]);
                $messages[] = 'Additional document added.';
            }
        }
        goto renderPage;
    }

    // =====================================================
    // Handle Delete "Other Supporting Doc"
    // =====================================================
    if (isset($_POST['del_other_id'])) {
        deleteOtherDoc($pdo, (int)$_POST['del_other_id']);
        $messages[] = 'Document removed.';
        goto renderPage;
    }

    // =====================================================
    // Handle Standard Checklist Doc Save (from manage.php)
    // =====================================================
    if (isset($_POST['doc_key']) && !isset($_POST['quick_upload'])) {
        $actDoc = $_POST['doc_key'];

        // Validate doc key against whitelist
        if (!array_key_exists($actDoc, $defs)) {
            $errors[] = 'Invalid document key.';
            goto renderPage;
        }

        $trackData = [
            'status'       => isset($_POST['doc_status']) ? 1 : 0,
            'collected_by' => $_POST['collected_by'] ?? null,
            'date'         => !empty($_POST['collected_date']) ? $_POST['collected_date'] : null,
        ];

        $newFilePath = null;

        // ---- Handle file upload ----
        if (!empty($_FILES['doc_file']['name'])) {
            $uploadResult = uploadDocumentFile($_FILES['doc_file'], $actDoc, $studentId);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
                goto renderPage;
            }
            // Delete old file if exists
            if (!empty($docRow[$actDoc])) {
                deleteDocFile($docRow[$actDoc]);
            }
            $newFilePath = $uploadResult['path'];
            $trackData['file_path'] = $newFilePath;

            // Auto-check status when file uploaded
            if (!$trackData['status']) {
                $trackData['status'] = 1;
            }
        }

        // ---- Handle file delete request ----
        if (isset($_POST['delete_file']) && !empty($docRow[$actDoc])) {
            deleteDocFile($docRow[$actDoc]);
            $trackData['file_path'] = ''; // Clear path
        }

        if (saveDocTracking($pdo, $studentId, $actDoc, $trackData)) {
            // Reload doc row
            $docRow = getOrCreateDocRecord($pdo, $studentId);
            $messages[] = htmlspecialchars($defs[$actDoc]['label']) . ' updated successfully.';
        } else {
            $errors[] = 'Failed to save. Please try again.';
        }
}


renderPage:

// Compute overall completion
$reqKeys   = array_keys(array_filter($defs, fn($d) => $d['required']));
$reqTotal  = count($reqKeys);
$reqDone   = 0;
foreach ($reqKeys as $k) {
    if (!empty($docRow[$k . '_status'])) $reqDone++;
}
$overallStatus = computeDocStatus($docRow);
$overallPct    = $reqTotal > 0 ? round(($reqDone / $reqTotal) * 100) : 0;

// Group definitions by group label
$groups = [];
foreach ($defs as $key => $def) {
    $groups[$def['group']][$key] = $def;
}

// Load existing "Other" documents
$otherDocs = getOtherStudentDocs($pdo, $studentId);

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>Document Manager</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Admin &rsaquo;
        <a href="index.php" style="color:inherit;">Documents</a> &rsaquo;
        <span><?= htmlspecialchars($student['full_name']) ?></span>
      </div>
    </div>
    <a href="index.php" class="btn-lms btn-outline" id="btn-back-docs">
      <i class="fas fa-arrow-left"></i> Back to List
    </a>
  </div>

  <!-- Student Info Banner -->
  <div class="doc-student-banner mb-20">
    <div class="dsb-avatar" style="background:<?= studentAvatarColor($student['full_name']) ?>">
      <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
    </div>
    <div class="dsb-info">
      <div class="dsb-name"><?= htmlspecialchars($student['full_name']) ?></div>
      <div class="dsb-meta">
        <span><i class="fas fa-id-badge"></i> <?= htmlspecialchars($student['student_id']) ?></span>
        <span><i class="fas fa-layer-group"></i> <?= htmlspecialchars($student['batch_number']) ?></span>
        <span><i class="fas fa-phone"></i> <?= htmlspecialchars($student['phone_number']) ?></span>
      </div>
    </div>
    <div class="dsb-status-box">
      <div class="dsb-progress-ring" data-pct="<?= $overallPct ?>">
        <svg width="72" height="72" viewBox="0 0 72 72">
          <circle cx="36" cy="36" r="28" fill="none" stroke="#e8e4ff" stroke-width="7"/>
          <circle cx="36" cy="36" r="28" fill="none"
            stroke="<?= $overallStatus==='completed'?'#10b981':($overallStatus==='pending'?'#f59e0b':'#ef4444') ?>"
            stroke-width="7"
            stroke-dasharray="175.93"
            stroke-dashoffset="<?= 175.93 - (175.93 * $overallPct / 100) ?>"
            stroke-linecap="round"
            transform="rotate(-90 36 36)"/>
        </svg>
        <div class="ring-pct"><?= $overallPct ?>%</div>
      </div>
      <div class="dsb-status-label">
        <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;">Required Docs</div>
        <div style="font-size:14px;font-weight:700;color:<?= $overallStatus==='completed'?'#059669':($overallStatus==='pending'?'#d97706':'#dc2626') ?>">
          <?= $reqDone ?> / <?= $reqTotal ?> Collected
        </div>
        <?= renderDocStatusBadge($overallStatus) ?>
      </div>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if ($messages): ?>
    <?php foreach ($messages as $msg): ?>
    <div class="alert-lms success auto-dismiss">
      <i class="fas fa-circle-check"></i>
      <?= $msg ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert-lms danger auto-dismiss">
      <i class="fas fa-triangle-exclamation"></i>
      <div>
        <?php foreach ($errors as $e): ?>
          <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- =====================================================
       DOCUMENT CHECKLIST — grouped by category
  ====================================================== -->
  <?php foreach ($groups as $groupName => $groupDocs): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title">
        <i class="fas fa-folder-closed" style="color:#5b4efa;"></i>
        <?= htmlspecialchars($groupName) ?>
      </div>
      <?php
        $gReq  = array_filter($groupDocs, fn($d) => $d['required']);
        $gDone = count(array_filter(array_keys($gReq), fn($k) => !empty($docRow[$k.'_status'])));
        $gTotal = count($groupDocs);
        $gDoneAll = count(array_filter(array_keys($groupDocs), fn($k) => !empty($docRow[$k.'_status'])));
      ?>
      <span class="section-badge">
        <?= $gDoneAll ?> / <?= $gTotal ?> collected
      </span>
    </div>
    <div class="card-lms-body" style="padding:0;">
      <table class="doc-checklist-table">
        <thead>
          <tr>
            <th style="width:30px;"></th>
            <th>Document</th>
            <th style="width:100px;">Type</th>
            <th style="width:130px;">Office</th>
            <th style="width:140px;">Date Collected</th>
            <th style="width:180px;">File</th>
            <th style="width:80px;">Upload</th>
            <th style="width:80px;">Save</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groupDocs as $docKey => $def): ?>
          <?php
            $isCollected = !empty($docRow[$docKey . '_status']);
            $collectedBy = $docRow[$docKey . '_collected_by'] ?? '';
            $collectedDate = $docRow[$docKey . '_date'] ?? '';
            $filePath    = $docRow[$docKey] ?? '';
            $rowClass    = $isCollected ? 'doc-row-collected' : ($def['required'] ? 'doc-row-missing' : 'doc-row-optional');
          ?>
          <tr class="<?= $rowClass ?>" id="doc-row-<?= $docKey ?>">
            <!-- Status indicator dot -->
            <td>
              <div class="doc-status-dot <?= $isCollected ? 'collected' : ($def['required'] ? 'missing' : 'optional') ?>"></div>
            </td>

            <!-- Document name -->
            <td>
              <div class="doc-name">
                <i class="fas <?= $def['icon'] ?>" style="color:<?= $isCollected?'#059669':'#94a3b8' ?>;margin-right:6px;"></i>
                <span class="fw-600"><?= htmlspecialchars($def['label']) ?></span>
                <?php if ($def['required']): ?>
                  <span class="req-badge">Required</span>
                <?php else: ?>
                  <span class="opt-badge">Optional</span>
                <?php endif; ?>
              </div>
            </td>

            <!-- Type indicator -->
            <td>
              <?php if ($def['required']): ?>
                <span class="badge-lms" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:10px;">
                  <i class="fas fa-star" style="font-size:8px;"></i> Required
                </span>
              <?php else: ?>
                <span class="badge-lms" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;font-size:10px;">
                  Optional
                </span>
              <?php endif; ?>
            </td>

            <!-- Inline mini-form for this document -->
            <form method="POST" action="manage.php?student_id=<?= $studentId ?>"
                  enctype="multipart/form-data"
                  id="form-<?= $docKey ?>"
                  class="doc-inline-form">
              <input type="hidden" name="doc_key"    value="<?= $docKey ?>">
              <input type="hidden" name="doc_status" value="0" id="hidden-status-<?= $docKey ?>">

              <!-- Office dropdown -->
              <td>
                <select name="collected_by" class="form-control-lms doc-select"
                        id="office-<?= $docKey ?>">
                  <option value="">— Office —</option>
                  <?php foreach (['W1','W2','H1','H2'] as $office): ?>
                    <option value="<?= $office ?>" <?= $collectedBy === $office ? 'selected' : '' ?>>
                      <?= $office ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>

              <!-- Date picker -->
              <td>
                <input type="date" name="collected_date" class="form-control-lms doc-input"
                       id="date-<?= $docKey ?>"
                       value="<?= htmlspecialchars($collectedDate) ?>">
              </td>

              <!-- File preview / delete -->
              <td>
                <?php if ($filePath): ?>
                  <div class="doc-file-cell">
                    <a href="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($filePath) ?>"
                       target="_blank" class="doc-file-link"
                       title="View <?= htmlspecialchars($def['label']) ?>">
                      <i class="fas <?= str_ends_with($filePath,'.pdf') ? 'fa-file-pdf' : 'fa-file-image' ?>"></i>
                      <span><?= strtoupper(pathinfo($filePath, PATHINFO_EXTENSION)) ?></span>
                    </a>
                    <button type="submit" name="delete_file" value="1"
                            class="doc-del-file"
                            data-confirm="Remove this file?"
                            title="Remove file">
                      <i class="fas fa-xmark"></i>
                    </button>
                  </div>
                <?php else: ?>
                  <span style="font-size:11px;color:#94a3b8;">No file</span>
                <?php endif; ?>
              </td>

              <!-- Upload button -->
              <td>
                <label class="doc-upload-label" for="file-<?= $docKey ?>" title="Upload file">
                  <i class="fas fa-cloud-arrow-up"></i>
                  <input type="file" name="doc_file" id="file-<?= $docKey ?>"
                         accept=".pdf,.jpg,.jpeg,.png"
                         class="doc-file-input"
                         onchange="handleFileChange('<?= $docKey ?>')">
                </label>
              </td>

              <!-- Collected checkbox + Save -->
              <td>
                <div class="doc-save-cell">
                  <label class="doc-check-wrap" title="Mark as collected">
                    <input type="checkbox" class="doc-checkbox"
                           id="check-<?= $docKey ?>"
                           <?= $isCollected ? 'checked' : '' ?>
                           onchange="syncHiddenStatus('<?= $docKey ?>')">
                    <span class="doc-checkmark"></span>
                  </label>
                  <button type="submit" class="btn-lms btn-primary btn-sm doc-save-btn"
                          id="save-<?= $docKey ?>"
                          onclick="syncHiddenStatus('<?= $docKey ?>')">
                    <i class="fas fa-floppy-disk"></i>
                  </button>
                </div>
              </td>

            </form>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- =====================================================
       OTHER SUPPORTING DOCUMENTS
  ====================================================== -->
  <div class="card-lms mt-40">
    <div class="card-lms-header" style="background:linear-gradient(90deg, #f8fafc, #f1f5f9);">
      <div class="card-lms-title">
        <i class="fas fa-file-circle-plus" style="color:#0ea5e9;"></i>
        Other Supporting Documents
      </div>
      <span class="badge-lms info"><?= count($otherDocs) ?> Uploaded</span>
    </div>
    <div class="card-lms-body">
      
      <!-- Upload New Other Doc -->
      <form method="POST" enctype="multipart/form-data" class="mb-20 p-3" style="background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1;">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <div class="form-group-lms mb-0">
              <label style="font-size:12px;font-weight:600;">Document Label / Name</label>
              <input type="text" name="other_label" class="form-control-lms" placeholder="e.g. Birth Certificate" required>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group-lms mb-0">
              <label style="font-size:12px;font-weight:600;">File (PDF/Image)</label>
              <input type="file" name="other_file" class="form-control-lms" required>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group-lms mb-0">
              <label style="font-size:12px;font-weight:600;">Office</label>
              <select name="other_collected_by" class="form-control-lms">
                <option value="">—</option>
                <option value="W1">W1</option><option value="W2">W2</option>
                <option value="H1">H1</option><option value="H2">H2</option>
              </select>
            </div>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" name="add_other" class="btn-primary-grad w-100">
              <i class="fas fa-upload"></i> Upload Extra
            </button>
          </div>
        </div>
      </form>

      <?php if (empty($otherDocs)): ?>
        <p class="text-muted text-center py-3" style="font-size:13px;">No additional supporting documents uploaded.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table-lms">
          <thead>
            <tr>
              <th>Document Name</th>
              <th>Office</th>
              <th>Uploaded Date</th>
              <th>File</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($otherDocs as $od): ?>
            <tr>
              <td>
                <div class="fw-700" style="color:var(--text-main);"><?= htmlspecialchars($od['label']) ?></div>
              </td>
              <td><span class="badge-lms secondary"><?= htmlspecialchars($od['collected_by'] ?: '—') ?></span></td>
              <td style="font-size:12px;"><?= date('M d, Y', strtotime($od['created_at'])) ?></td>
              <td>
                <a href="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($od['file_path']) ?>" target="_blank" class="btn-lms btn-outline btn-sm">
                  <i class="fas fa-eye"></i> View
                </a>
              </td>
              <td style="text-align:right;">
                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this additional document?')">
                  <input type="hidden" name="del_other_id" value="<?= $od['id'] ?>">
                  <button type="submit" class="btn-lms btn-danger btn-sm">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php
function studentAvatarColor(string $name): string {
    $colors = ['#5b4efa','#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4'];
    return $colors[ord($name[0]) % count($colors)];
}
?>

<?php
$extraJS = <<<'JS'
<script>
// Sync checkbox → hidden status field
function syncHiddenStatus(docKey) {
  const cb  = document.getElementById('check-' + docKey);
  const hid = document.getElementById('hidden-status-' + docKey);
  if (cb && hid) hid.value = cb.checked ? '1' : '0';
}

// When file selected, show filename preview and auto-check
function handleFileChange(docKey) {
  const fileInput = document.getElementById('file-' + docKey);
  const cb        = document.getElementById('check-' + docKey);
  const hid       = document.getElementById('hidden-status-' + docKey);
  if (fileInput.files.length > 0) {
    // Auto-check status when file is attached
    if (cb) { cb.checked = true; }
    if (hid) hid.value = '1';
    // Show filename in tooltip
    fileInput.closest('label').title = fileInput.files[0].name;
  }
}

// Init all states on load
document.querySelectorAll('.doc-checkbox').forEach(cb => {
  const key = cb.id.replace('check-', '');
  const hid = document.getElementById('hidden-status-' + key);
  if (hid) hid.value = cb.checked ? '1' : '0';
});
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>
