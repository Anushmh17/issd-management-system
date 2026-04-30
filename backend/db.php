<?php
// =====================================================
// ISSD Management - Database Connection (PDO)
// =====================================================

require_once __DIR__ . '/config.php';

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            require_once __DIR__ . '/config.php';
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\Throwable $e) {
            // Log it or die with a clear message
            error_log("Database Connection Error: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please check configuration.'
            ]));
        }
    }
    if ($pdo === null) {
        error_log("getDBConnection: PDO is null, should have died if connection failed.");
        die("Critical Error: Database connection object is null.");
    }
    return $pdo;
}

// Shorthand
$pdo = getDBConnection();

