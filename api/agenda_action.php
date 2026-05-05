<?php
/**
 * ISSD Management - Agenda Actions API
 * api/agenda_action.php
 */
require_once dirname(__DIR__) . '/backend/config.php';
require_once dirname(__DIR__) . '/backend/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$action   = $_POST['action'] ?? '';
$id       = (int)($_POST['id'] ?? 0);
$category = $_POST['category'] ?? '';

if (!$id || !$category) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    if ($action === 'snooze') {
        // Default to 2 hours if no time provided
        $timeParam = $_POST['time'] ?? '+2 hours';
        $newTime = date('Y-m-d H:i:s', strtotime($timeParam));
        
        if ($category === 'lead') {
            $stmt = $pdo->prepare("UPDATE leads SET next_followup_datetime = ? WHERE id = ?");
            $stmt->execute([$newTime, $id]);
            logActivity($_SESSION['user_id'] ?? null, 'lead_snoozed', "Lead ID $id snoozed to $newTime");
        } elseif ($category === 'student') {
            $stmt = $pdo->prepare("UPDATE students SET next_follow_up = ? WHERE id = ?");
            $stmt->execute([$newTime, $id]);
            logActivity($_SESSION['user_id'] ?? null, 'student_snoozed', "Student ID $id snoozed to $newTime");
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid category']);
            exit;
        }
        
        echo json_encode(['success' => true, 'new_time' => $newTime]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
