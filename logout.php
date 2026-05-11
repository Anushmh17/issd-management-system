<?php
// =====================================================
// ISSD Management - Logout
// =====================================================
require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

verifyCsrf();

logoutUser(); // handles session destroy + redirect


