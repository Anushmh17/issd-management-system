<?php
// =====================================================
// ISSD Management - Application Configuration
// =====================================================

define('APP_NAME', 'ISSD Management');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Webbuilders%20Projects/issd_management');
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/Webbuilders%20Projects/issd_management');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'issd_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Upload paths
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_LECTURER', 'lecturer');
define('ROLE_STUDENT', 'student');

// Timezone
date_default_timezone_set('Asia/Colombo');

