<?php
// =====================================================
// LEARN Management - Assignment Controller
// backend/assignment_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

define('ASSIGNMENT_DIR', BASE_PATH . '/assets/assignments/');
define('ASSIGNMENT_URL', BASE_URL  . '/assets/assignments/');
define('ASSIGNMENT_MAX_SIZE', 15 * 1024 * 1024); // 15 MB
define('ASSIGNMENT_EXTS', ['pdf','doc','docx','zip','rar']);

function ensureAssignmentDir() {
    if (!is_dir(ASSIGNMENT_DIR)) mkdir(ASSIGNMENT_DIR, 0755, true);
}

// -------------------------------------------------------
// Upload File Helper
// -------------------------------------------------------
function uploadAssignmentFile(array $file, string $prefix = 'ASM'): array {
    ensureAssignmentDir();
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return ['success' => false, 'path' => null, 'error' => ''];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    if ($file['size'] > ASSIGNMENT_MAX_SIZE) return ['success' => false, 'error' => 'File exceeds 15 MB limit.'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ASSIGNMENT_EXTS, true)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowable types: PDF, DOCX, ZIP, RAR.'];
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], ASSIGNMENT_DIR . $filename)) {
        return ['success' => true, 'path' => $filename];
    }
    return ['success' => false, 'error' => 'Failed to save file.'];
}

// -------------------------------------------------------
// Lecturer: Add Assignment
// -------------------------------------------------------
function addAssignment(PDO $pdo, int $lecturerId, array $d, ?array $file = null): array {
    $errors = [];
    if (empty(trim($d['course_id'] ?? ''))) $errors[] = 'Course is required.';
    if (empty(trim($d['title'] ?? '')))     $errors[] = 'Title is required.';
    if (empty(trim($d['deadline'] ?? '')))  $errors[] = 'Deadline is required.';

    if ($errors) return ['success' => false, 'errors' => $errors];

    $filePath = null;
    if ($file && !empty($file['name'])) {
        $up = uploadAssignmentFile($file, 'ASM');
        if (!$up['success']) return ['success' => false, 'errors' => [$up['error']]];
        $filePath = $up['path'];
    }

    try {
        $pdo->prepare("
            INSERT INTO assignments (course_id, lecturer_id, title, description, file, deadline)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $d['course_id'], $lecturerId, trim($d['title']), trim($d['description'] ?? ''),
            $filePath, $d['deadline']
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('addAssignment: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to create assignment.']];
    }
}

// -------------------------------------------------------
// Lecturer: Get Assignments
// -------------------------------------------------------
function getLecturerAssignments(PDO $pdo, int $lecturerId): array {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code,
               (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) as submission_count
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.lecturer_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$lecturerId]);
    return $stmt->fetchAll();
}

function getLecturerCourses(PDO $pdo, int $lecturerId): array {
    $stmt = $pdo->prepare("
        SELECT c.id, c.course_name, c.course_code
        FROM course_assignments ca
        JOIN courses c ON ca.course_id = c.id
        WHERE ca.lecturer_id = ? AND c.status = 'active'
    ");
    $stmt->execute([$lecturerId]);
    return $stmt->fetchAll();
}

function getAssignmentByIdAndLecturer(PDO $pdo, int $id, int $lecturerId): ?array {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND a.lecturer_id = ?
    ");
    $stmt->execute([$id, $lecturerId]);
    return $stmt->fetch() ?: null;
}

// -------------------------------------------------------
// Lecturer: Get Submissions for an Assignment
// -------------------------------------------------------
function getAssignmentSubmissions(PDO $pdo, int $assignmentId): array {
    $stmt = $pdo->prepare("
        SELECT s.*, st.full_name, st.student_id as student_reg
        FROM assignment_submissions s
        JOIN students st ON s.student_id = st.id
        WHERE s.assignment_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$assignmentId]);
    return $stmt->fetchAll();
}

function gradeSubmission(PDO $pdo, int $submissionId, array $d): array {
    try {
        $pdo->prepare("UPDATE assignment_submissions SET marks = ?, feedback = ? WHERE id = ?")
            ->execute([$d['marks'] ?? null, $d['feedback'] ?? null, $submissionId]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Failed to grade submission.'];
    }
}

// -------------------------------------------------------
// Student: Get Assignments
// -------------------------------------------------------
function getStudentAssignments(PDO $pdo, int $studentId): array {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code,
               s.id as submission_id, s.submitted_at, s.marks, s.remarks as feedback
        FROM assignments a
        JOIN student_courses sc ON a.course_id = sc.course_id
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE sc.student_id = ? AND sc.status IN ('ongoing', 'completed')
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$studentId, $studentId]);
    return $stmt->fetchAll();
}

function getAssignmentForStudent(PDO $pdo, int $assignmentId, int $studentId): ?array {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name,
               s.id as submission_id, s.submitted_at, s.submission_file, s.marks, s.feedback
        FROM assignments a
        JOIN student_courses sc ON a.course_id = sc.course_id
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE a.id = ? AND sc.student_id = ? AND sc.status IN ('ongoing', 'completed')
    ");
    $stmt->execute([$studentId, $assignmentId, $studentId]);
    return $stmt->fetch() ?: null;
}

// -------------------------------------------------------
// Student: Submit Assignment
// -------------------------------------------------------
function submitAssignment(PDO $pdo, int $studentId, int $assignmentId, array $file): array {
    if (empty($file['name'])) return ['success' => false, 'errors' => ['File is required to submit.']];
    
    $up = uploadAssignmentFile($file, 'SUB_' . $studentId);
    if (!$up['success']) return ['success' => false, 'errors' => [$up['error']]];

    try {
        $pdo->prepare("
            INSERT INTO assignment_submissions (assignment_id, student_id, submission_file)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE submission_file = VALUES(submission_file), submitted_at = CURRENT_TIMESTAMP
        ")->execute([$assignmentId, $studentId, $up['path']]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('submitAssignment: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to submit assignment.']];
    }
}
