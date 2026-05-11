<?php
// =====================================================
// ISSD Management - Leads Controller
// backend/leads_controller.php
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// -------------------------------------------------------
// Validate lead input fields
// -------------------------------------------------------
function validateLeadFields(array $d): array {
    $errors = [];
    if (empty(trim($d['name'] ?? '')))  $errors[] = 'Lead name is required.';
    
    $phone = trim($d['phone'] ?? '');
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } else {
        // Clean phone for validation: remove spaces, dashes, etc.
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        // Regex for SL formats: 07XXXXXXXX, 947XXXXXXXX, +947XXXXXXXX
        if (!preg_match('/^(\+94|94|0)?[7][0-9]{8}$/', $cleanPhone)) {
            $errors[] = 'Invalid Sri Lankan phone number. Use standard 9-digit format (7XXXXXXXX).';
        }
    }
    
    $sources = ['Facebook','WhatsApp','Walk-in','Other'];
    if (!in_array($d['source'] ?? '', $sources)) {
        $errors[] = 'Invalid source selected.';
    }
    
    return $errors;
}

// -------------------------------------------------------
// Add Lead
// -------------------------------------------------------
function addLead(PDO $pdo, array $d): array {
    $errors = validateLeadFields($d);
    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) $pdo->beginTransaction();
        $pdo->prepare("
            INSERT INTO leads
              (name, phone, source, status, next_followup_datetime, notes)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            trim($d['name']),
            formatSriLankanPhone($d['phone']),
            trim($d['source']),
            $d['status'] ?? 'new',
            !empty($d['next_followup_datetime']) ? $d['next_followup_datetime'] : null,
            trim($d['notes'] ?? ''),
        ]);
        $leadId = $pdo->lastInsertId();

        // --- Notification Sync ---
        if (!empty($d['next_followup_datetime'])) {
            require_once __DIR__ . '/notification_controller.php';
            $notifTitle = "New Lead Follow-up: " . trim($d['name']);
            $notifMsg = "Scheduled for " . date('M d, Y h:i A', strtotime($d['next_followup_datetime']));
            $hLink = BASE_URL . "/admin/leads/index.php?highlight_id=" . $leadId;
            addNotification($pdo, null, 'call', $notifTitle, $notifMsg, $hLink);
        }
        // --- Activity Log ---
        require_once dirname(__DIR__) . '/includes/auth.php';
        logActivity($_SESSION['user_id'] ?? null, 'lead_added', "New lead: " . trim($d['name']));

        if (!$inTransaction) $pdo->commit();
        return ['success' => true, 'id' => $leadId];
    } catch (PDOException $e) {
        if (!$inTransaction && $pdo->inTransaction()) $pdo->rollBack();
        error_log('addLead: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to add lead. Please try again.']];
    }
}

// -------------------------------------------------------
// Update Lead
// -------------------------------------------------------
function updateLead(PDO $pdo, int $id, array $d): array {
    $errors = validateLeadFields($d);
    
    $statuses = ['new','talking','converted','not_interested'];
    if (!in_array($d['status'] ?? '', $statuses)) {
        $errors[] = 'Invalid status selected.';
    }

    if ($errors) return ['success' => false, 'errors' => $errors];

    try {
        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) $pdo->beginTransaction();
        // Fetch existing lead to check for changes
        $stmt = $pdo->prepare("SELECT next_followup_datetime FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        $oldLead = $stmt->fetch();
        $oldFollowup = $oldLead['next_followup_datetime'] ?? null;
        $newFollowup = !empty($d['next_followup_datetime']) ? $d['next_followup_datetime'] : null;

        $pdo->prepare("
            UPDATE leads 
            SET name=?, phone=?, source=?, status=?, next_followup_datetime=?, notes=?
            WHERE id=?
        ")->execute([
            trim($d['name']),
            formatSriLankanPhone($d['phone']),
            trim($d['source']),
            trim($d['status']),
            $newFollowup,
            trim($d['notes'] ?? ''),
            $id
        ]);

        // --- Notification Sync (Only if date changed) ---
        if ($newFollowup && $newFollowup !== $oldFollowup) {
            require_once __DIR__ . '/notification_controller.php';
            $notifTitle = "Updated Lead Schedule: " . trim($d['name']);
            $notifMsg = "New time: " . date('M d, Y h:i A', strtotime($newFollowup));
            $hLink = BASE_URL . "/admin/leads/index.php?highlight_id=" . $id;
            addNotification($pdo, null, 'call', $notifTitle, $notifMsg, $hLink);
        }
        // --- Activity Log ---
        require_once dirname(__DIR__) . '/includes/auth.php';
        logActivity($_SESSION['user_id'] ?? null, 'lead_updated', "Lead updated: " . trim($d['name']));

        if (!$inTransaction) $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        if (!$inTransaction && $pdo->inTransaction()) $pdo->rollBack();
        error_log('updateLead: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to update lead.']];
    }
}

// -------------------------------------------------------
// Delete Lead
// -------------------------------------------------------
function deleteLead(PDO $pdo, int $id): bool {
    try {
        $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        error_log('deleteLead: ' . $e->getMessage());
        return false;
    }
}

// -------------------------------------------------------
// Get single lead
// -------------------------------------------------------
function getLeadById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// -------------------------------------------------------
// Get leads list with filters + pagination
// -------------------------------------------------------
function getLeadsList(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 15): array {
    $where  = [];
    $params = [];

    // Do not show converted leads in standard list unless explicitly filtered for
    $hideConverted = $filters['hide_converted'] ?? true;

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $where[]  = "(name LIKE ? OR phone LIKE ?)";
        $like = "%{$search}%";
        $params = array_merge($params, [$like,$like]);
    }

    $status = trim($filters['status'] ?? '');
    if ($status !== '') {
        $where[]  = "status = ?";
        $params[] = $status;
        $hideConverted = false; // if they explicitly search a status, don't hide
    }

    if ($hideConverted && $status === '') {
        $where[] = "status != 'converted'";
    }

    $source = trim($filters['source'] ?? '');
    if ($source !== '') {
        $where[]  = "source = ?";
        $params[] = $source;
    }

    $date = trim($filters['date'] ?? '');
    if ($date === 'today') {
        $where[] = "DATE(next_followup_datetime) = CURRENT_DATE";
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    // Order by next_followup ASC (upcoming first), NULLs last, then created_at DESC
    $stmt = $pdo->prepare("
        SELECT *, 
        CASE WHEN next_followup_datetime IS NULL THEN 1 ELSE 0 END as no_followup
        FROM leads
        {$whereSQL}
        ORDER BY no_followup ASC, next_followup_datetime ASC, created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $leads = $stmt->fetchAll();

    return compact('leads','total','pages','page');
}

