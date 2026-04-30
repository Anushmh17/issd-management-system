<?php
// =====================================================
// ISSD Management - Help Guide Modal
// includes/help_modal.php
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
          <i class="fas fa-circle-info me-2"></i> ISSD Guide
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-4">
          <p class="text-muted" style="font-size:14px; line-height:1.6;">Welcome to the <strong>ISSD Management System</strong>. Here is a quick guide tailored to your role to help you navigate through the portal with ease.</p>
        </div>

        <div class="row g-4">
          <?php if ($role === 'admin'): ?>
            <div class="col-md-6">
              <div style="background:#f5f4ff; padding:25px; border-radius:16px; height:100%; border: 1px solid rgba(91, 78, 250, 0.1);">
                <h6 class="fw-700 mb-3" style="color:#5b4efa;"><i class="fas fa-user-shield me-2"></i> Core Management</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:2;">
                  <li><strong>Student Onboarding:</strong> Use the unified form to register students, select courses, and crop profile pictures.</li>
                  <li><strong>Lead Conversion:</strong> Track potential students and convert them to active records with one click.</li>
                  <li><strong>Course Catalog:</strong> Manage programs, fees, and assign lecturers to specific batches.</li>
                  <li><strong>Document Tracking:</strong> Monitor registration document completion via the live progress bars.</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div style="background:#f0fdf4; padding:25px; border-radius:16px; height:100%; border: 1px solid rgba(16, 185, 129, 0.1);">
                <h6 class="fw-700 mb-3" style="color:#10b981;"><i class="fas fa-chart-line me-2"></i> Operations & Finance</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:2;">
                  <li><strong>Payment Tracking:</strong> Record tuition fees and monitor student payment history.</li>
                  <li><strong>Urgent Alerts:</strong> Keep an eye on "Call Alerts" for scheduled lead follow-ups.</li>
                  <li><strong>Notifications:</strong> Check the Bell icon for system updates and "Closed" call history.</li>
                  <li><strong>Broadcasts:</strong> Send official notices to all students or specific lecturer groups.</li>
                </ul>
              </div>
            </div>

          <?php elseif ($role === 'lecturer'): ?>
            <div class="col-md-6">
              <div style="background:#fff7ed; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-chalkboard-user text-warning me-2"></i> Course Delivery</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>My Courses:</strong> View assigned courses and scheduled batches.</li>
                  <li><strong>Assignments:</strong> Create new assignments with deadlines and marks.</li>
                  <li><strong>Materials:</strong> Upload lecture slides and reading resources.</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div style="background:#f0f9ff; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-check-double text-info me-2"></i> Grading & Feedback</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Submissions:</strong> View and download student-submitted assignments.</li>
                  <li><strong>Marking:</strong> Assign marks and provide performance feedback.</li>
                  <li><strong>Interaction:</strong> Use "My Students" to track individual progress.</li>
                </ul>
              </div>
            </div>

          <?php else: ?>
            <div class="col-md-6">
              <div style="background:#f5f4ff; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-graduation-cap text-primary me-2"></i> My Learning</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Course Access:</strong> View your enrolled courses and course content.</li>
                  <li><strong>Assignments:</strong> Download tasks and track upcoming deadlines.</li>
                  <li><strong>Submissions:</strong> Submit your work digitally via the portal.</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div style="background:#fdf2f8; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-credit-card text-danger me-2"></i> Student Services</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Payments:</strong> Monitor your payment history and balance dues.</li>
                  <li><strong>Notices:</strong> Stay updated with important institute announcements.</li>
                  <li><strong>Profile:</strong> Update your contact details and qualifications.</li>
                </ul>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4 p-3 text-center" style="border-top:1px solid #eee;">
          <p class="mb-0" style="font-size:12px; color:#999;">Need more help? Contact Technical Support at <strong>support@issd.com</strong></p>
        </div>
      </div>
    </div>
  </div>
</div>


