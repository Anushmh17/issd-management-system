<?php
// =====================================================
// LEARN Management - Student Controller
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// -------------------------------------------------------
// Generate a unique Student ID  e.g. STU-2026-0001
// -------------------------------------------------------
function generateStudentId(PDO $pdo): string {
    $year = date('Y');
    $prefix = 'STU-' . $year . '-';
    $stmt = $pdo->prepare(
        "SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last) {
        $seq = (int) substr($last, strlen($prefix));
        $seq++;
    } else {
        $seq = 1;
    }
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// -------------------------------------------------------
// Validate student fields
// -------------------------------------------------------
function validateStudentFields(array $data): array {
    $errors = [];
    if (empty(trim($data['full_name'])))    $errors[] = 'Full name is required.';
    if (empty(trim($data['nic_number'])))   $errors[] = 'NIC number is required.';
    if (empty(trim($data['batch_number']))) $errors[] = 'Batch number is required.';
    if (empty(trim($data['phone_number']))) $errors[] = 'Phone number is required.';
    if (!empty($data['office_email']) && !filter_var($data['office_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Office email format is invalid.';
    }
    if (!empty($data['personal_email']) && !filter_var($data['personal_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Personal email format is invalid.';
    }
    return $errors;
}

// -------------------------------------------------------
// Add Student
// -------------------------------------------------------
function addStudent(PDO $pdo, array $data): array {
    $errors = validateStudentFields($data);
    if ($errors) return ['success' => false, 'errors' => $errors];

    $studentId = generateStudentId($pdo);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO students
              (student_id, full_name, nic_number, batch_number, join_date,
               office_email, office_email_password, personal_email,
               phone_number, whatsapp_number, guardian_name, guardian_phone,
               house_address, boarding_address, status)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId,
            trim($data['full_name']),
            trim($data['nic_number']),
            trim($data['batch_number']),
            !empty($data['join_date']) ? $data['join_date'] : null,
            !empty($data['office_email']) ? trim($data['office_email']) : null,
            !empty($data['office_email_password']) ? trim($data['office_email_password']) : null,
            !empty($data['personal_email']) ? trim($data['personal_email']) : null,
            trim($data['phone_number']),
            !empty($data['whatsapp_number']) ? trim($data['whatsapp_number']) : null,
            !empty($data['guardian_name']) ? trim($data['guardian_name']) : null,
            !empty($data['guardian_phone']) ? trim($data['guardian_phone']) : null,
            !empty($data['house_address']) ? trim($data['house_address']) : null,
            !empty($data['boarding_address']) ? trim($data['boarding_address']) : null,
            $data['status'] ?? 'new_joined',
        ]);
        return ['success' => true, 'student_id' => $studentId];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return ['success' => false, 'errors' => ['NIC number or Student ID already exists.']];
        }
        error_log('addStudent error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to add student. Please try again.']];
    }
}

// -------------------------------------------------------
// Update Student
// -------------------------------------------------------
function updateStudent(PDO $pdo, int $id, array $data): array {
    $errors = validateStudentFields($data);
    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        $stmt = $pdo->prepare("
            UPDATE students SET
              full_name             = ?,
              nic_number            = ?,
              batch_number          = ?,
              join_date             = ?,
              office_email          = ?,
              office_email_password = ?,
              personal_email        = ?,
              phone_number          = ?,
              whatsapp_number       = ?,
              guardian_name         = ?,
              guardian_phone        = ?,
              house_address         = ?,
              boarding_address      = ?,
              status                = ?
            WHERE id = ?
        ");
        $stmt->execute([
            trim($data['full_name']),
            trim($data['nic_number']),
            trim($data['batch_number']),
            !empty($data['join_date']) ? $data['join_date'] : null,
            !empty($data['office_email']) ? trim($data['office_email']) : null,
            !empty($data['office_email_password']) ? trim($data['office_email_password']) : null,
            !empty($data['personal_email']) ? trim($data['personal_email']) : null,
            trim($data['phone_number']),
            !empty($data['whatsapp_number']) ? trim($data['whatsapp_number']) : null,
            !empty($data['guardian_name']) ? trim($data['guardian_name']) : null,
            !empty($data['guardian_phone']) ? trim($data['guardian_phone']) : null,
            !empty($data['house_address']) ? trim($data['house_address']) : null,
            !empty($data['boarding_address']) ? trim($data['boarding_address']) : null,
            $data['status'] ?? 'new_joined',
            $id,
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('updateStudent error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to update student. Please try again.']];
    }
}

// -------------------------------------------------------
// Soft-delete by setting status (returns bool)
// -------------------------------------------------------
function softDeleteStudent(PDO $pdo, int $id): bool {
    try {
        $pdo->prepare("UPDATE students SET status = 'dropout' WHERE id = ?")
            ->execute([$id]);
        return true;
    } catch (PDOException $e) {
        error_log('softDeleteStudent error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Permanently delete a student
// -------------------------------------------------------
function deleteStudent(PDO $pdo, int $id): bool {
    try {
        $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        error_log('deleteStudent error: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Get single student by ID
// -------------------------------------------------------
function getStudentById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// -------------------------------------------------------
// Get paginated + filtered students list
// Returns: ['students'=>[], 'total'=>int, 'pages'=>int]
// -------------------------------------------------------
function getStudentsList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 15): array {
    $where  = [];
    $params = [];

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $where[] = "(full_name LIKE ? OR student_id LIKE ? OR nic_number LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $batch = trim($filters['batch'] ?? '');
    if ($batch !== '') {
        $where[] = "batch_number = ?";
        $params[] = $batch;
    }

    $status = trim($filters['status'] ?? '');
    if ($status !== '') {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM students {$whereSQL}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $pages  = max(1, (int) ceil($total / $perPage));
    $page   = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT * FROM students {$whereSQL}
        ORDER BY created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    return compact('students', 'total', 'pages', 'page');
}

// -------------------------------------------------------
// Get all distinct batch numbers for filter dropdown
// -------------------------------------------------------
function getAllBatches(PDO $pdo): array {
    return $pdo->query("SELECT DISTINCT batch_number FROM students ORDER BY batch_number ASC")
               ->fetchAll(PDO::FETCH_COLUMN);
}
