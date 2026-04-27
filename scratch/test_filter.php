<?php
require_once 'backend/db.php';
require_once 'backend/notification_controller.php';

echo "Testing category: call\n";
$notifs = getRecentNotifications($pdo, 1, 'admin', 'call', 50);
foreach($notifs as $n) {
    echo "Type: {$n['type']} | Title: {$n['title']}\n";
}

echo "\nTesting category: all\n";
$all = getRecentNotifications($pdo, 1, 'admin', 'all', 50);
foreach($all as $a) {
    echo "Type: {$a['type']} | Title: {$a['title']}\n";
}
