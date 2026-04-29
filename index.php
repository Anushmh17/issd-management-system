<?php
// =====================================================
// ISSD Management - Entry Point (redirect)
// =====================================================
require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = currentRole();
    if ($role === ROLE_ADMIN)    redirect(BASE_URL . '/frontend/admin/dashboard.php');
    if ($role === ROLE_LECTURER) redirect(BASE_URL . '/frontend/lecturer/dashboard.php');
    if ($role === ROLE_STUDENT)  redirect(BASE_URL . '/frontend/student/dashboard.php');
}

redirect(BASE_URL . '/login.php');
