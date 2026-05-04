<?php
$url = 'http://localhost/Webbuilders%20Projects/issd_management/admin/payments/api.php?api=courses&student_id=2';
// We can't really call localhost via curl easily if the server isn't running in a way we can access.
// But we can include the file and mock the $_GET.

$_GET['api'] = 'courses';
$_GET['student_id'] = 2;

// Capture output
ob_start();
require 'admin/payments/api.php';
$output = ob_get_clean();

echo "API OUTPUT:\n";
echo $output;
?>
