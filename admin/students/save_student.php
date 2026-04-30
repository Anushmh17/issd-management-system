<?php
// =====================================================
// ISSD Management "" Add Student POST Handler
// admin/students/save_student.php
// =====================================================

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/student_controller.php';
require_once __DIR__ . '/../../backend/document_controller.php';

// â"€â"€ Guard: POST only, admin only â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit;
}

requireRole(ROLE_ADMIN);          // redirects to login if not authenticated

$pdo = getDBConnection();

// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
// 1. Collect & sanitise student fields
// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
$studentData = [
    'full_name'             => $_POST['full_name']             ?? '',
    'nic_number'            => $_POST['nic_number']            ?? '',
    'batch_number'          => $_POST['batch_number']          ?? '',
    'join_date'             => $_POST['join_date']             ?? '',
    'office_email'          => $_POST['office_email']          ?? '',
    'office_email_password' => $_POST['office_email_password'] ?? '',
    'personal_email'        => $_POST['personal_email']        ?? '',
    'phone_number'          => $_POST['telephone_number']      ?? '',   // form name â†' controller name
    'whatsapp_number'       => $_POST['whatsapp_number']       ?? '',
    'guardian_name'         => $_POST['guardian_name']         ?? '',
    'guardian_phone'        => $_POST['guardian_phone']        ?? '',
    'guardian_verified'     => isset($_POST['guardian_verified']) ? 1 : 0,
    'house_address'         => $_POST['house_address']         ?? '',
    'boarding_address'      => $_POST['boarding_address']      ?? '',
    'status'                => 'new_joined',
];

// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
// 2. Insert student row (uses existing addStudent())
// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
$result = addStudent($pdo, $studentData);

if (!$result['success']) {
    // Return to form with errors stored in session
    session_start();
    $_SESSION['form_errors']  = $result['errors'];
    $_SESSION['form_old']     = $studentData;
    header('Location: add.php');
    exit;
}

// Fetch the numeric PK for the newly inserted student
$studentRow = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$studentRow->execute([$result['student_id']]);
$studentDbId = (int) $studentRow->fetchColumn();

// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
// 3. Create the student_documents row (blank skeleton)
// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
getOrCreateDocRecord($pdo, $studentDbId);

// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
// 4. Document checklist "" map form keys â†' DB column keys
// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
//
// Form input name pattern:
//   doc_{key}         â†' $_FILES  (file upload)
//   collected_{key}   â†' $_POST   (checkbox)
//   office_{key}      â†' $_POST   (H1/H2/W1/W2 select)
//   date_{key}        â†' $_POST   (date input)
//
// DB column pattern (from student_documents_table.sql):
//   {db_key}               "" file path
//   {db_key}_status        "" 0/1
//   {db_key}_collected_by  "" ENUM
//   {db_key}_date          "" DATE
//
// The form uses slightly different keys for OL/AL (ol_al â†' ol_results / al_results).
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

    // â"€â"€ a) Was a file submitted? â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

    // â"€â"€ b) Tracking fields â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
// 5. Redirect with success (or partial-success) message
// â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
session_start();

if (empty($docErrors)) {
    $_SESSION['flash_success'] = 'Student <strong>' . htmlspecialchars($studentData['full_name']) . '</strong> added successfully. (ID: ' . htmlspecialchars($result['student_id']) . ')';
} else {
    // Student was saved but some document uploads failed
    $_SESSION['flash_warning'] = 'Student added, but some document uploads failed:<br>' . implode('<br>', array_map('htmlspecialchars', $docErrors));
}

header('Location: index.php');
exit;

