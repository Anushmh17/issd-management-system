<?php
require_once 'backend/config.php';
$old = BASE_PATH . '/assets/documents/STU0_profile_20260429_164031_18f113dc.jpg';
$new = BASE_PATH . '/assets/documents/profile_4.jpg';
if (file_exists($old)) {
    if (rename($old, $new)) {
        echo "SUCCESS: Renamed to profile_4.jpg\n";
        require_once 'backend/db.php';
        $pdo->prepare("UPDATE students SET profile_picture = 'profile_4.jpg' WHERE id = 4")->execute();
        echo "DB UPDATED\n";
    } else {
        echo "FAILED: Could not rename\n";
    }
} else {
    echo "FAILED: Old file not found at $old\n";
}
?>
