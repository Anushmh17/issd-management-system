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

/**
 * Standardize Sri Lankan phone numbers to +94 format
 */
function formatSriLankanPhone($phone) {
    if (empty($phone)) return null;
    
    // Remove all non-numeric characters except +
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // If starts with 0 (e.g. 0712345678) -> convert to +94712345678
    if (preg_match('/^0([0-9]{9})$/', $phone, $matches)) {
        return '+94' . $matches[1];
    }
    
    // If starts with 94 (e.g. 94712345678) -> prepend +
    if (preg_match('/^94([0-9]{9})$/', $phone, $matches)) {
        return '+94' . $matches[1];
    }
    
    // If starts with +94... already perfect
    if (preg_match('/^\+94[0-9]{9}$/', $phone)) {
        return $phone;
    }
    
    return $phone; 
}

