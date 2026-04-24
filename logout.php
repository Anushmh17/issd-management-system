<?php
// =====================================================
// LEARN Management - Logout
// =====================================================
require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser(); // handles session destroy + redirect
