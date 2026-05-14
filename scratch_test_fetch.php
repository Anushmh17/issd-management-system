<?php
session_start();
$url = 'http://localhost/Webbuilders%20Projects/issd_management/backend/notice_read.php';

$data = json_encode([
    'notice_id' => 1,
    'csrf_token' => $_SESSION['csrf_token'] ?? ''
]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n" .
                     "Cookie: PHPSESSID=" . session_id() . "\r\n",
        'method'  => 'POST',
        'content' => $data,
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
var_dump($result);
