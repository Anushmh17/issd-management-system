<?php
// =====================================================
// LEARN Management - Shared Footer
// =====================================================
$year = date('Y');
?>
  <!-- ===== PAGE FOOTER ===== -->
  <footer id="page-footer">
    &copy; <?= $year ?> <strong>LEARN Management</strong>. All rights reserved. &nbsp;|&nbsp;
    Institute Management System &nbsp;&bull;&nbsp; Version 1.0
  </footer>

</div><!-- /#main-content -->
</div><!-- /#app-wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<?php if (isset($extraJS)) echo $extraJS; ?>

<?php require_once __DIR__ . '/modals.php'; ?>

</body>
</html>
