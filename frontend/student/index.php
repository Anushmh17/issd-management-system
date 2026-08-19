<?php
// Redirect frontend/student/ to dashboard.php
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

redirect(BASE_URL . '/frontend/student/dashboard.php');
