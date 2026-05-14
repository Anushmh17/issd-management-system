<?php
// =====================================================
// ISSD Management - Assignment Controller
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

    // H2: add MIME type validation consistent with other upload functions
    $allowedMimes = ['application/pdf','application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'application/zip','application/x-zip-compressed',
                     'application/x-rar-compressed','application/octet-stream'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedMimes, true)) {
        return ['success' => false, 'error' => 'Invalid MIME type detected. Only PDF, DOCX, ZIP, RAR allowed.'];
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
    if (empty(trim($d['due_date'] ?? '')))  $errors[] = 'Due date is required.';

    if ($errors) return ['success' => false, 'errors' => $errors];

    $filePath = null;
    if ($file && !empty($file['name'])) {
        $up = uploadAssignmentFile($file, 'ASM');
        if (!$up['success']) return ['success' => false, 'errors' => [$up['error']]];
        $filePath = $up['path'];
    }

    try {
        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) $pdo->beginTransaction();
        $pdo->prepare("
            INSERT INTO assignments (course_id, lecturer_id, title, description, file, due_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $d['course_id'], $lecturerId, trim($d['title']), trim($d['description'] ?? ''),
            $filePath, $d['due_date']
        ]);
        if (!$inTransaction) $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        if (!$inTransaction && $pdo->inTransaction()) $pdo->rollBack();
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

function gradeSubmission(PDO $pdo, int $submissionId, int $lecturerId, array $d): array {
    try {
        // H3: verify that the submission belongs to an assignment owned by this lecturer
        $check = $pdo->prepare("
            SELECT s.id 
            FROM assignment_submissions s
            JOIN assignments a ON s.assignment_id = a.id
            WHERE s.id = ? AND a.lecturer_id = ?
            LIMIT 1
        ");
        $check->execute([$submissionId, $lecturerId]);
        if (!$check->fetch()) {
            return ['success' => false, 'error' => 'Unauthorized: You do not own this assignment.'];
        }

        $pdo->prepare("UPDATE assignment_submissions SET marks = ?, feedback = ? WHERE id = ?")
            ->execute([$d['marks'] ?? null, $d['feedback'] ?? null, $submissionId]);

        // --- Notification Sync (Student) ---
        $info = $pdo->prepare("
            SELECT a.title, st.user_id 
            FROM assignment_submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN students st ON s.student_id = st.id
            WHERE s.id = ?
        ");
        $info->execute([$submissionId]);
        $subInfo = $info->fetch();
        if ($subInfo && $subInfo['user_id']) {
            require_once __DIR__ . '/notification_controller.php';
            $title = "Assignment Graded: " . $subInfo['title'];
            $msg = "Your assignment has been graded. Marks: " . ($d['marks'] ?? 'N/A');
            $link = BASE_URL . "/frontend/student/assignments/index.php";
            addNotification($pdo, (string)$subInfo['user_id'], 'system', $title, $msg, $link);
        }

        return ['success' => true];
    } catch (PDOException $e) {
        error_log('gradeSubmission: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to grade submission.'];
    }
}


// -------------------------------------------------------
// Student: Get Assignments
// -------------------------------------------------------
function getStudentAssignments(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name, c.course_code,
               s.id as submission_id, s.submitted_at, s.marks, s.feedback
        FROM assignments a
        JOIN student_courses sc ON a.course_id = sc.course_id
        JOIN students st ON sc.student_id = st.id
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE st.user_id = ? AND sc.status IN ('ongoing', 'completed')
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}

function getAssignmentForStudent(PDO $pdo, int $assignmentId, int $userId): ?array {
    $stmt = $pdo->prepare("
        SELECT a.*, c.course_name,
               s.id as submission_id, s.submitted_at, s.submission_file, s.marks, s.feedback
        FROM assignments a
        JOIN student_courses sc ON a.course_id = sc.course_id
        JOIN students st ON sc.student_id = st.id
        JOIN courses c ON a.course_id = c.id
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE a.id = ? AND st.user_id = ? AND sc.status IN ('ongoing', 'completed')
    ");
    $stmt->execute([$userId, $assignmentId, $userId]);
    return $stmt->fetch() ?: null;
}

// -------------------------------------------------------
// Student: Submit Assignment
// -------------------------------------------------------
function submitAssignment(PDO $pdo, int $studentId, int $assignmentId, array $file): array {
    if (empty($file['name'])) return ['success' => false, 'errors' => ['File is required to submit.']];

    try {
        // H5: verify that the student is actually enrolled in the course that owns this assignment
        $check = $pdo->prepare("
            SELECT a.id 
            FROM assignments a
            JOIN student_courses sc ON a.course_id = sc.course_id
            WHERE a.id = ? AND sc.student_id = ? AND sc.status IN ('ongoing', 'completed')
            LIMIT 1
        ");
        $check->execute([$assignmentId, $studentId]);
        if (!$check->fetch()) {
            return ['success' => false, 'errors' => ['Unauthorized: You are not enrolled in this course.']];
        }

        $up = uploadAssignmentFile($file, 'SUB_' . $studentId);
        if (!$up['success']) return ['success' => false, 'errors' => [$up['error']]];

        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) $pdo->beginTransaction();

        // H4: delete old submission file from disk before overwriting the DB record
        $existing = $pdo->prepare("SELECT submission_file FROM assignment_submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1");
        $existing->execute([$assignmentId, $studentId]);
        $oldFile = $existing->fetchColumn();
        if ($oldFile && is_file(ASSIGNMENT_DIR . $oldFile)) {
            @unlink(ASSIGNMENT_DIR . $oldFile);
        }

        $pdo->prepare("
            INSERT INTO assignment_submissions (assignment_id, student_id, submission_file)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE submission_file = VALUES(submission_file), submitted_at = CURRENT_TIMESTAMP
        ")->execute([$assignmentId, $studentId, $up['path']]);

        // --- Notification Sync (Lecturer) ---
        $info = $pdo->prepare("
            SELECT a.title, a.lecturer_id, st.full_name
            FROM assignments a
            JOIN students st ON st.id = ?
            WHERE a.id = ?
        ");
        $info->execute([$studentId, $assignmentId]);
        $assignInfo = $info->fetch();
        if ($assignInfo) {
            require_once __DIR__ . '/notification_controller.php';
            $title = "New Submission: " . $assignInfo['title'];
            $msg = $assignInfo['full_name'] . " has submitted their assignment.";
            $link = BASE_URL . "/frontend/lecturer/assignments/view.php?id=" . $assignmentId;
            addNotification($pdo, 'L' . $assignInfo['lecturer_id'], 'system', $title, $msg, $link);
        }

        if (!$inTransaction) $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        if (!$inTransaction && $pdo->inTransaction()) $pdo->rollBack();
        error_log('submitAssignment: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to submit assignment.']];
    }
}


