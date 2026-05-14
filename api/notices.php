<?php
// =====================================================
// ISSD Management - API: Notice Actions
// api/notices.php
// =====================================================
require_once dirname(__DIR__) . '/backend/config.php';
require_once dirname(__DIR__) . '/backend/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'readers' && $id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(u.name, 'Unknown User') as name, 
                   COALESCE(u.role, 'student') as role, 
                   u.avatar, 
                   DATE_FORMAT(rn.read_at, '%d %b, %h:%i %p') as read_at
            FROM read_notices rn
            LEFT JOIN users u ON u.id = rn.user_id
            WHERE rn.notice_id = ? 
              AND rn.user_id NOT LIKE 'L%' 
              AND (u.role != 'admin' OR u.role IS NULL)
            UNION ALL
            SELECT l.name, 'lecturer' as role, l.photo as avatar, DATE_FORMAT(rn.read_at, '%d %b, %h:%i %p') as read_at
            FROM read_notices rn
            JOIN lecturers l ON l.id = SUBSTRING(rn.user_id, 2)
            WHERE rn.notice_id = ? AND rn.user_id LIKE 'L%'
            ORDER BY role ASC, read_at DESC
        ");
        $stmt->execute([$id, $id]);
        $readers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'readers' => $readers]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action or ID']);
