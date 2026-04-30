<?php
// =====================================================
// ISSD Management - Shared Footer
// =====================================================
$year = date('Y');
?>
  <!-- ===== PAGE FOOTER ===== -->
  <footer id="page-footer">
    &copy; <?= $year ?> <strong>ISSD Management</strong>. All rights reserved. &nbsp;|&nbsp;
    Institute Management System &nbsp;&bull;&nbsp; Version 1.0
  </footer>

</div><!-- /#main-content -->
</div><!-- /#app-wrapper -->

<!-- Toast Notification Container -->
<div class="toast-container-lms" id="toastContainerLms"></div>

<script>
/**
 * Modern Toast Notification System
 * @param {string} type - success, danger, warning, info
 * @param {string} message - The message content
 * @param {string} title - Optional title
 */
function showToast(type, message, title = '') {
  const container = document.getElementById('toastContainerLms');
  if (!container) return;

  const icons = {
    success: 'fa-circle-check',
    danger: 'fa-circle-xmark',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info'
  };

  if (!title) {
    title = type.charAt(0).toUpperCase() + type.slice(1);
    if (type === 'danger') title = 'Error';
  }

  const toast = document.createElement('div');
  toast.className = `toast-lms ${type}`;
  toast.innerHTML = `
    <div class="toast-icon">
      <i class="fas ${icons[type] || 'fa-bell'}"></i>
    </div>
    <div class="toast-body">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
    </div>
    <i class="fas fa-times toast-close" onclick="this.parentElement.remove()"></i>
    <div class="toast-progress"></div>
  `;

  container.appendChild(toast);

  // Trigger animation
  setTimeout(() => toast.classList.add('active'), 10);

  // Auto-remove after 5 seconds
  const timer = setTimeout(() => {
    toast.classList.remove('active');
    setTimeout(() => toast.remove(), 500);
  }, 5000);

  // Manual close
  toast.querySelector('.toast-close').onclick = () => {
    clearTimeout(timer);
    toast.classList.remove('active');
    setTimeout(() => toast.remove(), 500);
  };
}
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script>const BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/notifications.js"></script>

<?php if (isset($extraJS)) echo $extraJS; ?>

<?php require_once __DIR__ . '/modals.php'; ?>

</body>
</html>

