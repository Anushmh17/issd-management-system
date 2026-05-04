<?php
$file = 'admin/lecturers/edit.php';
$content = file_get_contents($file);

$old = '<span style="font-size:9px;font-weight:700;color:#059669;">â-- </span>';
$new = '<span style="font-size:7px;color:#059669;margin-left:auto;"><i class="fas fa-circle"></i></span>';
$content = str_replace($old, $new, $content);

$old2 = '<span style="font-size:9px;font-weight:700;color:#94a3b8;">â-- </span>';
$new2 = '<span style="font-size:7px;color:#94a3b8;margin-left:auto;"><i class="fas fa-circle"></i></span>';
$content = str_replace($old2, $new2, $content);

file_put_contents($file, $content);
echo "Fixed $file\n";
?>
