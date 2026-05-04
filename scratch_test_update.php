<?php
require_once 'backend/config.php';
require_once 'backend/db.php';
require_once 'backend/lecturer_controller.php';

$id = 1; // nimal_silva
$lecturer = getLecturerById($pdo, $id);
echo "Current Hash: " . $lecturer['password'] . "\n";

$data = [
    'name'           => $lecturer['name'],
    'email'          => $lecturer['email'],
    'phone'          => $lecturer['phone'],
    'username'       => $lecturer['username'],
    'new_password'   => 'password123', // Test new password
    'qualifications' => $lecturer['qualifications'],
    'department'     => $lecturer['department'],
    'employee_id'    => $lecturer['employee_id'],
    'joined_date'    => $lecturer['joined_date'],
    'status'         => $lecturer['status'],
];

$result = updateLecturer($pdo, $id, $data);
if ($result['success']) {
    echo "Update successful.\n";
    $updated = getLecturerById($pdo, $id);
    echo "Updated Hash: " . $updated['password'] . "\n";
    if ($lecturer['password'] !== $updated['password']) {
        echo "Password HASH CHANGED as expected.\n";
    } else {
        echo "Password HASH DID NOT CHANGE!\n";
    }
} else {
    print_r($result['errors']);
}
?>
