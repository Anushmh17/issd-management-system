<?php
require_once 'backend/db.php';
require_once 'backend/notification_controller.php';

// Add a dummy notification to verify the history page
addNotification($pdo, null, 'call', 'Sample Call Reminder', 'This is a test notification to verify the history page is working.', '#');
addNotification($pdo, null, 'payment', 'Tuition Fee Due', 'Student Anush MH has a pending payment of Rs. 5,000.', '#');
addNotification($pdo, null, 'system', 'Database Backup', 'Weekly database backup completed successfully.', '#');

echo "Dummy notifications added successfully!";
