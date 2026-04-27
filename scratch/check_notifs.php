<?php
require_once 'backend/db.php';
$count = $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
echo "Total Notifications: " . $count . "\n";

$calls = $pdo->query("SELECT id, full_name, next_follow_up FROM students WHERE next_follow_up IS NOT NULL")->fetchAll();
echo "Students with follow-up: " . count($calls) . "\n";
foreach($calls as $c) {
    echo "- {$c['full_name']} (Due: {$c['next_follow_up']})\n";
}
