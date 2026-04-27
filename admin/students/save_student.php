<?php
// =====================================================
// LEARN Management – Add Student POST Handler
// admin/students/save_student.php
// =====================================================

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/student_controller.php';
require_once __DIR__ . '/../../backend/document_controller.php';

// ── Guard: POST only, admin only ──────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_student_form.html');
    exit;
}

requireRole(ROLE_ADMIN);          // redirects to login if not authenticated

$pdo = getDBConnection();

// ─────────────────────────────────────────────────────
// 1. Collect & sanitise student fields
// ─────────────────────────────────────────────────────
$studentData = [
    'full_name'             => $_POST['full_name']             ?? '',
    'nic_number'            => $_POST['nic_number']            ?? '',
    'batch_number'          => $_POST['batch_number']          ?? '',
    'join_date'             => $_POST['join_date']             ?? '',
    'office_email'          => $_POST['office_email']          ?? '',
    'office_email_password' => $_POST['office_email_password'] ?? '',
    'personal_email'        => $_POST['personal_email']        ?? '',
    'phone_number'          => $_POST['telephone_number']      ?? '',   // form name → controller name
    'whatsapp_number'       => $_POST['whatsapp_number']       ?? '',
    'guardian_name'         => $_POST['guardian_name']         ?? '',
    'guardian_phone'        => $_POST['guardian_phone']        ?? '',
    'guardian_verified'     => isset($_POST['guardian_verified']) ? 1 : 0,
    'house_address'         => $_POST['house_address']         ?? '',
    'boarding_address'      => $_POST['boarding_address']      ?? '',
    'status'                => 'new_joined',
];

// ─────────────────────────────────────────────────────
// 2. Insert student row (uses existing addStudent())
// ─────────────────────────────────────────────────────
$result = addStudent($pdo, $studentData);

if (!$result['success']) {
    // Return to form with errors stored in session
    session_start();
    $_SESSION['form_errors']  = $result['errors'];
    $_SESSION['form_old']     = $studentData;
    header('Location: add_student_form.html');
    exit;
}

// Fetch the numeric PK for the newly inserted student
$studentRow = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$studentRow->execute([$result['student_id']]);
$studentDbId = (int) $studentRow->fetchColumn();

// ─────────────────────────────────────────────────────
// 3. Create the student_documents row (blank skeleton)
// ─────────────────────────────────────────────────────
getOrCreateDocRecord($pdo, $studentDbId);

// ─────────────────────────────────────────────────────
// 4. Document checklist — map form keys → DB column keys
// ─────────────────────────────────────────────────────
//
// Form input name pattern:
//   doc_{key}         → $_FILES  (file upload)
//   collected_{key}   → $_POST   (checkbox)
//   office_{key}      → $_POST   (H1/H2/W1/W2 select)
//   date_{key}        → $_POST   (date input)
//
// DB column pattern (from student_documents_table.sql):
//   {db_key}               – file path
//   {db_key}_status        – 0/1
//   {db_key}_collected_by  – ENUM
//   {db_key}_date          – DATE
//
// The form uses slightly different keys for OL/AL (ol_al → ol_results / al_results).
// We map them explicitly here so the rest of the code is uniform.

$docMap = [
    // form_key        => db_key
    'nic_front'        => 'nic_front',
    'nic_back'         => 'nic_back',
    'gs_jp'            => 'gs_jp_letter',
    'ol_al'            => 'ol_results',          // OL & AL combined in the form
    'slc'              => 'school_leaving_certificate',
    'cv'               => 'cv',
    'bank_passbook'    => 'bank_passbook',
    'reference_letter' => 'reference_letter',
];

$docErrors = [];    // collect non-fatal upload problems

foreach ($docMap as $formKey => $dbKey) {

    // ── a) Was a file submitted? ────────────────────
    $fileUploaded = false;
    $savedPath    = null;

    if (
        isset($_FILES['doc_' . $formKey]) &&
        $_FILES['doc_' . $formKey]['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $uploadResult = uploadDocumentFile(
            $_FILES['doc_' . $formKey],
            $dbKey,
            $studentDbId
        );

        if ($uploadResult['success']) {
            $fileUploaded = true;
            $savedPath    = $uploadResult['path'];
        } else {
            $docErrors[] = $uploadResult['error'] . " ({$dbKey})";
        }
    }

    // ── b) Tracking fields ──────────────────────────
    $isCollected = isset($_POST['collected_' . $formKey]);
    $office      = $_POST['office_' . $formKey] ?? '';
    $date        = $_POST['date_'   . $formKey] ?? '';

    // Validate office enum (extra server-side guard)
    $validOffices = ['H1', 'H2', 'W1', 'W2'];
    $office       = in_array($office, $validOffices, true) ? $office : null;

    // Validate date format YYYY-MM-DD
    $date = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) ? $date : null;

    // Only save tracking if collected is checked OR a file was uploaded
    if ($isCollected || $fileUploaded) {
        saveDocTracking($pdo, $studentDbId, $dbKey, [
            'status'       => $isCollected ? 1 : 0,
            'collected_by' => $office,
            'date'         => $date,
            'file_path'    => $savedPath,   // null = don't overwrite existing path
        ]);
    }
}

// ─────────────────────────────────────────────────────
// 5. Redirect with success (or partial-success) message
// ─────────────────────────────────────────────────────
session_start();

if (empty($docErrors)) {
    $_SESSION['flash_success'] = 'Student <strong>' . htmlspecialchars($studentData['full_name']) . '</strong> added successfully. (ID: ' . htmlspecialchars($result['student_id']) . ')';
} else {
    // Student was saved but some document uploads failed
    $_SESSION['flash_warning'] = 'Student added, but some document uploads failed:<br>' . implode('<br>', array_map('htmlspecialchars', $docErrors));
}

header('Location: ../../admin/students/students.php');
exit;
