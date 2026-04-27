<?php
// =====================================================
// LEARN Management - Auth Helper
// =====================================================

require_once dirname(__DIR__) . '/backend/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------
// Check if user is logged in
// -------------------------------------------------------
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// -------------------------------------------------------
// Get current user info
// -------------------------------------------------------
function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function hasRole(string $role): bool {
    return currentRole() === $role;
}

// -------------------------------------------------------
// Redirect helpers
// -------------------------------------------------------
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// -------------------------------------------------------
// Require login (redirect to login if not authenticated)
// -------------------------------------------------------
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }

    // Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect(BASE_URL . '/login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}

// -------------------------------------------------------
// Require specific role
// -------------------------------------------------------
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        redirect(BASE_URL . '/login.php?forbidden=1');
    }
}

// -------------------------------------------------------
// Login logic — checks users table (admin) AND lecturers table
// -------------------------------------------------------
function loginUser(string $identifier, string $password): array {
    require_once dirname(__DIR__) . '/backend/db.php';
    $pdo = getDBConnection();

    // ── 1. Try admin/student from users table (by email only) ──
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch();

    if ($user) {
        $valid = password_verify($password, $user['password']) || $user['password'] === $password;
        if ($valid) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['user']          = [
                'id'     => $user['id'],
                'name'   => $user['name'],
                'email'  => $user['email'],
                'role'   => $user['role'],
                'avatar' => $user['avatar'] ?? null,
                'source' => 'users',
            ];
            logActivity($user['id'], 'login', 'User logged in');
            return ['success' => true, 'role' => $user['role']];
        }
    }

    // ── 2. Try lecturer table (email OR username) ──
    $stmt2 = $pdo->prepare("
        SELECT * FROM lecturers
        WHERE (email = ? OR username = ?) AND status = 'active'
        LIMIT 1
    ");
    $stmt2->execute([$identifier, $identifier]);
    $lect = $stmt2->fetch();

    if ($lect && password_verify($password, $lect['password'])) {
        $_SESSION['user_id']       = 'L' . $lect['id']; // prefix to distinguish from users.id
        $_SESSION['role']          = ROLE_LECTURER;
        $_SESSION['lecturer_id']   = $lect['id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['user']          = [
            'id'     => $lect['id'],
            'name'   => $lect['name'],
            'email'  => $lect['email'],
            'role'   => ROLE_LECTURER,
            'avatar' => $lect['photo'] ?? null,
            'source' => 'lecturers',
        ];
        // log as generic entry
        logActivity(null, 'login', 'Lecturer ' . $lect['name'] . ' logged in');
        return ['success' => true, 'role' => ROLE_LECTURER];
    }

    return ['success' => false, 'message' => 'Invalid email/username or password.'];
}

// -------------------------------------------------------
// Logout
// -------------------------------------------------------
function logoutUser(): void {
    if (isLoggedIn()) {
        logActivity(currentUserId(), 'logout', 'User logged out');
    }
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/login.php?logged_out=1');
}

// -------------------------------------------------------
// Activity logger
// -------------------------------------------------------
function logActivity($userId, string $action, string $details = ''): void {
    try {
        require_once dirname(__DIR__) . '/backend/db.php';
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $dbUserId = is_numeric($userId) ? (int)$userId : (is_int($userId) ? $userId : null);

        $stmt = $pdo->prepare(
            "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$dbUserId, $action, $details, $ip]);
    } catch (\Throwable $e) {
        // Silent fail for logging, but we can log to php error log
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

// -------------------------------------------------------
// CSRF Token helpers
// -------------------------------------------------------
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

// -------------------------------------------------------
// Flash messages
// -------------------------------------------------------
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = compact('type', 'message');
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
