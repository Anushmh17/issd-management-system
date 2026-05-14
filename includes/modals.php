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
          <?php if ($role === 'admin'): ?>
          <!-- Admin Guide Nav -->
          <div class="col-md-3" style="background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05); padding: 30px 20px; height: 560px; overflow-y: auto;">
             <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="width:64px; height:64px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius:20px; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:28px; box-shadow:0 10px 25px rgba(16, 185, 129, 0.3);">
                   <i class="fas fa-book"></i>
                </div>
                <h5 class="fw-800" style="color:#fff; font-size:17px; margin:0;">Admin Guide</h5>
                <p class="text-muted" style="font-size:11px; opacity:0.6;">Version 1.1.0</p>
             </div>
             <div class="nav flex-column nav-pills help-nav" id="v-pills-tab" role="tablist">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#help-leads" type="button"><i class="fas fa-headset"></i> Leads</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-students" type="button"><i class="fas fa-user-graduate"></i> Students</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-courses" type="button"><i class="fas fa-graduation-cap"></i> Courses</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-payroll" type="button"><i class="fas fa-hand-holding-dollar"></i> Lecturer Pays</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-finance" type="button"><i class="fas fa-wallet"></i> Finance Hub</button>
             </div>
          </div>
          <!-- Admin Content Area -->
          <div class="col-md-9 help-content-area" style="padding: 45px 50px; position:relative; height: 560px; overflow-y: auto; background: #fff;">
             <button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute; top:25px; right:25px; opacity: 0.5; transition: 0.3s;"></button>
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
                      <p>Define Course Names, Codes (e.g., FSD-2026), and Durations. Each course has a fixed <strong>Monthly Fee</strong> used for student billing.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Staff Assignments</h6>
                      <p>Assign Lecturers to courses via <strong>Courses > Assign Lecturer</strong>. This allows lecturers to see their assigned students, manage assignments, and view submissions.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Batch Tracking</h6>
                      <p>Monitor student progress through courses. Mark students as <strong>Completed</strong> or <strong>Dropped Out</strong> to maintain accurate graduation reports.</p>
                   </div>
                   <div class="help-section">
                      <h6>4. Certificates</h6>
                      <p>Once a student completes a course, issue a certificate via <strong>Certificates > Issue Certificate</strong>. This automatically marks their enrollment as Completed.</p>
                   </div>
                </div>
                <!-- PAYROLL SECTION -->
                <div class="tab-pane fade" id="help-payroll">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Lecturer Payroll</h4>
                   <div class="help-section">
                      <h6>1. Payroll Hub</h6>
                      <p>Go to <strong>Lecturer Pays</strong> in the sidebar. The hub shows total payouts, active lecturers, and payroll completion status for the current month.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Who Hasn't Been Paid?</h6>
                      <p>Click the <strong>Payroll Status card</strong> to open a modal listing all unpaid lecturers. Each row has a <strong>Pay Now</strong> button that takes you straight to their payout form.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Recording a Payout</h6>
                      <p>The payout form works in 3 steps:</p>
                      <ol style="font-size:14px; color:#64748b; line-height:2;">
                         <li>Select the <strong>Lecturer</strong> → their courses load automatically</li>
                         <li>Optionally select a <strong>Course</strong> (shows enrolled student count)</li>
                         <li>Choose a <strong>Payment Mode:</strong>
                            <ul>
                               <li><strong>Flat Monthly</strong> — Fixed amount (pre-filled from course fee)</li>
                               <li><strong>Per Student</strong> — Enter rate × student count, total auto-calculates</li>
                            </ul>
                         </li>
                      </ol>
                   </div>
                   <div class="help-section">
                      <h6>4. Automatic Payout Alerts</h6>
                      <p>From the <strong>20th of each month</strong>, the system generates a notification for every unpaid active lecturer. These appear in the <strong>Payments</strong> tab of the notification bell and clear automatically once a payout is recorded.</p>
                   </div>
                </div>
                <!-- FINANCE SECTION -->
                <div class="tab-pane fade" id="help-finance">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Finance & Notifications</h4>
                   <div class="help-section">
                      <h6>1. Student Fee Collection</h6>
                      <p>Navigate to <strong>Student Payments > Add Payment</strong>. The system automatically calculates pending dues based on the student's enrollment date and course fee, including any carried-over balance.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Finance Hub</h6>
                      <p>The <strong>Finance Hub</strong> gives a consolidated view of monthly revenue (student payments) vs. expenses (lecturer payouts) and shows net income with a transaction feed.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Notification Bell Categories</h6>
                      <p>The 🔔 bell in the header refreshes every 5 seconds and is split into tabs:</p>
                      <ul class="mt-2" style="font-size:14px; color:#64748b; line-height:2;">
                         <li><strong>All:</strong> Every system update.</li>
                         <li><strong>Calls:</strong> Overdue lead follow-up alerts.</li>
                         <li><strong>Payments:</strong> Student overdue/due-soon alerts + lecturer payout due alerts.</li>
                         <li><strong>System:</strong> Technical alerts and registration logs.</li>
                      </ul>
                   </div>
                   <div class="help-section">
                      <h6>4. Arrears Management</h6>
                      <p>Students with overdue payments are automatically flagged. Check the <strong>Payment Alerts</strong> page for a categorized view: <span style="color:#ef4444;">Overdue</span>, <span style="color:#f59e0b;">Due Soon</span>, and <span style="color:#f97316;">High Balance</span>.</p>
                   </div>
                </div>
             </div>
             <div class="mt-4 pt-4 text-center" style="border-top:1px solid #f1f5f9;">
                <p class="text-muted mb-0" style="font-size:12px;">Need deeper assistance? Email <span style="color:var(--primary); font-weight:700;">support@issd.com</span></p>
             </div>
          </div>

          <?php elseif ($role === 'lecturer'): ?>
          <!-- Lecturer Guide Nav -->
          <div class="col-md-3" style="background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05); padding: 30px 20px; height: 560px; overflow-y: auto;">
             <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="width:64px; height:64px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius:20px; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:28px; box-shadow:0 10px 25px rgba(245, 158, 11, 0.3);">
                   <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h5 class="fw-800" style="color:#fff; font-size:17px; margin:0;">Lecturer Guide</h5>
                <p class="text-muted" style="font-size:11px; opacity:0.6;">Version 1.0.0</p>
             </div>
             <div class="nav flex-column nav-pills help-nav" id="v-pills-tab" role="tablist">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#help-lec-courses" type="button"><i class="fas fa-book-open"></i> My Courses</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-lec-assignments" type="button"><i class="fas fa-file-alt"></i> Assignments</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-lec-students" type="button"><i class="fas fa-users"></i> Students</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-lec-payroll" type="button"><i class="fas fa-sack-dollar"></i> Finance & Payouts</button>
             </div>
          </div>
          <!-- Lecturer Content Area -->
          <div class="col-md-9 help-content-area" style="padding: 45px 50px; position:relative; height: 560px; overflow-y: auto; background: #fff;">
             <button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute; top:25px; right:25px; opacity: 0.5; transition: 0.3s;"></button>
             <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="help-lec-courses">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">My Courses</h4>
                   <div class="help-section">
                      <h6>1. Course Dashboard</h6>
                      <p>Navigate to the <strong>My Courses</strong> tab to view all academic modules assigned to you by the administration. Clicking on a course provides an overview of the syllabus and the total student count.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Student Engagement</h6>
                      <p>Monitor the real-time active or completed status of your assigned batches. This helps in tailoring your lecture pace and identifying groups that need extra attention.</p>
                   </div>
                </div>
                <div class="tab-pane fade" id="help-lec-assignments">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Assignments & Grading</h4>
                   <div class="help-section">
                      <h6>1. Creating Assignments</h6>
                      <p>Go to <strong>Assignments > Add Assignment</strong>. You must select the target course, write comprehensive instructions, and attach any required reference materials (PDF, DOCX, ZIP files under 15MB).</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Strict Deadlines</h6>
                      <p>Always set a firm <strong>Due Date</strong>. The LMS automatically enforces this deadline, restricting late submissions to maintain academic discipline.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Grading & Feedback Loops</h6>
                      <p>When students submit their work, you will receive an instant dashboard notification. Navigate to <strong>Submissions</strong>, download their files, and enter a numerical mark alongside constructive feedback. Once saved, your feedback is immediately pushed to the student's personal portal.</p>
                   </div>
                </div>
                <div class="tab-pane fade" id="help-lec-students">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Student Management</h4>
                   <div class="help-section">
                      <h6>1. Student Directory</h6>
                      <p>Click <strong>My Students</strong> to access a complete, filterable roster of every student enrolled under your active courses. You can view their basic contact info to facilitate academic communication.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Announcements</h6>
                      <p>Utilize the <strong>Notice Board</strong> to post global announcements. This is the fastest way to communicate class rescheduling, exam tips, or resource links to your batches.</p>
                   </div>
                </div>
                <div class="tab-pane fade" id="help-lec-payroll">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Finance & Payouts</h4>
                   <div class="help-section">
                      <h6>1. Payout Receipts</h6>
                      <p>The system transparently tracks all compensation issued by the Admin. Whenever a payout is recorded to your account, you will receive an instant notification detailing the exact amount and the cleared month.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Automated Reminders</h6>
                      <p>You do not need to manually request payments. If the 20th of the month passes without a recorded payout, the LMS automatically alerts the Administrator to process your payroll.</p>
                   </div>
                </div>
             </div>
             <div class="mt-4 pt-4 text-center" style="border-top:1px solid #f1f5f9;">
                <p class="text-muted mb-0" style="font-size:12px;">Need deeper assistance? Email <span style="color:var(--primary); font-weight:700;">support@issd.com</span></p>
             </div>
          </div>

          <?php else: ?>
          <!-- Student Guide Nav -->
          <div class="col-md-3" style="background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05); padding: 30px 20px; height: 560px; overflow-y: auto;">
             <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="width:64px; height:64px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius:20px; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:28px; box-shadow:0 10px 25px rgba(59, 130, 246, 0.3);">
                   <i class="fas fa-user-graduate"></i>
                </div>
                <h5 class="fw-800" style="color:#fff; font-size:17px; margin:0;">Student Guide</h5>
                <p class="text-muted" style="font-size:11px; opacity:0.6;">Version 1.0.0</p>
             </div>
             <div class="nav flex-column nav-pills help-nav" id="v-pills-tab" role="tablist">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#help-stu-courses" type="button"><i class="fas fa-book-open"></i> Courses</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-stu-assignments" type="button"><i class="fas fa-file-alt"></i> Assignments</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-stu-payments" type="button"><i class="fas fa-money-bill-wave"></i> Payments</button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#help-stu-notices" type="button"><i class="fas fa-bell"></i> Alerts & Notices</button>
             </div>
          </div>
          <!-- Student Content Area -->
          <div class="col-md-9 help-content-area" style="padding: 45px 50px; position:relative; height: 560px; overflow-y: auto; background: #fff;">
             <button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute; top:25px; right:25px; opacity: 0.5; transition: 0.3s;"></button>
             <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="help-stu-courses">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">My Courses</h4>
                   <div class="help-section">
                      <h6>1. Course Library</h6>
                      <p>Navigate to the <strong>My Courses</strong> tab to view your enrolled programs. Here you can find details about the curriculum and your assigned lecturers.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Lecturer Information</h6>
                      <p>Each active course explicitly lists your assigned lecturer. You can await their specific module announcements on the Notice Board.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Completion Status</h6>
                      <p>Track your academic progression. Once you fulfill all coursework requirements, your status will transition from "Ongoing" to "Completed", and a digital Certificate of Completion will be logged by the Admin.</p>
                   </div>
                </div>
                <div class="tab-pane fade" id="help-stu-assignments">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Assignments</h4>
                   <div class="help-section">
                      <h6>1. Viewing Tasks</h6>
                      <p>Go to the <strong>Assignments</strong> section to see pending homework. Pay close attention to the <strong>Due Date</strong>; the system enforces strict deadlines and may block late submissions.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Submitting Work</h6>
                      <p>Click "Submit", upload your completed digital file (PDF, DOCX, ZIP formats up to 15MB are supported), and wait for the green confirmation toast.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Grades & Feedback</h6>
                      <p>The moment your Lecturer grades your work, an alert triggers in your notification bell. Click it to review your lecturer's comments, pinpoint areas for improvement, and see your final marks.</p>
                   </div>
                </div>
                <div class="tab-pane fade" id="help-stu-payments">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Payments</h4>
                   <div class="help-section">
                      <h6>1. Payment Ledger</h6>
                      <p>The <strong>Payments</strong> tab acts as a secure financial ledger. It tracks all your past tuition receipts, allowing you to verify your historical payment status at any time.</p>
                   </div>
                   <div class="help-section">
                      <h6>2. Real-Time Balances</h6>
                      <p>Your outstanding balance is calculated automatically based on your initial course fee and your payment history, ensuring complete financial transparency.</p>
                   </div>
                   <div class="help-section">
                      <h6>3. Automated Reminders</h6>
                      <p>The system proactively sends you an alert <strong>5 days before</strong> a monthly installment is due, and another alert on the exact due date. Please do not ignore these alerts to avoid late penalties or account locks.</p>
                   </div>
                </div>
                <div class="tab-pane fade" id="help-stu-notices">
                   <h4 class="fw-800 mb-4" style="color:var(--primary);">Alerts & Notices</h4>
                   <div class="help-section">
                      <h6>1. Global Announcements</h6>
                      <p>The dashboard <strong>Notice Board</strong> highlights critical, urgent alerts from the Administration or your Lecturers (e.g., holiday declarations, class cancellations, or schedule changes).</p>
                   </div>
                   <div class="help-section">
                      <h6>2. The Notification Bell</h6>
                      <p>Always check the 🔔 bell icon in the top right. It categorizes your personal alerts into tabs (All, Payments, System) so you instantly know when you've received a payment receipt, assignment grade, or new course enrollment.</p>
                   </div>
                </div>
             </div>
             <div class="mt-4 pt-4 text-center" style="border-top:1px solid #f1f5f9;">
                <p class="text-muted mb-0" style="font-size:12px;">Need deeper assistance? Email <span style="color:var(--primary); font-weight:700;">support@issd.com</span></p>
             </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>


<style>
.help-nav .nav-link {
   display: flex;
   align-items: center;
   text-align: left;
   padding: 14px 20px;
   border-radius: 14px;
   color: #94a3b8;
   font-weight: 700;
   font-size: 13px;
   margin-bottom: 8px;
   border: none;
   transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
   background: transparent;
   gap: 12px;
}
.help-nav .nav-link i {
   font-size: 16px;
   opacity: 0.5;
   transition: 0.3s;
}
.help-nav .nav-link:hover {
   background: rgba(255, 255, 255, 0.05);
   color: #fff;
}
.help-nav .nav-link:hover i {
   opacity: 1;
   transform: translateX(3px);
}
.help-nav .nav-link.active {
   background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
   color: #fff !important;
   box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
}
.help-nav .nav-link.active i {
   opacity: 1 !important;
   color: #fff !important;
}

body.lms-dark-mode .help-content-area { background: #1e293b !important; }
body.lms-dark-mode .help-section h6 { color: #fff !important; }
body.lms-dark-mode .help-section p { color: #94a3b8 !important; }
body.lms-dark-mode .help-content-area .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

/* Global Modal Dark Mode */
body.lms-dark-mode .lms-modal {
    background: #0f172a !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
}

body.lms-dark-mode #notice-modal-content {
    color: #cbd5e1 !important;
}

body.lms-dark-mode .author-box {
    background: rgba(255, 255, 255, 0.03) !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
}

body.lms-dark-mode #notice-modal-author {
    color: #fff !important;
}

body.lms-dark-mode #notice-modal-badge {
    background: rgba(34, 211, 238, 0.1) !important;
    color: #22d3ee !important;
}



.help-section p {
   font-size: 14px;
   color: #64748b;
   line-height: 1.6;
}

/* Hide Modal Sidebar Scrollbar Only */
#helpGuideModal .col-md-3::-webkit-scrollbar {
    display: none !important;
}
#helpGuideModal .col-md-3 {
    -ms-overflow-style: none !important;
    scrollbar-width: none !important;
}
</style>

<!-- Notice Viewer Modal (Premium UI) -->
<div class="modal fade" id="viewNoticeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content lms-modal" style="border:none; border-radius:28px; overflow:hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <!-- Premium Indigo Header -->
        <div id="notice-modal-header" style="padding:45px 35px 25px 35px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color:#fff; position:relative;">
            <div style="position:absolute; top:0; left:0; right:0; bottom:0; background: url('https://www.transparenttextures.com/patterns/cubes.png'); opacity:0.1;"></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position:absolute; top:25px; right:25px; opacity:0.8; transition:0.3s;"></button>
            
            <div id="notice-modal-badge" style="display:inline-block; padding:4px 12px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border-radius:8px; font-size:10px; text-transform:uppercase; letter-spacing:1.5px; font-weight:800; margin-bottom:15px; border:1px solid rgba(255,255,255,0.3);">
                <i class="fas fa-shield-halved" style="margin-right:6px;"></i> Official Notice
            </div>
            <h3 id="notice-modal-title" class="fw-800 m-0" style="font-size:28px; line-height:1.2; letter-spacing:-0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.1);">Notice Title</h3>
        </div>

        <div class="modal-body p-35" style="padding:35px;">
            <!-- Premium Author Box -->
            <div class="d-flex align-items-center gap-3 mb-30 p-3 author-box" style="background: rgba(99, 102, 241, 0.03); border-radius:20px; border:1px solid rgba(99, 102, 241, 0.1); padding:15px 20px !important;">
                <div id="notice-modal-avatar" style="width:50px; height:50px; border-radius:16px; background:linear-gradient(135deg, #6366f1, #8b5cf6); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:20px; box-shadow: 0 8px 15px rgba(99, 102, 241, 0.2);">A</div>
                <div>
                   <div class="text-muted fw-700" style="font-size:10px; text-transform:uppercase; letter-spacing:1px; margin-bottom:2px;">Issued By</div>
                   <div id="notice-modal-author" class="fw-800" style="color:var(--text-main); font-size:16px; line-height:1;">Super Admin</div>
                </div>
                <div class="ms-auto text-end">
                    <div id="notice-modal-date" class="fw-700" style="font-size:13px; color:#6366f1;">Apr 09, 2026</div>
                    <div class="text-muted" style="font-size:10px; font-weight:600;">Broadcast Date</div>
                </div>
            </div>

            <!-- Content -->
            <div id="notice-modal-content" style="font-size:16px; line-height:1.8; color:var(--text-main); white-space:pre-wrap; opacity:0.9; font-weight:500;">
                Notice content goes here...
            </div>
        </div>

        <div class="modal-footer border-0 p-35 pt-0" style="padding:35px; padding-top:0;">
            <button type="button" class="btn-lms btn-primary w-100 shadow-premium" onclick="markNoticeAsReadGlobal()" style="border-radius:16px; padding:14px; font-weight:800; letter-spacing:0.5px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid rgba(255,255,255,0.05);">
                Acknowledge & Close
            </button>
        </div>
    </div>
  </div>
</div>

<script>
function markNoticeAsReadGlobal() {
    const modalEl = document.getElementById('viewNoticeModal');
    if (!modalEl) return;
    
    // Hide modal manually first for snappy feel
    if (typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getInstance(modalEl);
        if (bsModal) bsModal.hide();
    }

    // Prevent admins from accidentally marking notices as read during preview
    if (window.location.pathname.includes('/admin/')) {
        return;
    }

    const noticeId = modalEl.dataset.noticeId;
    const isRead = modalEl.dataset.isRead === '1';

    if (!noticeId || isRead) {
        return;
    }

    // Immediately update UI to feel responsive
    const premiumItem = document.querySelector(`.notice-premium-card[data-id="${noticeId}"]`);
    if (premiumItem) {
        premiumItem.dataset.isRead = '1';
        premiumItem.classList.replace('is-unread', 'is-read');
        const indicator = premiumItem.querySelector('.unread-indicator');
        if (indicator) {
            const readBadge = document.createElement('span');
            readBadge.className = 'badge-lms success outline';
            readBadge.style.cssText = 'font-size:9px; padding:2px 8px; border-radius:100px; font-weight:800; letter-spacing:0.5px;';
            readBadge.textContent = 'READ';
            indicator.parentNode.replaceChild(readBadge, indicator);
        }
    }

    const dashItem = document.querySelector(`.notice-item-lms[data-id="${noticeId}"]`);
    if (dashItem) {
        dashItem.dataset.isRead = '1';
        dashItem.classList.replace('is-unread', 'is-read');
        const indicator = dashItem.querySelector('.unread-indicator');
        if (indicator) {
            indicator.outerHTML = `<span class="badge-lms success outline" style="font-size:8px; padding:1px 6px; border-radius:100px;">READ</span>`;
        }
    }

    // Update Student Notices Card
    const studentCard = document.querySelector(`.notice-card-clickable[data-real-id="${noticeId}"]`);
    if (studentCard) {
        studentCard.dataset.isRead = '1';
        studentCard.classList.add('is-read');
        studentCard.style.opacity = '0.6';
        const header = studentCard.querySelector('div:first-child');
        if (header && !header.querySelector('.db-green')) {
            const badge = document.createElement('span');
            badge.className = 'dark-badge db-green';
            badge.style.background = 'rgba(34,197,94,0.1)';
            badge.style.color = '#4ade80';
            badge.innerHTML = '<i class="fas fa-check-circle"></i> READ';
            const indicator = header.querySelector('.unread-indicator');
            if (indicator) indicator.remove();
            const newBadge = header.querySelector('.db-red');
            if (newBadge) newBadge.remove();
            header.insertBefore(badge, header.children[1]);
        }
    }

    // Now send the fetch
    const url = '<?= BASE_URL ?>/backend/notice_read.php';
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          notice_id: noticeId,
          csrf_token: "<?= csrfToken() ?>"
        })
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP error ' + res.status);
        return res.json();
    })
    .then(data => {
        if (!data.success) {
            alert("Backend Error: " + data.error);
        }
    })
    .catch(err => {
        alert("Fetch Failed! Please tell me this error: " + err.message + " | URL: " + url);
    });
}
</script>
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
          <form action="<?= BASE_URL ?>/logout.php" method="POST" class="w-100">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-lms btn-danger w-100">Yes, Logout</button>
          </form>
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
.lms-modal-icon {
  width: 60px;
  height: 60px;
  background: rgba(91, 78, 250, 0.1);
  color: var(--primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  margin: 0 auto;
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

<!-- Action Confirmation Modal (Reusable) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
    <div class="modal-content lms-modal">
      <div class="modal-body text-center p-4">
        <div class="lms-modal-icon mb-3" id="confirm-modal-icon">
          <i class="fas fa-question-circle"></i>
        </div>
        <h3 class="fw-800 mb-2" style="font-size:22px; color:var(--text-main);" id="confirm-modal-title">Are you sure?</h3>
        <p class="text-muted mb-4" style="font-size:14px; line-height:1.5;" id="confirm-modal-message">Do you really want to perform this action? This might be permanent.</p>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-light py-3 rounded-4 fw-700 w-100" data-bs-dismiss="modal" style="font-size:13px; border:1px solid #e2e8f0;">Cancel</button>
          <button type="button" class="btn-lms btn-primary py-3 rounded-4 fw-800 w-100" id="confirm-modal-btn" style="font-size:13px; box-shadow:0 8px 15px rgba(91, 78, 250, 0.2);">Confirm Action</button>
        </div>
      </div>
    </div>
  </div>
</div>


