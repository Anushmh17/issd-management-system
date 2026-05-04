<?php
$file = 'admin/lecturers/edit.php';
$content = file_get_contents($file);

// Use regex to replace the spans with glitched content
$content = preg_replace(
    '/<span style="font-size:9px;font-weight:700;color:#059669;">.*?<\/span>/',
    '<span style="font-size:7px;color:#059669;margin-left:auto;" title="Active"><i class="fas fa-circle"></i></span>',
    $content
);

$content = preg_replace(
    '/<span style="font-size:9px;font-weight:700;color:#94a3b8;">.*?<\/span>/',
    '<span style="font-size:7px;color:#94a3b8;margin-left:auto;" title="Inactive"><i class="fas fa-circle"></i></span>',
    $content
);

file_put_contents($file, $content);
echo "Regex Fix applied to $file\n";
?>
