<?php
// =====================================================
// ISSD Management - Course Controller
// backend/course_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// -------------------------------------------------------
// Validate course fields
// -------------------------------------------------------
function validateCourseFields(array $d): array {
    $errors = [];
    if (empty(trim($d['course_name'] ?? '')))    $errors[] = 'Course name is required.';
    if (empty(trim($d['course_code'] ?? '')))    $errors[] = 'Course code is required.';
    if (!is_numeric($d['monthly_fee'] ?? ''))    $errors[] = 'Monthly fee must be a numeric value.';
    if ((float)($d['monthly_fee'] ?? 0) < 0)    $errors[] = 'Monthly fee cannot be negative.';
    return $errors;
}

// -------------------------------------------------------
// Add Course
// -------------------------------------------------------
function addCourse(PDO $pdo, array $d): array {
    $errors = validateCourseFields($d);
    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        $pdo->prepare("
            INSERT INTO courses (course_name, course_code, duration, monthly_fee, description, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            trim($d['course_name']),
            strtoupper(trim($d['course_code'])),
            trim($d['duration'] ?? ''),
            (float)$d['monthly_fee'],
            trim($d['description'] ?? ''),
            $d['status'] ?? 'active',
        ]);
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) return ['success' => false, 'errors' => ['Course code already exists.']];
        error_log('addCourse: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to save course. Please try again.']];
    }
}

// -------------------------------------------------------
// Update Course
// -------------------------------------------------------
function updateCourse(PDO $pdo, int $id, array $d): array {
    $errors = validateCourseFields($d);
    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        $pdo->prepare("
            UPDATE courses SET
              course_name  = ?,
              course_code  = ?,
              duration     = ?,
              monthly_fee  = ?,
              description  = ?,
              status       = ?
            WHERE id = ?
        ")->execute([
            trim($d['course_name']),
            strtoupper(trim($d['course_code'])),
            trim($d['duration'] ?? ''),
            (float)$d['monthly_fee'],
            trim($d['description'] ?? ''),
            $d['status'] ?? 'active',
            $id,
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) return ['success' => false, 'errors' => ['Course code already exists.']];
        error_log('updateCourse: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to update course.']];
    }
}

// -------------------------------------------------------
// Delete Course (hard delete)
// -------------------------------------------------------
function deleteCourse(PDO $pdo, int $id): bool {
    try {
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        error_log('deleteCourse: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Get single course by ID
// -------------------------------------------------------
function getCourseById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("
        SELECT c.*,
               ca.lecturer_id,
               u.name AS lecturer_name,
               (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id=c.id AND sc.status='ongoing') AS student_count
        FROM courses c
        LEFT JOIN course_assignments ca ON ca.course_id = c.id
        LEFT JOIN users u ON u.id = ca.lecturer_id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// -------------------------------------------------------
// Get courses list with optional filters
// -------------------------------------------------------
function getCoursesList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 20): array {
    $where  = [];
    $params = [];

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $where[]  = "(c.course_name LIKE ? OR c.course_code LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
    }

    $status = trim($filters['status'] ?? '');
    if ($status !== '') {
        $where[]  = "c.status = ?";
        $params[] = $status;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses c {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $pages  = max(1, (int)ceil($total / $perPage));
    $page   = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT c.*,
               ca.lecturer_id,
               ca.assigned_date,
               u.name AS lecturer_name,
               (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id=c.id AND sc.status='ongoing') AS student_count
        FROM courses c
        LEFT JOIN course_assignments ca ON ca.course_id = c.id
        LEFT JOIN users u ON u.id = ca.lecturer_id
        {$whereSQL}
        ORDER BY c.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $courses = $stmt->fetchAll();

    return compact('courses', 'total', 'pages', 'page');
}

// -------------------------------------------------------
// Assign Lecturer to Course (admin only)
// -------------------------------------------------------
function assignLecturer(PDO $pdo, int $courseId, int $lecturerId, ?string $date = null): array {
    if (!$courseId || !$lecturerId) {
        return ['success' => false, 'errors' => ['Course and lecturer are required.']];
    }
    try {
        // Upsert: insert or update the assignment for this course
        $pdo->prepare("
            INSERT INTO course_assignments (course_id, lecturer_id, assigned_date)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE lecturer_id = VALUES(lecturer_id), assigned_date = VALUES(assigned_date)
        ")->execute([$courseId, $lecturerId, $date ?: date('Y-m-d')]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('assignLecturer: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to assign lecturer.']];
    }
}

// -------------------------------------------------------
// Remove lecturer assignment from a course
// -------------------------------------------------------
function removeLecturerAssignment(PDO $pdo, int $courseId): bool {
    try {
        $pdo->prepare("DELETE FROM course_assignments WHERE course_id = ?")->execute([$courseId]);
        return true;
    } catch (PDOException $e) {
        error_log('removeLecturerAssignment: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Assign Student to Course
// -------------------------------------------------------
function assignStudentToCourse(PDO $pdo, int $studentId, int $courseId, array $data = []): array {
    if (!$studentId || !$courseId) {
        return ['success' => false, 'errors' => ['Student and course are required.']];
    }

    // Check if already enrolled (and not dropped)
    $check = $pdo->prepare("
        SELECT id FROM student_courses
        WHERE student_id = ? AND course_id = ? AND status != 'dropped'
    ");
    $check->execute([$studentId, $courseId]);
    if ($check->fetch()) {
        return ['success' => false, 'errors' => ['Student is already enrolled in this course.']];
    }

    try {
        $pdo->prepare("
            INSERT INTO student_courses (student_id, course_id, start_date, end_date, status)
            VALUES (?, ?, ?, ?, 'ongoing')
            ON DUPLICATE KEY UPDATE start_date = VALUES(start_date), end_date = VALUES(end_date), status = 'ongoing'
        ")->execute([
            $studentId,
            $courseId,
            !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d'),
            !empty($data['end_date'])   ? $data['end_date']   : null,
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log('assignStudentToCourse: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to enroll student.']];
    }
}

// -------------------------------------------------------
// Update student_course status
// -------------------------------------------------------
function updateStudentCourseStatus(PDO $pdo, int $id, string $status): bool {
    $allowed = ['ongoing', 'completed', 'dropped'];
    if (!in_array($status, $allowed, true)) return false;
    try {
        $pdo->prepare("UPDATE student_courses SET status = ? WHERE id = ?")->execute([$status, $id]);
        return true;
    } catch (PDOException $e) {
        error_log('updateStudentCourseStatus: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Get all active lecturers (for dropdowns) "" uses standalone lecturers table
// -------------------------------------------------------
function getActiveLecturers(PDO $pdo): array {
    return $pdo->query("
        SELECT id, name, department, email
        FROM lecturers
        WHERE status = 'active'
        ORDER BY name ASC
    ")->fetchAll();
}

// -------------------------------------------------------
// Get all active courses (for dropdowns)
// -------------------------------------------------------
function getActiveCourses(PDO $pdo): array {
    return $pdo->query("
        SELECT id, course_name, course_code, monthly_fee
        FROM courses
        WHERE status = 'active'
        ORDER BY course_name ASC
    ")->fetchAll();
}

// -------------------------------------------------------
// Get all active students (for dropdowns)
// -------------------------------------------------------
function getActiveStudentsForCourse(PDO $pdo): array {
    return $pdo->query("
        SELECT id, student_id, full_name, batch_number
        FROM students
        WHERE status != 'dropout'
        ORDER BY full_name ASC
    ")->fetchAll();
}

// -------------------------------------------------------
// Get students enrolled in a specific course
// -------------------------------------------------------
function getCourseStudents(PDO $pdo, int $courseId): array {
    $stmt = $pdo->prepare("
        SELECT sc.*, s.student_id AS sid, s.full_name, s.phone_number, s.batch_number
        FROM student_courses sc
        JOIN students s ON s.id = sc.student_id
        WHERE sc.course_id = ?
        ORDER BY sc.created_at DESC
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

