<?php
$logPath = __DIR__ . '/backend/notice_debug.log';
if (file_exists($logPath)) {
    echo "Exists. Content:\n";
    echo file_get_contents($logPath);
} else {
    echo "Does not exist.";
}
