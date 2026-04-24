<?php
// =====================================================
// LEARN Management - Lecturer Controller
// backend/lecturer_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// -------------------------------------------------------
// Upload constants
// -------------------------------------------------------
define('LECT_PHOTO_DIR', BASE_PATH . '/assets/images/lecturers/');
define('LECT_PHOTO_URL', BASE_URL  . '/assets/images/lecturers/');
define('LECT_PHOTO_MAX', 5 * 1024 * 1024); // 5 MB
define('LECT_PHOTO_TYPES', ['image/jpeg','image/jpg','image/png','image/gif','image/webp']);
define('LECT_PHOTO_EXTS',  ['jpg','jpeg','png','gif','webp']);

function ensureLectPhotoDir(): void {
    if (!is_dir(LECT_PHOTO_DIR)) mkdir(LECT_PHOTO_DIR, 0755, true);
}

// -------------------------------------------------------
// Validate lecturer input fields
// -------------------------------------------------------
function validateLecturerFields(array $d, bool $isAdd = true, ?int $editId = null): array {
    $errors = [];
    require_once __DIR__ . '/db.php';
    $pdo = getDBConnection();

    if (empty(trim($d['name'] ?? '')))     $errors[] = 'Full name is required.';
    if (empty(trim($d['email'] ?? '')))    $errors[] = 'Email address is required.';
    elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty(trim($d['username'] ?? ''))) $errors[] = 'Username is required.';
    if ($isAdd && empty(trim($d['password'] ?? ''))) $errors[] = 'Password is required.';

    // Unique email check
    if (!empty($d['email'])) {
        $q = $editId
            ? "SELECT id FROM lecturers WHERE email = ? AND id != ?"
            : "SELECT id FROM lecturers WHERE email = ?";
        $p = $editId ? [$d['email'], $editId] : [$d['email']];
        $stmt = $pdo->prepare($q); $stmt->execute($p);
        if ($stmt->fetch()) $errors[] = 'Email address is already in use.';
    }

    // Unique username check
    if (!empty($d['username'])) {
        $q = $editId
            ? "SELECT id FROM lecturers WHERE username = ? AND id != ?"
            : "SELECT id FROM lecturers WHERE username = ?";
        $p = $editId ? [$d['username'], $editId] : [$d['username']];
        $stmt = $pdo->prepare($q); $stmt->execute($p);
        if ($stmt->fetch()) $errors[] = 'Username is already taken.';
    }

    // Unique employee_id check
    if (!empty(trim($d['employee_id'] ?? ''))) {
        $q = $editId
            ? "SELECT id FROM lecturers WHERE employee_id = ? AND id != ?"
            : "SELECT id FROM lecturers WHERE employee_id = ?";
        $p = $editId ? [$d['employee_id'], $editId] : [$d['employee_id']];
        $stmt = $pdo->prepare($q); $stmt->execute($p);
        if ($stmt->fetch()) $errors[] = 'Employee ID is already in use.';
    }

    return $errors;
}

// -------------------------------------------------------
// Upload lecturer photo
// Returns ['success'=>bool, 'path'=>string|null, 'error'=>string]
// -------------------------------------------------------
function uploadLecturerPhoto(array $file, int $lecturerId): array {
    ensureLectPhotoDir();

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => null, 'error' => ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > LECT_PHOTO_MAX) {
        return ['success' => false, 'path' => null, 'error' => 'Photo exceeds 5 MB limit.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, LECT_PHOTO_EXTS, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid type. Use JPG, PNG, GIF or WebP.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, LECT_PHOTO_TYPES, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid MIME type.'];
    }

    $filename = 'LECT' . $lecturerId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = LECT_PHOTO_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to save photo.'];
    }
    return ['success' => true, 'path' => $filename, 'error' => ''];
}

// -------------------------------------------------------
// Add Lecturer
// -------------------------------------------------------
function addLecturer(PDO $pdo, array $d, ?array $photoFile = null): array {
    $errors = validateLecturerFields($d, true);
    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        $pdo->prepare("
            INSERT INTO lecturers
              (name, email, phone, qualifications, username, password,
               department, employee_id, joined_date, status)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            trim($d['name']),
            trim($d['email']),
            trim($d['phone'] ?? ''),
            trim($d['qualifications'] ?? ''),
            trim($d['username']),
            password_hash(trim($d['password']), PASSWORD_DEFAULT),
            trim($d['department'] ?? ''),
            trim($d['employee_id'] ?? '') ?: null,
            !empty($d['joined_date']) ? $d['joined_date'] : date('Y-m-d'),
            $d['status'] ?? 'active',
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Handle photo upload
        if ($photoFile && !empty($photoFile['name'])) {
            $up = uploadLecturerPhoto($photoFile, $newId);
            if ($up['success']) {
                $pdo->prepare("UPDATE lecturers SET photo = ? WHERE id = ?")
                    ->execute([$up['path'], $newId]);
            } elseif ($up['error']) {
                // Non-fatal: lecturer saved, photo failed
                return ['success' => true, 'id' => $newId, 'warning' => $up['error']];
            }
        }

        return ['success' => true, 'id' => $newId];
    } catch (PDOException $e) {
        error_log('addLecturer: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to add lecturer. Please try again.']];
    }
}

// -------------------------------------------------------
// Update Lecturer
// -------------------------------------------------------
function updateLecturer(PDO $pdo, int $id, array $d, ?array $photoFile = null): array {
    $errors = validateLecturerFields($d, false, $id);
    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        // Build SET clause — password only if provided
        $sets   = ['name=?','email=?','phone=?','qualifications=?',
                   'username=?','department=?','employee_id=?','joined_date=?','status=?'];
        $params = [
            trim($d['name']),
            trim($d['email']),
            trim($d['phone'] ?? ''),
            trim($d['qualifications'] ?? ''),
            trim($d['username']),
            trim($d['department'] ?? ''),
            trim($d['employee_id'] ?? '') ?: null,
            !empty($d['joined_date']) ? $d['joined_date'] : null,
            $d['status'] ?? 'active',
        ];

        if (!empty(trim($d['new_password'] ?? ''))) {
            $sets[]   = 'password=?';
            $params[] = password_hash(trim($d['new_password']), PASSWORD_DEFAULT);
        }

        // Photo upload
        if ($photoFile && !empty($photoFile['name'])) {
            $up = uploadLecturerPhoto($photoFile, $id);
            if ($up['success']) {
                // Delete old photo
                $old = $pdo->prepare("SELECT photo FROM lecturers WHERE id=?");
                $old->execute([$id]);
                $oldPhoto = $old->fetchColumn();
                if ($oldPhoto && is_file(LECT_PHOTO_DIR . $oldPhoto)) {
                    @unlink(LECT_PHOTO_DIR . $oldPhoto);
                }
                $sets[]   = 'photo=?';
                $params[] = $up['path'];
            } elseif ($up['error']) {
                return ['success' => false, 'errors' => [$up['error']]];
            }
        }

        $params[] = $id;
        $pdo->prepare("UPDATE lecturers SET " . implode(',', $sets) . " WHERE id=?")
            ->execute($params);

        return ['success' => true];
    } catch (PDOException $e) {
        error_log('updateLecturer: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to update lecturer.']];
    }
}

// -------------------------------------------------------
// Delete Lecturer (hard delete — photo cleaned up)
// -------------------------------------------------------
function deleteLecturer(PDO $pdo, int $id): bool {
    try {
        $stmt = $pdo->prepare("SELECT photo FROM lecturers WHERE id=?");
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();
        if ($photo && is_file(LECT_PHOTO_DIR . $photo)) {
            @unlink(LECT_PHOTO_DIR . $photo);
        }
        $pdo->prepare("DELETE FROM lecturers WHERE id=?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        error_log('deleteLecturer: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Get single lecturer + assigned courses
// -------------------------------------------------------
function getLecturerById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;

    // Assigned courses
    $cStmt = $pdo->prepare("
        SELECT c.id, c.course_name, c.course_code, c.monthly_fee, c.status,
               ca.assigned_date
        FROM course_assignments ca
        JOIN courses c ON c.id = ca.course_id
        WHERE ca.lecturer_id = ?
        ORDER BY ca.assigned_date DESC
    ");
    $cStmt->execute([$id]);
    $row['courses'] = $cStmt->fetchAll();
    $row['course_count'] = count($row['courses']);

    return $row;
}

// -------------------------------------------------------
// Get lecturers list with filters + pagination
// -------------------------------------------------------
function getLecturersList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 15): array {
    $where  = [];
    $params = [];

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $where[]  = "(l.name LIKE ? OR l.email LIKE ? OR l.username LIKE ? OR l.department LIKE ?)";
        $like = "%{$search}%";
        $params = array_merge($params, [$like,$like,$like,$like]);
    }

    $status = trim($filters['status'] ?? '');
    if ($status !== '') {
        $where[]  = "l.status = ?";
        $params[] = $status;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM lecturers l {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT l.*,
               (SELECT COUNT(*) FROM course_assignments ca WHERE ca.lecturer_id = l.id) AS course_count
        FROM lecturers l
        {$whereSQL}
        ORDER BY l.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $lecturers = $stmt->fetchAll();

    return compact('lecturers','total','pages','page');
}

// -------------------------------------------------------
// Lecturer login (called from auth.php extra check)
// -------------------------------------------------------
function loginLecturer(string $identifier, string $password): array {
    $pdo = getDBConnection();

    // Allow login by email OR username
    $stmt = $pdo->prepare("
        SELECT * FROM lecturers
        WHERE (email = ? OR username = ?) AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier]);
    $lect = $stmt->fetch();

    if (!$lect) return ['success' => false];
    if (!password_verify($password, $lect['password'])) return ['success' => false];

    return ['success' => true, 'lecturer' => $lect];
}

// -------------------------------------------------------
// Get lecturers for dropdowns (course_controller uses this)
// -------------------------------------------------------
function getAllActiveLecturers(PDO $pdo): array {
    return $pdo->query("
        SELECT id, name, department, email
        FROM lecturers
        WHERE status = 'active'
        ORDER BY name ASC
    ")->fetchAll();
}

// -------------------------------------------------------
// Photo URL helper
// -------------------------------------------------------
function lecturerPhotoUrl(?string $photo): string {
    if ($photo && is_file(LECT_PHOTO_DIR . $photo)) {
        return LECT_PHOTO_URL . $photo;
    }
    return BASE_URL . '/assets/images/avatar-default.png';
}
