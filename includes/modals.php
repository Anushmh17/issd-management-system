<?php
// =====================================================
// ISSD Management - Global Modals
// includes/modals.php
// =====================================================
if (!isset($role)) {
    $role = $_SESSION['user']['role'] ?? 'student';
}
?>

<!-- Help Guide Modal (Redesigned & Detailed) -->
<div class="modal fade" id="helpGuideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered shadow-lg">
    <div class="modal-content lms-modal" style="border:none; border-radius:24px; overflow:hidden;">
      <div class="modal-body p-0">
        <div class="row g-0">
          <!-- Sidebar Nav for Modal -->
          <div class="col-md-3" style="background: #f8fafc; border-right: 1px solid #e2e8f0; padding: 30px 20px;">
             <div class="text-center mb-4">
                <div style="width:60px; height:60px; background:var(--primary); border-radius:15px; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; box-shadow:0 10px 20px rgba(91, 78, 250, 0.2);">
                   <i class="fas fa-book-open"></i>
                </div>
                <h5 class="fw-800" style="color:var(--text-main); font-size:16px; margin:0;">ISSD Admin Guide</h5>
                <p class="text-muted" style="font-size:11px;">Version 1.0.2</p>
             </div>
             
             <div class="nav flex-column nav-pills help-nav" id="v-pills-tab" role="tablist">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#help-leads" type="button"><i class="fas fa-bullhorn me-2"></i> Leads & Calls</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-students" type="button"><i class="fas fa-user-graduate me-2"></i> Students & Docs</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-courses" type="button"><i class="fas fa-graduation-cap me-2"></i> Courses & Staff</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-finance" type="button"><i class="fas fa-wallet me-2"></i> Finance & Alerts</button>
             </div>
          </div>
          
          <!-- Content Area -->
          <div class="col-md-9" style="padding: 40px; position:relative;">
             <button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute; top:25px; right:25px;"></button>
             
             <div class="tab-content" id="v-pills-tabContent">
                <!-- LEADS SECTION -->
                <div class="tab-pane fade show active" id="help-leads">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Leads & Call Management</h4>
                   <div class="help-section">
                      <h6>1. Capturing Leads</h6>
                      <p>Record potential students via <strong>Leads > Add Lead</strong>. Ensure you capture the correct "Source" (WhatsApp, Facebook, etc.) to track marketing ROI.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Call Alerts & Toasts</h6>
                      <p>The system triggers <strong>Urgent Call Alerts</strong> based on the "Next Call Date" you set. These appear as toasts on your dashboard.</p>
                      <div class="alert alert-info py-2" style="font-size:13px; border-radius:10px;">
                         <strong>Pro Tip:</strong> Click "Close" on a toast to save it to your history. Dismissed calls can be reviewed in the <strong>Notifications Dropdown > Calls</strong> tab.
                      </div>
                   </div>
                   <div class="help-section">
                      <h6>3. Conversion</h6>
                      <p>Once a lead is ready to join, click <strong>"Convert to Student"</strong> on their profile. This pre-fills 80% of the registration form automatically.</p>
                   </div>
                </div>

                <!-- STUDENTS SECTION -->
                <div class="tab-pane fade" id="help-students">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Student Onboarding</h4>
                   <div class="help-section">
                      <h6>1. Registration Form</h6>
                      <p>Fill out the multi-section form. Note that <strong>Course Selection</strong> and <strong>NIC</strong> are mandatory fields.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Email Uniqueness</h6>
                      <p>The system enforces a policy where the <strong>Personal Email</strong> and <strong>Office Email</strong> must be unique to avoid communication conflicts.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Profile Photo & Cropping</h6>
                      <p>Upload a photo and wait for the <strong>Cropper Tool</strong> to open. You can zoom and center the face to ensure a professional student ID photo.</p>
                   </div>
                   <div class="help-section">
                      <h6>4. Document Checklist</h6>
                      <p>Track NIC, O/L, and A/L results. Use the <strong>"Collected"</strong> checkbox and specify which office branch holds the physical copy.</p>
                   </div>
                </div>

                <!-- COURSES SECTION -->
                <div class="tab-pane fade" id="help-courses">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Courses & Academic Management</h4>
                   <div class="help-section">
                      <h6>1. Program Management</h6>
                      <p>Define Course Names, Codes (e.g., FSD-2026), and Durations. Each course has a fixed <strong>Monthly Fee</strong> used for billing.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Staff Assignments</h6>
                      <p>Assign Lecturers to courses. This allows lecturers to see their assigned students, upload materials, and manage assignments.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Batch Tracking</h6>
                      <p>Monitor student progress through courses. Mark students as <strong>Completed</strong> or <strong>Dropped Out</strong> to maintain accurate graduation reports.</p>
                   </div>
                </div>

                <!-- FINANCE SECTION -->
                <div class="tab-pane fade" id="help-finance">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Finance & Notifications</h4>
                   <div class="help-section">
                      <h6>1. Fee Collection</h6>
                      <p>Navigate to <strong>Payments > Add Payment</strong>. The system will automatically calculate pending dues based on the student's enrollment date and course fee.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Notification Dropdown</h6>
                      <p>The Bell icon in the header is divided into categories:
                         <ul class="mt-2">
                            <li><strong>All:</strong> Every system update.</li>
                            <li><strong>Calls:</strong> Log of closed lead calls.</li>
                            <li><strong>System:</strong> Technical alerts and registration logs.</li>
                         </ul>
                      </p>
                   </div>
                   <div class="help-section">
                      <h6>3. Arrears Management</h6>
                      <p>Students with overdue payments are flagged in red. Use the <strong>"Payments Report"</strong> to generate a list of pending fees for the current month.</p>
                   </div>
                </div>
             </div>
             
             <div class="mt-4 pt-4 text-center" style="border-top:1px solid #f1f5f9;">
                <p class="text-muted mb-0" style="font-size:12px;">Need deeper assistance? Email <span style="color:var(--primary); font-weight:700;">support@issd.com</span></p>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.help-nav .nav-link {
   text-align: left;
   padding: 14px 20px;
   border-radius: 12px;
   color: #64748b;
   font-weight: 600;
   font-size: 14px;
   margin-bottom: 8px;
   border: 1px solid transparent;
   transition: all 0.2s;
}
.help-nav .nav-link:hover {
   background: #fff;
   color: var(--primary);
   border-color: #e2e8f0;
}
.help-nav .nav-link.active {
   background: #fff !important;
   color: var(--primary) !important;
   border-color: var(--primary);
   box-shadow: 0 4px 12px rgba(91, 78, 250, 0.08);
}
.help-section {
   margin-bottom: 25px;
}
.help-section h6 {
   font-weight: 700;
   color: var(--text-main);
   margin-bottom: 8px;
   font-size: 15px;
}
.help-section p {
   font-size: 14px;
   color: #64748b;
   line-height: 1.6;
}
</style>

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


