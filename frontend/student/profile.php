<?php
// =====================================================
// LEARN Management - Student Profile
// frontend/student/profile.php
// =====================================================
define('PAGE_TITLE', 'My Profile');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);

$user = currentUser();
$userId = (int)$user['id'];

// Get Student core details
$stmt = $pdo->prepare("
    SELECT s.id as student_id, s.full_name, s.student_id as student_reg, s.status, u.avatar
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ?
");
$stmt->execute([$userId]);
$student = $stmt->fetch();

if (!$student) {
    die("Student profile not found.");
}
$studentId = $student['student_id'];

// Handle Qualification Upload
$uploadDir = BASE_PATH . '/assets/qualifications/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_qual') {
    $type = $_POST['type'] ?? '';
    if (in_array($type, ['OL', 'AL', 'NVQ', 'Other']) && !empty($_FILES['file']['name'])) {
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf' && $file['size'] <= 5 * 1024 * 1024) {
            $filename = 'Q_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $pdo->prepare("INSERT INTO student_qualifications (student_id, type, file_path) VALUES (?, ?, ?)")
                    ->execute([$studentId, $type, $filename]);
                setFlash('success', 'Qualification uploaded successfully.');
            } else {
                setFlash('danger', 'Failed to save uploaded file.');
            }
        } else {
            setFlash('danger', 'Invalid file. Please upload a PDF under 5MB.');
        }
    } else {
        setFlash('danger', 'Invalid form data.');
    }
    header('Location: profile.php');
    exit;
}

// Fetch existing qualifications
$stmt = $pdo->prepare("SELECT * FROM student_qualifications WHERE student_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$studentId]);
$quals = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header">
    <div class="page-header-left">
      <h1>My Profile</h1>
      <div class="breadcrumb-custom">
        <i class="fas fa-home"></i> Student &rsaquo; <span>Profile</span>
      </div>
    </div>
  </div>

  <div class="row g-4">
    
    <!-- Left Column: Basic Info -->
    <div class="col-lg-4">
      <div class="card-lms" style="text-align:center;">
        <div class="card-lms-body" style="padding:40px 20px;">
          <div style="position:relative;display:inline-block;margin-bottom:20px;">
            <?php if ($student['avatar']): ?>
              <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($student['avatar']) ?>" 
                   style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:0 10px 25px rgba(0,0,0,0.1);">
            <?php else: ?>
              <div style="width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:700;margin:0 auto;box-shadow:0 10px 25px rgba(91,78,250,0.3);">
                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
          </div>
          
          <h3 class="fw-700" style="font-size:20px;margin-bottom:4px;"><?= htmlspecialchars($student['full_name']) ?></h3>
          <div style="font-size:14px;color:#64748b;margin-bottom:16px;">System ID: <?= htmlspecialchars($student['student_reg']) ?></div>
          
          <?php 
            $statusStr = strtolower($student['status'] ?? 'new');
            $stBadge = 'info';
            if ($statusStr === 'active') $stBadge = 'success';
            if ($statusStr === 'dropout') $stBadge = 'danger';
            if ($statusStr === 'completed') $stBadge = 'primary';
            if ($statusStr === 'new') $stBadge = 'warning';
          ?>
          <div style="display:inline-block;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;" class="badge-lms <?= $stBadge ?>">
            Status: <?= htmlspecialchars($statusStr) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Qualifications Upload -->
    <div class="col-lg-8">
      <div class="card-lms mb-20">
        <div class="card-lms-header">
          <div class="card-lms-title">
            <i class="fas fa-file-pdf" style="color:#ef4444;"></i> Educational Qualifications (PDF Only)
          </div>
        </div>
        <div class="card-lms-body">
          <form method="POST" action="profile.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_qual">
            <div class="row g-3" style="align-items:flex-end;">
              <div class="col-md-4">
                <label style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;">Document Type</label>
                <select name="type" class="form-control-lms" required>
                  <option value="">— Select —</option>
                  <option value="OL">O/L Certificate</option>
                  <option value="AL">A/L Certificate</option>
                  <option value="NVQ">NVQ Level</option>
                  <option value="Other">Other Certificate</option>
                </select>
              </div>
              <div class="col-md-6">
                <label style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;">Select PDF File (Max 5MB)</label>
                <input type="file" name="file" class="form-control-lms" accept=".pdf" required>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn-primary-grad" style="width:100%;height:42px;"><i class="fas fa-upload"></i></button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card-lms">
        <div class="card-lms-header" style="border:none;padding-bottom:0;">
          <div style="font-size:14px;font-weight:700;color:#334155;">Uploaded Documents</div>
        </div>
        <div class="card-lms-body">
          <?php if (empty($quals)): ?>
            <div class="empty-state">
              <i class="fas fa-folder-open"></i>
              <p>No qualifications uploaded yet.</p>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($quals as $q): ?>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                <div style="display:flex;align-items:center;gap:12px;">
                  <div style="width:40px;height:40px;border-radius:8px;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:18px;">
                    <i class="fas fa-file-pdf"></i>
                  </div>
                  <div>
                    <div class="fw-700" style="color:#334155;font-size:14px;"><?= htmlspecialchars($q['type']) ?> Certificate</div>
                    <div style="font-size:11px;color:#94a3b8;"><?= date('d M Y, h:i A', strtotime($q['uploaded_at'])) ?></div>
                  </div>
                </div>
                <a href="<?= BASE_URL ?>/assets/qualifications/<?= htmlspecialchars($q['file_path']) ?>" target="_blank" class="btn-lms btn-outline btn-sm">
                  <i class="fas fa-eye"></i> View
                </a>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div> <!-- row -->

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
