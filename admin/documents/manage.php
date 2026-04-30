<?php
// =====================================================
// ISSD Management - Admin: Document Manager (per student)
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
// Handle POST "" process one document at a time
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
    }

    // =====================================================
    // Handle Delete "Other Supporting Doc"
    // =====================================================
    if (isset($_POST['del_other_id'])) {
        deleteOtherDoc($pdo, (int)$_POST['del_other_id']);
        $messages[] = 'Document removed.';
    }

    // =====================================================
    // Handle Standard Checklist Doc Save (from manage.php)
    // =====================================================
    if (isset($_POST['doc_key']) && !isset($_POST['quick_upload'])) {
        $actDoc = $_POST['doc_key'];

        // Validate doc key against whitelist
        if (!array_key_exists($actDoc, $defs)) {
            $errors[] = 'Invalid document key.';
        } else {
            $trackData = [
                'status'       => ($_POST['doc_status'] ?? '0') === '1' ? 1 : 0,
                'collected_by' => $_POST['collected_by'] ?? null,
                'date'         => !empty($_POST['collected_date']) ? $_POST['collected_date'] : null,
            ];

            $newFilePath = null;

            // ---- Handle file upload ----
            if (!empty($_FILES['doc_file']['name'])) {
                $uploadResult = uploadDocumentFile($_FILES['doc_file'], $actDoc, $studentId);
                if (!$uploadResult['success']) {
                    $errors[] = $uploadResult['error'];
                } else {
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
            }

            // ---- Handle file delete request ----
            if (isset($_POST['delete_file']) && !empty($docRow[$actDoc])) {
                deleteDocFile($docRow[$actDoc]);
                $trackData['file_path'] = ''; // Clear path
            }

            if (empty($errors)) {
                if (saveDocTracking($pdo, $studentId, $actDoc, $trackData)) {
                    // Reload doc row
                    $docRow = getOrCreateDocRecord($pdo, $studentId);
                    $messages[] = htmlspecialchars($defs[$actDoc]['label']) . ' updated successfully.';
                } else {
                    $errors[] = 'Failed to save. Please try again.';
                }
            }
        }
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

$extraCSS = <<<'CSS'
<style>
/* Document Manager Specific Styles */
.doc-student-banner {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1.5px solid var(--border-color);
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}
.doc-student-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 6px; height: 100%;
    background: var(--primary);
}
.dsb-avatar {
    width: 72px; height: 72px;
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 800; color: #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    flex-shrink: 0;
}
.dsb-info { flex: 1; }
.dsb-name { font-size: 22px; font-weight: 800; color: var(--text-main); margin-bottom: 6px; font-family: 'Poppins', sans-serif; }
.dsb-meta { display: flex; gap: 15px; color: var(--text-muted); font-size: 13px; font-weight: 500; }
.dsb-meta span { display: flex; align-items: center; gap: 6px; }
.dsb-meta i { color: var(--primary); opacity: 0.7; }

.dsb-status-box {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-left: 24px;
    border-left: 1.5px solid var(--border-light);
}
.dsb-progress-ring { position: relative; width: 72px; height: 72px; }
.dsb-progress-ring svg { transform: rotate(-90deg); }
.dsb-progress-ring .ring-pct {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    font-size: 14px; font-weight: 800; color: var(--text-main);
}
.dsb-status-label { display: flex; flex-direction: column; gap: 4px; }

/* Checklist Table */
.doc-checklist-table { width: 100%; border-collapse: collapse; }
.doc-checklist-table thead th {
    padding: 14px 16px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1.5px solid var(--border-light);
    text-align: left;
}
.doc-checklist-table tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid var(--border-light); }
.doc-checklist-table tbody tr:hover { background: #fcfcff; }

.doc-status-dot { width: 10px; height: 10px; border-radius: 50%; }
.doc-status-dot.collected { background: var(--accent); box-shadow: 0 0 8px rgba(0, 201, 167, 0.4); }
.doc-status-dot.missing { background: var(--danger); box-shadow: 0 0 8px rgba(255, 107, 107, 0.4); }
.doc-status-dot.optional { background: var(--text-muted); opacity: 0.3; }

.doc-name { display: flex; align-items: center; gap: 8px; }
.req-badge { background: #fff1f2; color: #e11d48; font-size: 9px; font-weight: 800; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
.opt-badge { background: #f1f5f9; color: #64748b; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }

.doc-select, .doc-input { padding: 8px 10px; font-size: 12px; height: 36px; }
.doc-file-cell { display: flex; align-items: center; gap: 8px; }
.doc-file-link {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 10px; background: var(--primary-light); color: var(--primary);
    border-radius: 8px; font-size: 11px; font-weight: 700; transition: var(--transition);
}
.doc-file-link:hover { background: var(--primary); color: #fff; }
.doc-del-file {
    border: none; background: #fff1f2; color: #e11d48;
    width: 24px; height: 24px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: var(--transition);
}
.doc-del-file:hover { background: #e11d48; color: #fff; }

.doc-upload-label {
    width: 36px; height: 36px; border-radius: 10px;
    background: #f8fafc; border: 1.5px dashed var(--border-color);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted); cursor: pointer; transition: var(--transition);
}
.doc-upload-label:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.doc-file-input { display: none; }

.doc-save-cell { display: flex; align-items: center; gap: 12px; }
.doc-check-wrap { cursor: pointer; display: flex; align-items: center; }
.doc-checkbox {
    width: 20px; height: 20px; cursor: pointer;
    accent-color: var(--primary);
}

.section-badge {
    background: var(--primary-light); color: var(--primary);
    padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
</style>
CSS;

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
    <div class="page-header-right">
        <a href="index.php" class="btn-lms btn-outline" id="btn-back-docs">
          <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
  </div>

  <!-- Student Info Banner -->
  <div class="doc-student-banner mb-20">
    <?php if (!empty($student['profile_picture'])): ?>
      <div class="dsb-avatar" style="background: none; padding: 0; overflow: hidden; border: 2px solid var(--primary-light);">
        <img src="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($student['profile_picture']) ?>" 
             style="width: 100%; height: 100%; object-fit: cover;" 
             alt="<?= htmlspecialchars($student['full_name']) ?>">
      </div>
    <?php else: ?>
      <div class="dsb-avatar" style="background:<?= studentAvatarColor($student['full_name']) ?>">
        <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
      </div>
    <?php endif; ?>
    <div class="dsb-info">
      <div class="dsb-name"><?= htmlspecialchars($student['full_name']) ?></div>
      <div class="dsb-meta">
        <span><i class="fas fa-id-badge"></i> <?= htmlspecialchars($student['student_id']) ?></span>
        <span><i class="fas fa-layer-group"></i> Batch <?= htmlspecialchars($student['batch_number']) ?></span>
        <span><i class="fas fa-phone"></i> <?= htmlspecialchars($student['phone_number']) ?></span>
      </div>
    </div>
    <div class="dsb-status-box">
      <div class="dsb-progress-ring">
        <svg width="72" height="72" viewBox="0 0 72 72">
          <circle cx="36" cy="36" r="30" fill="none" stroke="#f1f5f9" stroke-width="8"/>
          <circle cx="36" cy="36" r="30" fill="none"
            stroke="<?= $overallStatus==='completed'?'#10b981':($overallStatus==='pending'?'#f59e0b':'#ef4444') ?>"
            stroke-width="8"
            stroke-dasharray="188.5"
            stroke-dashoffset="<?= 188.5 - (188.5 * $overallPct / 100) ?>"
            stroke-linecap="round"/>
        </svg>
        <div class="ring-pct"><?= $overallPct ?>%</div>
      </div>
      <div class="dsb-status-label">
        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Completion</div>
        <div style="font-size:14px;font-weight:800;color:var(--text-main);">
          <?= $reqDone ?> / <?= $reqTotal ?> <span style="font-size:11px;font-weight:500;color:#64748b;">Collected</span>
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

  <!-- Checklist sections -->
  <?php foreach ($groups as $groupName => $groupDocs): ?>
  <div class="card-lms mb-20">
    <div class="card-lms-header">
      <div class="card-lms-title">
        <i class="fas fa-folder-open" style="color:var(--primary);"></i>
        <?= htmlspecialchars($groupName) ?>
      </div>
      <?php
        $gTotal = count($groupDocs);
        $gDoneAll = count(array_filter(array_keys($groupDocs), fn($k) => !empty($docRow[$k.'_status'])));
      ?>
      <span class="section-badge">
        <?= $gDoneAll ?> / <?= $gTotal ?> collected
      </span>
    </div>
    <div class="card-lms-body" style="padding:0; overflow-x:auto;">
      <table class="doc-checklist-table">
        <thead>
          <tr>
            <th style="width:40px;"></th>
            <th>Document Description</th>
            <th style="width:120px;">Importance</th>
            <th style="width:130px;">Office/Center</th>
            <th style="width:150px;">Date Collected</th>
            <th style="width:180px;">Attached File</th>
            <th style="width:80px; text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <!-- We place forms here to be part of the DOM but hidden, to avoid invalid HTML nesting -->
          <div style="display:none;">
            <?php foreach ($groupDocs as $docKey => $def): ?>
              <?php $formId = "form-" . $docKey; ?>
              <form id="<?= $formId ?>" method="POST" action="manage.php?student_id=<?= $studentId ?>" enctype="multipart/form-data">
                  <input type="hidden" name="doc_key" value="<?= $docKey ?>">
                  <input type="hidden" name="doc_status" value="<?= !empty($docRow[$docKey . '_status'])?1:0 ?>" id="hidden-status-<?= $docKey ?>">
              </form>
            <?php endforeach; ?>
          </div>

          <?php foreach ($groupDocs as $docKey => $def): ?>
          <?php
            $isCollected = !empty($docRow[$docKey . '_status']);
            $collectedBy = $docRow[$docKey . '_collected_by'] ?? '';
            $collectedDate = $docRow[$docKey . '_date'] ?? '';
            $filePath    = $docRow[$docKey] ?? '';
            $formId      = "form-" . $docKey;
          ?>
          <tr id="doc-row-<?= $docKey ?>">
            <!-- Status Dot -->
            <td style="text-align:center;">
              <div class="doc-status-dot <?= $isCollected ? 'collected' : ($def['required'] ? 'missing' : 'optional') ?>"></div>
            </td>

            <!-- Doc Info -->
            <td>
              <div class="doc-name">
                <i class="fas <?= $def['icon'] ?>" style="color:<?= $isCollected?'var(--accent)':'#cbd5e1' ?>; font-size:16px;"></i>
                <span class="fw-700" style="font-size:14px;"><?= htmlspecialchars($def['label']) ?></span>
              </div>
            </td>

            <!-- Type -->
            <td>
              <?php if ($def['required']): ?>
                <span class="req-badge">Required</span>
              <?php else: ?>
                <span class="opt-badge">Optional</span>
              <?php endif; ?>
            </td>

            <!-- Office -->
            <td>
              <select name="collected_by" form="<?= $formId ?>" class="form-control-lms doc-select">
                <option value="">-- Select --</option>
                <?php foreach (['W1','W2','H1','H2'] as $office): ?>
                  <option value="<?= $office ?>" <?= $collectedBy === $office ? 'selected' : '' ?>><?= $office ?></option>
                <?php endforeach; ?>
              </select>
            </td>

            <!-- Date -->
            <td>
              <input type="date" name="collected_date" form="<?= $formId ?>" class="form-control-lms doc-input" value="<?= htmlspecialchars($collectedDate) ?>">
            </td>

            <!-- File Link / Upload -->
            <td>
              <div class="doc-file-cell">
                <?php if ($filePath): ?>
                  <a href="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($filePath) ?>" target="_blank" class="doc-file-link">
                    <i class="fas <?= str_ends_with($filePath,'.pdf') ? 'fa-file-pdf' : 'fa-file-image' ?>"></i>
                    <?= strtoupper(pathinfo($filePath, PATHINFO_EXTENSION)) ?>
                  </a>
                  <button type="submit" name="delete_file" form="<?= $formId ?>" value="1" class="doc-del-file" data-confirm="Remove file?"><i class="fas fa-trash-alt"></i></button>
                <?php else: ?>
                  <label class="doc-upload-label" title="Upload">
                    <i class="fas fa-plus"></i>
                    <input type="file" name="doc_file" id="file-<?= $docKey ?>" form="<?= $formId ?>" class="doc-file-input" onchange="handleFileChange('<?= $docKey ?>')">
                  </label>
                  <span id="file-name-<?= $docKey ?>" style="font-size:11px;color:#94a3b8;font-weight:500;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;vertical-align:middle;">Pending</span>
                <?php endif; ?>
              </div>
            </td>

            <!-- Save Action -->
            <td style="text-align:center;">
                <div class="doc-save-cell" style="justify-content:center;">
                    <input type="checkbox" class="doc-checkbox" id="check-<?= $docKey ?>" <?= $isCollected ? 'checked' : '' ?> onchange="syncHiddenStatus('<?= $docKey ?>')">
                    <button type="submit" form="<?= $formId ?>" class="btn-lms btn-primary btn-sm" style="padding: 8px 10px; border-radius:8px;">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Supporting Docs Card -->
  <div class="card-lms mt-40">
    <div class="card-lms-header">
      <div class="card-lms-title"><i class="fas fa-paperclip"></i> Other Supporting Documents</div>
      <span class="badge-lms info"><?= count($otherDocs) ?> Items</span>
    </div>
    <div class="card-lms-body">
      <form method="POST" enctype="multipart/form-data" class="mb-20 p-4" style="background:#f8fafc; border-radius:16px; border:1.5px dashed #cbd5e1;">
        <div class="row g-3 align-items-end">
          <div class="col-md-5">
            <div class="form-group-lms mb-0">
              <label>Document Title</label>
              <input type="text" name="other_label" class="form-control-lms" placeholder="e.g. Birth Certificate" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group-lms mb-0">
              <label>Select File</label>
              <input type="file" name="other_file" class="form-control-lms" required>
            </div>
          </div>
          <div class="col-md-3">
            <button type="submit" name="add_other" class="btn-primary-grad w-100">
              <i class="fas fa-cloud-upload-alt"></i> Upload Now
            </button>
          </div>
        </div>
      </form>

      <?php if (!empty($otherDocs)): ?>
      <div class="table-responsive">
        <table class="table-lms">
          <thead>
            <tr>
              <th>Document</th>
              <th>Uploaded On</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($otherDocs as $od): ?>
            <tr>
              <td class="fw-700"><?= htmlspecialchars($od['label']) ?></td>
              <td><?= date('M d, Y', strtotime($od['created_at'])) ?></td>
              <td>
                <div class="d-flex gap-10">
                    <a href="<?= BASE_URL ?>/assets/documents/<?= htmlspecialchars($od['file_path']) ?>" target="_blank" class="btn-lms btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
                    <form method="POST" onsubmit="return confirm('Remove?')">
                        <input type="hidden" name="del_other_id" value="<?= $od['id'] ?>">
                        <button type="submit" class="btn-lms btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
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
    return $colors[ord($name[0]??'A') % count($colors)];
}

$extraJS = <<<'JS'
<script>
function syncHiddenStatus(docKey) {
  const cb  = document.getElementById('check-' + docKey);
  const hid = document.getElementById('hidden-status-' + docKey);
  if (cb && hid) hid.value = cb.checked ? '1' : '0';
}
function handleFileChange(docKey) {
  const fileInput = document.getElementById('file-' + docKey);
  const nameDisplay = document.getElementById('file-name-' + docKey);
  const cb = document.getElementById('check-' + docKey);
  
  if (fileInput && fileInput.files.length > 0) {
    const fileName = fileInput.files[0].name;
    if (nameDisplay) {
      nameDisplay.innerText = fileName;
      nameDisplay.style.color = 'var(--primary)';
      nameDisplay.style.fontWeight = '700';
    }
    if (cb) { cb.checked = true; syncHiddenStatus(docKey); }
  }
}
</script>
JS;

require_once dirname(__DIR__, 2) . '/includes/footer.php';
?>

