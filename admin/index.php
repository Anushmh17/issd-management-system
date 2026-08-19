<?php
// Redirect root admin/ directory to admin dashboard
require_once dirname(__DIR__) . '/backend/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

redirect(BASE_URL . '/frontend/admin/dashboard.php');
