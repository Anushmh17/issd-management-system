<?php
require 'backend/config.php';
require 'backend/db.php';
require 'backend/notification_controller.php';

$userId = 'L1';
$role = 'lecturer';

$notifs = getRecentNotifications($pdo, $userId, $role, 'all', 50, false, false);
print_r($notifs);
