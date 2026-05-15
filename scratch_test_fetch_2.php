<?php
$url = 'http://localhost/Webbuilders%20Projects/issd_management/backend/notice_read.php';

$data = json_encode([
    'notice_id' => 1,
    'csrf_token' => 'dummy'
]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo "Result:\n";
var_dump($result);
echo "\nHeaders:\n";
var_dump($http_response_header ?? []);
