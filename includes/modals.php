<?php
// =====================================================
// LEARN Management - Global Modals
// includes/modals.php
// =====================================================
if (!isset($role)) {
    $role = $_SESSION['user']['role'] ?? 'student';
}
?>

<!-- Help Guide Modal -->
<div class="modal fade" id="helpGuideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered shadow-lg">
    <div class="modal-content lms-modal" style="border:none; border-radius:18px;">
      <div class="modal-header border-0 pb-0 mt-2 px-4 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-700" style="color:var(--primary); font-size:22px;">
          <i class="fas fa-circle-info me-2"></i> LEARN Guide
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Content remains the same as previously implemented -->
        <div class="mb-4">
          <p class="text-muted" style="font-size:14px; line-height:1.6;">Welcome to the <strong>LEARN Management System</strong>. Here is a quick guide tailored to your role.</p>
        </div>
        <div class="row g-4">
          <?php if ($role === 'admin'): ?>
            <div class="col-md-6">
              <div style="background:#f5f4ff; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-user-shield text-primary me-2"></i> Administrative Controls</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Management:</strong> Add and monitor students, lecturers, and courses.</li>
                  <li><strong>Finance:</strong> Track student fee payments and manage lecturer payrolls.</li>
                </ul>
              </div>
            </div>
          <?php elseif ($role === 'lecturer'): ?>
            <div class="col-md-6">
              <div style="background:#fff7ed; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-chalkboard-user text-warning me-2"></i> Course Delivery</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Assignments:</strong> Create new assignments with deadlines and marks.</li>
                  <li><strong>Materials:</strong> Upload lecture slides and reading resources.</li>
                </ul>
              </div>
            </div>
          <?php else: ?>
            <div class="col-md-6">
              <div style="background:#f5f4ff; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-graduation-cap text-primary me-2"></i> My Learning</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Assignments:</strong> Download tasks and submit your work digitally.</li>
                  <li><strong>Payments:</strong> Monitor your payment history and balance dues.</li>
                </ul>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notice Viewer Modal -->
<div class="modal fade" id="viewNoticeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content lms-modal" style="border:none; border-radius:18px; overflow:hidden;">
        <div id="notice-modal-header" style="padding:40px 30px 20px 30px; background: linear-gradient(135deg, var(--primary), var(--accent)); color:#fff; position:relative;">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position:absolute; top:20px; right:20px;"></button>
            <div id="notice-modal-badge" class="badge bg-white text-primary mb-3" style="font-size:10px; text-transform:uppercase; letter-spacing:1px; font-weight:700;">OFFICIAL NOTICE</div>
            <h3 id="notice-modal-title" class="fw-800 m-0" style="font-size:24px; line-height:1.2;">Notice Title</h3>
        </div>
        <div class="modal-body p-4 pt-4">
            <div class="d-flex align-items-center gap-15 mb-4 p-3" style="background:#f8fafc; border-radius:12px; border:1px solid #f1f5f9;">
                <div id="notice-modal-avatar" style="width:45px; height:45px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px;">A</div>
                <div>
                   <div id="notice-modal-author" class="fw-700" style="color:var(--text-main); font-size:14px;">Super Admin</div>
                   <div id="notice-modal-date" class="text-muted" style="font-size:12px;">Apr 09, 2026</div>
                </div>
            </div>
            <div id="notice-modal-content" style="font-size:15px; line-height:1.8; color:#475569; white-space:pre-wrap;">
                Notice content goes here...
            </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn-lms btn-primary w-100" data-bs-dismiss="modal"><?= ($role === 'admin') ? 'Close Preview' : "I've Read This" ?></button>
        </div>
    </div>
  </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
    <div class="modal-content lms-modal">
      <div class="modal-body text-center p-4">
        <div class="lms-modal-icon mb-3">
          <i class="fas fa-right-from-bracket"></i>
        </div>
        <h3 class="fw-700 mb-2" style="font-size:20px;color:var(--text-main);">Terminate Session?</h3>
        <p class="text-muted mb-4" style="font-size:13.5px;">Are you sure you want to logout?</p>
        <div class="d-flex gap-10">
          <button type="button" class="btn-lms btn-outline w-100" data-bs-dismiss="modal">Keep Me In</button>
          <a href="<?= BASE_URL ?>/logout.php" class="btn-lms btn-danger w-100">Yes, Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
/* Modal & Notice Aesthetic */
.lms-modal {
  border: none !important;
  border-radius: 20px !important;
  background: #fff !important;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1) !important;
}
.notice-card-clickable {
  cursor: pointer;
  transition: all 0.3s ease;
}
.notice-card-clickable:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.05);
  border-color: var(--primary) !important;
}
@keyframes slideInRight {
  from { opacity: 0; transform: translateX(30px); }
  to   { opacity: 1; transform: translateX(0); }
}
</style>
