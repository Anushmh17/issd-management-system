<?php
session_start();
$_SESSION['user_id'] = 'L1';
$_SESSION['role'] = 'lecturer';
session_write_close();

$url = 'http://localhost/Webbuilders%20Projects/issd_management/frontend/lecturer/notices.php';

$options = [
    'http' => [
        'header'  => "Cookie: PHPSESSID=" . session_id() . "\r\n",
        'method'  => 'GET'
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

// Find the fetch block
if (preg_match('/fetch\([^)]+notice_read\.php[^)]*\)/', $result, $matches)) {
    echo "Found fetch: " . $matches[0];
} else {
    echo "Fetch block not found in HTML.";
}
