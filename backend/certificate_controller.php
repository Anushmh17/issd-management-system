<?php
// =====================================================
// ISSD Management - Certificate Controller
// backend/certificate_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

define('INTERN_DOCS_DIR', BASE_PATH . '/assets/intern_docs/');
define('INTERN_DOCS_URL', BASE_URL  . '/assets/intern_docs/');

function ensureInternDocsDir() {
    if (!is_dir(INTERN_DOCS_DIR)) mkdir(INTERN_DOCS_DIR, 0755, true);
}

// -------------------------------------------------------
// Add Certificate & Complete Student
// -------------------------------------------------------
function addCertificate(PDO $pdo, array $d, ?array $file = null): array {
    $studentId = (int)($d['student_id'] ?? 0);
    $certNum   = trim($d['certificate_number'] ?? '');
    $issueDate = trim($d['issue_date'] ?? '');
    $isProvided = trim($d['is_provided'] ?? 'no');

    $errors = [];
    if (!$studentId) $errors[] = "Student is required.";
    if (!$certNum)   $errors[] = "Certificate number is required.";
    if (!$issueDate) $errors[] = "Issue date is required.";

    // Check if certificate num exists
    $stmt = $pdo->prepare("SELECT id FROM certificates WHERE certificate_number = ?");
    $stmt->execute([$certNum]);
    if ($stmt->fetchColumn()) $errors[] = "Certificate number already exists.";

    // Check if student already has a certificate
    $stmt = $pdo->prepare("SELECT id FROM certificates WHERE student_id = ?");
    $stmt->execute([$studentId]);
    if ($stmt->fetchColumn()) $errors[] = "This student already has a certificate on record.";

    if ($errors) return ['success' => false, 'errors' => $errors];

    $filePath = null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        ensureInternDocsDir();
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'doc', 'docx', 'zip', 'jpg', 'png'])) {
            $filename = 'INTERN_' . $studentId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], INTERN_DOCS_DIR . $filename)) {
                $filePath = $filename;
            } else {
                return ['success' => false, 'errors' => ['Failed to save intern document.']];
            }
        } else {
            return ['success' => false, 'errors' => ['Invalid document type.']];
        }
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO certificates (student_id, certificate_number, issue_date, is_provided, intern_document)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$studentId, $certNum, $issueDate, $isProvided, $filePath]);

        // Mark student as completed
        $pdo->prepare("UPDATE students SET status = 'completed' WHERE id = ?")->execute([$studentId]);
        
        // Also update any ongoing course enrollments for this student to 'completed'
        $pdo->prepare("UPDATE student_courses SET status = 'completed' WHERE student_id = ? AND status = 'ongoing'")->execute([$studentId]);
        // Same for generic enrollments table if exists
        $pdo->prepare("UPDATE enrollments SET status = 'completed' WHERE student_id = ? AND status = 'active'")->execute([$studentId]);

        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("addCertificate: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to add certificate.']];
    }
}

// -------------------------------------------------------
// Get Eligible Students
// -------------------------------------------------------
function getEligibleStudentsForCertificate(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT s.id, s.full_name, s.student_id as student_reg
        FROM students s
        LEFT JOIN certificates c ON s.id = c.student_id
        WHERE c.id IS NULL AND s.status != 'dropout'
        ORDER BY s.full_name ASC
    ");
    return $stmt->fetchAll();
}

// -------------------------------------------------------
// Get Certificates
// -------------------------------------------------------
function getCertificatesList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 15): array {
    $where  = [];
    $params = [];

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $where[]  = "(c.certificate_number LIKE ? OR s.full_name LIKE ? OR s.student_id LIKE ?)";
        $like = "%{$search}%";
        $params = array_merge($params, [$like,$like,$like]);
    }

    $provided = trim($filters['is_provided'] ?? '');
    if ($provided !== '') {
        $where[]  = "c.is_provided = ?";
        $params[] = $provided;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM certificates c 
        JOIN students s ON c.student_id = s.id 
        {$whereSQL}
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT c.*, s.full_name, s.student_id as student_reg
        FROM certificates c
        JOIN students s ON c.student_id = s.id
        {$whereSQL}
        ORDER BY c.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $certs = $stmt->fetchAll();

    return compact('certs','total','pages','page');
}

// -------------------------------------------------------
// Toggle is_provided
// -------------------------------------------------------
function toggleCertificateProvided(PDO $pdo, int $id): bool {
    try {
        $pdo->prepare("
            UPDATE certificates 
            SET is_provided = IF(is_provided = 'yes', 'no', 'yes') 
            WHERE id = ?
        ")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

