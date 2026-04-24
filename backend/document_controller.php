<?php
// =====================================================
// LEARN Management - Document Controller
// backend/document_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// -------------------------------------------------------
// Document definitions — single source of truth
// -------------------------------------------------------
function getDocumentDefinitions(): array {
    return [
        // key => [label, required, icon, accept_hint]
        'nic_front' => [
            'label'    => 'NIC (Front)',
            'required' => true,
            'icon'     => 'fa-id-card',
            'group'    => 'Identity Documents',
        ],
        'nic_back' => [
            'label'    => 'NIC (Back)',
            'required' => true,
            'icon'     => 'fa-id-card',
            'group'    => 'Identity Documents',
        ],
        'gs_jp_letter' => [
            'label'    => 'GS / JP Letter',
            'required' => true,
            'icon'     => 'fa-file-signature',
            'group'    => 'Official Letters',
        ],
        'ol_results' => [
            'label'    => 'O/L Results',
            'required' => true,
            'icon'     => 'fa-graduation-cap',
            'group'    => 'Academic Documents',
        ],
        'al_results' => [
            'label'    => 'A/L Results',
            'required' => true,
            'icon'     => 'fa-graduation-cap',
            'group'    => 'Academic Documents',
        ],
        'school_leaving_certificate' => [
            'label'    => 'School Leaving Certificate',
            'required' => true,
            'icon'     => 'fa-certificate',
            'group'    => 'Academic Documents',
        ],
        'registration_fee_certificate' => [
            'label'    => 'Registration Fee Certificate',
            'required' => true,
            'icon'     => 'fa-receipt',
            'group'    => 'Financial Documents',
        ],
        'cv' => [
            'label'    => 'CV / Resume',
            'required' => false,
            'icon'     => 'fa-file-user',
            'group'    => 'Optional Documents',
        ],
        'bank_passbook' => [
            'label'    => 'Bank Passbook',
            'required' => false,
            'icon'     => 'fa-landmark',
            'group'    => 'Optional Documents',
        ],
        'reference_letter' => [
            'label'    => 'Reference Letter',
            'required' => false,
            'icon'     => 'fa-envelope-open-text',
            'group'    => 'Optional Documents',
        ],
    ];
}

// -------------------------------------------------------
// Allowed upload types + max size
// -------------------------------------------------------
define('DOC_ALLOWED_TYPES', ['application/pdf','image/jpeg','image/jpg','image/png']);
define('DOC_ALLOWED_EXTS',  ['pdf','jpg','jpeg','png']);
define('DOC_MAX_SIZE',      10 * 1024 * 1024); // 10 MB
define('DOC_UPLOAD_DIR',    BASE_PATH . '/assets/documents/');
define('DOC_UPLOAD_URL',    BASE_URL  . '/assets/documents/');

// -------------------------------------------------------
// Ensure the upload directory exists
// -------------------------------------------------------
function ensureDocUploadDir(): void {
    $dir = DOC_UPLOAD_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// -------------------------------------------------------
// Get or create the document row for a student
// -------------------------------------------------------
function getOrCreateDocRecord(PDO $pdo, int $studentId): array {
    $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->prepare("INSERT INTO student_documents (student_id) VALUES (?)")
            ->execute([$studentId]);
        $row = $pdo->prepare("SELECT * FROM student_documents WHERE student_id = ?");
        $row->execute([$studentId]);
        $row = $row->fetch();
    }
    return $row;
}

// -------------------------------------------------------
// Upload a single document file
// Returns ['success'=>bool, 'path'=>string|null, 'error'=>string]
// -------------------------------------------------------
function uploadDocumentFile(array $file, string $docKey, int $studentId): array {
    ensureDocUploadDir();

    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'path' => null, 'error' => 'No file selected.'];
        }
        return ['success' => false, 'path' => null, 'error' => 'Upload error code: ' . $file['error']];
    }

    // Size check
    if ($file['size'] > DOC_MAX_SIZE) {
        return ['success' => false, 'path' => null, 'error' => 'File exceeds 10 MB limit.'];
    }

    // Extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, DOC_ALLOWED_EXTS, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file type. Only PDF, JPG, PNG allowed.'];
    }

    // MIME check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, DOC_ALLOWED_TYPES, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid MIME type detected.'];
    }

    // Build a unique filename — no overwrite
    $safeName  = 'STU' . $studentId . '_' . $docKey . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath  = DOC_UPLOAD_DIR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to save file. Check server permissions.'];
    }

    return ['success' => true, 'path' => $safeName, 'error' => ''];
}

// -------------------------------------------------------
// Save tracking fields (status, collected_by, date) for one doc
// -------------------------------------------------------
function saveDocTracking(PDO $pdo, int $studentId, string $docKey, array $data): bool {
    // Validate key to prevent SQL injection (whitelist)
    $allowed = array_keys(getDocumentDefinitions());
    if (!in_array($docKey, $allowed, true)) return false;

    $status      = isset($data['status']) ? (int)(bool)$data['status'] : 0;
    $collectedBy = in_array($data['collected_by'] ?? '', ['W1','W2','H1','H2']) ? $data['collected_by'] : null;
    $date        = !empty($data['date']) ? $data['date'] : null;
    $filePath    = $data['file_path'] ?? null; // null = don't update file

    // Build SET clause dynamically using safe column names
    $sets   = [];
    $params = [];

    $sets[]   = "`{$docKey}_status` = ?";
    $params[] = $status;

    $sets[]   = "`{$docKey}_collected_by` = ?";
    $params[] = $collectedBy;

    $sets[]   = "`{$docKey}_date` = ?";
    $params[] = $date;

    if ($filePath !== null) {
        $sets[]   = "`{$docKey}` = ?";
        $params[] = $filePath;
    }

    $params[] = $studentId;

    $sql = "UPDATE student_documents SET " . implode(', ', $sets) . " WHERE student_id = ?";

    try {
        $pdo->prepare($sql)->execute($params);
        return true;
    } catch (PDOException $e) {
        error_log('saveDocTracking error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Compute document completion status for a student
// Returns: 'completed' | 'pending' | 'missing'
// -------------------------------------------------------
function computeDocStatus(array $docRow): string {
    $defs = getDocumentDefinitions();
    $required = array_keys(array_filter($defs, fn($d) => $d['required']));
    $total    = count($required);
    $collected = 0;

    foreach ($required as $key) {
        if (!empty($docRow[$key . '_status'])) $collected++;
    }

    if ($collected === 0)     return 'missing';
    if ($collected < $total)  return 'pending';
    return 'completed';
}

// -------------------------------------------------------
// Get document status for multiple students (bulk)
// Returns: [student_id => 'missing'|'pending'|'completed']
// -------------------------------------------------------
function getBulkDocStatus(PDO $pdo, array $studentIds): array {
    if (empty($studentIds)) return [];

    $in   = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE student_id IN ($in)");
    $stmt->execute($studentIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['student_id']] = computeDocStatus($row);
    }
    // Students with no doc row = missing
    foreach ($studentIds as $sid) {
        if (!isset($map[$sid])) $map[$sid] = 'missing';
    }
    return $map;
}

// -------------------------------------------------------
// Render the doc status badge HTML
// -------------------------------------------------------
function renderDocStatusBadge(string $status): string {
    return match($status) {
        'completed' => '<span class="doc-badge completed"><i class="fas fa-circle-check"></i> Completed</span>',
        'pending'   => '<span class="doc-badge pending"><i class="fas fa-clock"></i> Pending</span>',
        default     => '<span class="doc-badge missing"><i class="fas fa-circle-xmark"></i> Missing</span>',
    };
}

// -------------------------------------------------------
// Delete old file from disk (safe)
// -------------------------------------------------------
function deleteDocFile(string $filename): void {
    $path = DOC_UPLOAD_DIR . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}
// -------------------------------------------------------
// Get other supporting documents for a student
// -------------------------------------------------------
function getOtherStudentDocs(PDO $pdo, int $studentId): array {
    $stmt = $pdo->prepare("SELECT * FROM student_other_documents WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

// -------------------------------------------------------
// Save a new other supporting document
// -------------------------------------------------------
function saveOtherDoc(PDO $pdo, array $data): bool {
    $sql = "INSERT INTO student_other_documents (student_id, label, file_path, collected_by, collected_date) VALUES (?, ?, ?, ?, ?)";
    try {
        $pdo->prepare($sql)->execute([
            $data['student_id'],
            $data['label'],
            $data['file_path'],
            $data['collected_by'],
            $data['collected_date']
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('saveOtherDoc error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Delete an other supporting document
// -------------------------------------------------------
function deleteOtherDoc(PDO $pdo, int $docId): bool {
    $stmt = $pdo->prepare("SELECT file_path FROM student_other_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $file = $stmt->fetchColumn();
    if ($file) {
        deleteDocFile($file);
    }
    return $pdo->prepare("DELETE FROM student_other_documents WHERE id = ?")->execute([$docId]);
}
