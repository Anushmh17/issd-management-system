<?php
/**
 * Test Snooze Logic
 */
require_once __DIR__ . '/../backend/config.php';

function testSnooze($param) {
    $currentTime = date('Y-m-d H:i:s');
    $newTime = date('Y-m-d H:i:s', strtotime($param));
    echo "Param: $param\n";
    echo "Current Time: $currentTime\n";
    echo "Snoozed Time: $newTime\n";
    echo "--------------------------\n";
}

testSnooze('+2 hours');
testSnooze('tomorrow 09:00:00');
testSnooze('+2 days');
