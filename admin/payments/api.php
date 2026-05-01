<?php
// admin/payments/api.php
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/backend/payment_controller.php';

header('Content-Type: application/json');
if (ob_get_length()) ob_clean();

$action = $_GET['api'] ?? '';

if ($action === 'courses') {
    $sid = (int)($_GET['student_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT c.id, c.course_name, c.course_code 
        FROM student_courses sc 
        JOIN courses c ON sc.course_id = c.id 
        WHERE sc.student_id = ?
    ");
    $stmt->execute([$sid]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($action === 'info') {
    $sid   = (int)($_GET['student_id'] ?? 0);
    $cid   = (int)($_GET['course_id'] ?? 0);
    $month = $_GET['month'] ?? date('Y-m');
    echo json_encode(getPaymentInfoForm($pdo, $sid, $cid, $month));
} else {
    echo json_encode(['error' => 'Invalid API action']);
}
exit;
