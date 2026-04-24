<?php
// =====================================================
// LEARN Management - Help Guide Modal
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
          <i class="fas fa-circle-info me-2"></i> LEARN Guide
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-4">
          <p class="text-muted" style="font-size:14px; line-height:1.6;">Welcome to the <strong>LEARN Management System</strong>. Here is a quick guide tailored to your role to help you navigate through the portal with ease.</p>
        </div>

        <div class="row g-4">
          <?php if ($role === 'admin'): ?>
            <div class="col-md-6">
              <div style="background:#f5f4ff; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-user-shield text-primary me-2"></i> Administrative Controls</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Management:</strong> Add and monitor students, lecturers, and courses.</li>
                  <li><strong>Enrollments:</strong> Review and approve student course registrations.</li>
                  <li><strong>Finance:</strong> Track student fee payments and manage lecturer payrolls.</li>
                  <li><strong>Broadcasting:</strong> Post official notices to specific audiences.</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div style="background:#f0fdf4; padding:20px; border-radius:12px; height:100%;">
                <h6 class="fw-700 mb-3"><i class="fas fa-chart-pie text-success me-2"></i> Insights & Reports</h6>
                <ul class="text-muted" style="font-size:13px; padding-left:18px; line-height:1.8;">
                  <li><strong>Dashboard:</strong> Real-time overview of active students and total revenue.</li>
                  <li><strong>Reports:</strong> Generate detailed reports on enrollments and attendance.</li>
                  <li><strong>Alerts:</strong> Monitor overdue payments in red on the dashboard.</li>
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
          <p class="mb-0" style="font-size:12px; color:#999;">Need more help? Contact Technical Support at <strong>support@learn.management</strong></p>
        </div>
      </div>
    </div>
  </div>
</div>
