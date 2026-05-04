<?php
$_GET['api'] = 'info';
$_GET['student_id'] = 2;
$_GET['course_id'] = 1;
$_GET['month'] = date('Y-m');

ob_start();
require 'admin/payments/api.php';
$output = ob_get_clean();

echo "INFO API OUTPUT:\n";
echo $output;
?>
