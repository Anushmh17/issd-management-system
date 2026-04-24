<?php
// =====================================================
// LEARN Management - Shared Header
// =====================================================
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'LEARN Management');
if (!defined('BASE_URL'))   define('BASE_URL', '/Webbuilders%20Projects/LEARN%20Management');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="LEARN Management - Institute Management System">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars(PAGE_TITLE) ?> | LEARN Management</title>

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

  <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
