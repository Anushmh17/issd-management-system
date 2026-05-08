<?php
// =====================================================
// ISSD Management - Help Guide Modal (Enhanced)
// includes/help_modal.php
// =====================================================
if (!isset($role)) {
    $role = $_SESSION['user']['role'] ?? 'student';
}
?>
<style>
  .help-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s;
  }
  .help-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
  .help-card h6 { display: flex; align-items: center; gap: 8px; font-weight: 800; margin-bottom: 15px; }
  .help-card ul { list-style: none; padding: 0; margin: 0; }
  .help-card li { margin-bottom: 10px; font-size: 13px; color: #64748b; line-height: 1.4; position: relative; padding-left: 20px; }
  .help-card li::before { content: "\f058"; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; left: 0; top: 1px; color: var(--primary); font-size: 11px; }
  .help-card strong { color: #1e293b; }

  /* Dark Mode Overrides */
  body.lms-dark-mode .lms-modal { background: #0f172a !important; color: #f8fafc !important; border: 1px solid rgba(255,255,255,0.1) !important; }
  body.lms-dark-mode .help-card { background: rgba(30, 41, 59, 0.4) !important; border-color: rgba(255, 255, 255, 0.05) !important; }
  body.lms-dark-mode .help-card li { color: #94a3b8 !important; }
  body.lms-dark-mode .help-card strong { color: #f8fafc !important; }
  body.lms-dark-mode .modal-header .btn-close { filter: invert(1); }
</style>

<!-- Help Guide Modal -->
<div class="modal fade" id="helpGuideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content lms-modal" style="border:none; border-radius:24px; overflow:hidden;">
      <div class="modal-header border-0 pb-0 pt-4 px-4">
        <div>
          <h5 class="modal-title fw-800" style="color:var(--primary); font-size:24px; letter-spacing:-0.5px;">
            <i class="fas fa-book-open-reader me-2"></i> ISSD User Manual
          </h5>
          <p class="text-muted mb-0 mt-1" style="font-size:13px;">Comprehensive guide to the ISSD Management Ecosystem</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        
        <?php if ($role === 'admin'): ?>
          <div class="row g-4">
            <!-- Section 1: Student Lifecycle -->
            <div class="col-md-4">
              <div class="help-card" style="border-top: 4px solid #4f46e5;">
                <h6 style="color:#4f46e5;"><i class="fas fa-users-gear"></i> Student Lifecycle</h6>
                <ul>
                  <li><strong>Lead Management:</strong> Capture potential students. Use the "Convert" button to instantly move a lead to official registration.</li>
                  <li><strong>New Registration:</strong> Use the "Add Student" form. Essential fields include Name, Email, and Course selection.</li>
                  <li><strong>Profile Manager:</strong> Upload high-res photos. The built-in cropper ensures perfect alignment for ID cards.</li>
                  <li><strong>Enrollment Hub:</strong> Link students to specific batches. Monitor their "Ongoing" or "Completed" status live.</li>
                </ul>
              </div>
            </div>

            <!-- Section 2: Academics -->
            <div class="col-md-4">
              <div class="help-card" style="border-top: 4px solid #10b981;">
                <h6 style="color:#10b981;"><i class="fas fa-graduation-cap"></i> Academics</h6>
                <ul>
                  <li><strong>Course Catalog:</strong> Manage your academic portfolio. Define fees, durations, and status (Active/Inactive).</li>
                  <li><strong>Lecturer Management:</strong> Register staff and assign them unique handles. Monitor their course assignment load.</li>
                  <li><strong>Curriculum Assignment:</strong> Link lecturers to courses. This enables them to upload materials and create assignments.</li>
                  <li><strong>Performance Tracking:</strong> View overall student counts and completion rates per course.</li>
                </ul>
              </div>
            </div>

            <!-- Section 3: Finance & Operations -->
            <div class="col-md-4">
              <div class="help-card" style="border-top: 4px solid #f59e0b;">
                <h6 style="color:#f59e0b;"><i class="fas fa-sack-dollar"></i> Finance & Operations</h6>
                <ul>
                  <li><strong>Payment Recording:</strong> Log tuition fees manually. The system automatically calculates remaining balances.</li>
                  <li><strong>Financial Reports:</strong> Generate daily/monthly income statements and filter by course or student.</li>
                  <li><strong>Notices & Broadcasts:</strong> Use the Communication module to send announcements to specific audiences (e.g., Everyone, Lecturers).</li>
                  <li><strong>System Audit:</strong> Check the dashboard for "Call Alerts" and "Pending Documents" to maintain operational health.</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="mt-4 p-3 rounded-4" style="background: rgba(var(--primary-rgb), 0.05); border: 1px dashed var(--primary);">
             <div class="d-flex align-items-center gap-3">
               <div style="font-size:24px; color:var(--primary);"><i class="fas fa-lightbulb"></i></div>
               <div style="font-size:13px; color:#64748b;">
                 <strong>Pro Tip:</strong> Use the <strong>Global Search</strong> at the top of management pages to instantly find records by Name, ID, or Course Code. In Dark Mode, all badges are color-coded for instant status identification.
               </div>
             </div>
          </div>

        <?php elseif ($role === 'lecturer'): ?>
          <div class="row g-4">
            <div class="col-md-6">
              <div class="help-card">
                <h6 class="text-primary"><i class="fas fa-chalkboard-user"></i> Academic Delivery</h6>
                <ul>
                  <li><strong>Assignment Desk:</strong> Upload tasks with clear deadlines. Students are instantly notified upon publication.</li>
                  <li><strong>Material Vault:</strong> Keep your students updated by uploading PDF slides and resource links.</li>
                  <li><strong>Submissions:</strong> Monitor student uploads and download work for offline marking.</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="help-card">
                <h6 class="text-success"><i class="fas fa-check-double"></i> Grading & Feedback</h6>
                <ul>
                  <li><strong>Marking System:</strong> Enter marks directly. The system handles total calculations and ranking.</li>
                  <li><strong>Notice Board:</strong> View administrative directives and institute-wide holidays.</li>
                  <li><strong>Attendance:</strong> Use the student lists to track engagement within your assigned courses.</li>
                </ul>
              </div>
            </div>
          </div>

        <?php else: ?>
          <div class="row g-4">
            <div class="col-md-6">
              <div class="help-card">
                <h6 class="text-primary"><i class="fas fa-laptop-code"></i> Learning Portal</h6>
                <ul>
                  <li><strong>Dashboard:</strong> A real-time overview of your upcoming tasks and recent announcements.</li>
                  <li><strong>Course Content:</strong> Access your materials and download assignments from anywhere.</li>
                  <li><strong>Digital Submission:</strong> Upload your completed work securely through the portal.</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="help-card">
                <h6 class="text-danger"><i class="fas fa-user-check"></i> Account & Finance</h6>
                <ul>
                  <li><strong>Payment Wallet:</strong> Monitor your fee payments, balances, and download receipts.</li>
                  <li><strong>ID Verification:</strong> Ensure your profile picture is clear for the digital student ID card.</li>
                  <li><strong>Notices:</strong> Check for important updates regarding exams, holidays, and events.</li>
                </ul>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="mt-4 pt-4 border-top d-flex justify-content-between align-items-center">
          <div style="font-size:12px; color:#94a3b8;">&copy; 2026 ISSD Management System | Version 2.4.0</div>
          <div class="d-flex gap-3">
             <a href="mailto:support@issd.com" class="text-decoration-none" style="font-size:13px; font-weight:700; color:var(--primary);">
               <i class="fas fa-envelope me-1"></i> Technical Support
             </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
