// =====================================================
// ISSD Management - Main JavaScript
// =====================================================

/**
 * GLOBAL HELPERS (Available project-wide)
 */

/**
 * Global Custom Confirmation Modal
 * Returns a Promise that resolves to true (confirm) or false (cancel)
 */
function lmsConfirm(message, options = {}) {
  return new Promise((resolve) => {
    const opts = {
      title: options.title || 'Are you sure?',
      confirmText: options.confirmText || 'Confirm',
      cancelText: options.cancelText || 'Cancel',
      type: options.type || 'confirm', // 'confirm' or 'danger'
      icon: options.icon || (options.type === 'danger' ? 'fa-triangle-exclamation' : 'fa-question-circle')
    };

    // Create Modal HTML
    const overlay = document.createElement('div');
    overlay.className = 'lms-modal-overlay';
    overlay.innerHTML = `
      <div class="lms-modal-box">
        <div class="lms-modal-header">
          <div class="lms-modal-icon ${opts.type === 'danger' ? 'icon-danger' : 'icon-confirm'}">
            <i class="fas ${opts.icon}"></i>
          </div>
          <h3 class="lms-modal-title">${opts.title}</h3>
        </div>
        <div class="lms-modal-body">
          <p class="lms-modal-text">${message}</p>
        </div>
        <div class="lms-modal-footer">
          <button class="lms-modal-btn lms-modal-btn-cancel">${opts.cancelText}</button>
          <button class="lms-modal-btn lms-modal-btn-${opts.type === 'danger' ? 'danger' : 'confirm'}">${opts.confirmText}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    // Trigger Animation
    setTimeout(() => overlay.classList.add('active'), 10);

    // Cleanup function
    const close = (result) => {
      overlay.classList.remove('active');
      setTimeout(() => overlay.remove(), 300);
      resolve(result);
    };

    // Listeners
    overlay.querySelector('.lms-modal-btn-cancel').onclick = (e) => { e.stopPropagation(); close(false); };
    overlay.querySelector('.lms-modal-btn-confirm, .lms-modal-btn-danger').onclick = (e) => { e.stopPropagation(); close(true); };
    overlay.onclick = (e) => { if (e.target === overlay) close(false); };
  });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container') || createToastContainer();
  const toast = document.createElement('div');
  toast.className = `lms-toast lms-toast-${type}`;
  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
  toast.innerHTML = `<div class="toast-lms-icon"><i class="fas ${icons[type] || 'fa-info-circle'}"></i></div> 
                   <div class="toast-lms-body"><span>${message}</span></div>`;
  // Minimal core positioning, styles move to CSS
  toast.style.cssText = `
    display:flex; align-items:center; gap:12px;
    padding:14px 20px; border-radius:12px; margin-bottom:12px;
    box-shadow:0 8px 30px rgba(0,0,0,0.1);
    animation:slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    transition: all 0.4s ease;
  `;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.4s';
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

function createToastContainer() {
  const c = document.createElement('div');
  c.id = 'toast-container';
  c.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;width:300px;';
  document.body.appendChild(c);
  return c;
}

/**
 * Animate number counters
 */
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target   = parseInt(el.dataset.count, 10);
    const duration = 900;
    const step     = target / (duration / 16);
    let current    = 0;
    const timer    = setInterval(() => {
      current += step;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      el.textContent = Math.floor(current).toLocaleString();
    }, 16);
  });
}

/**
 * MAIN INITIALIZATION
 */
document.addEventListener('DOMContentLoaded', function () {

  // --- Sidebar Toggle ---
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar       = document.getElementById('sidebar');
  const overlay       = document.getElementById('sidebarOverlay');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('sidebar-open');
      if (overlay) overlay.classList.toggle('active');
    });
  }
  const sidebarClose = document.getElementById('sidebarClose');
  if (sidebarClose && sidebar) {
    sidebarClose.addEventListener('click', function () {
      sidebar.classList.remove('sidebar-open');
      if (overlay) overlay.classList.remove('active');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('sidebar-open');
      overlay.classList.remove('active');
    });
  }

  // --- Active nav link ---
  const navLinks = document.querySelectorAll('.nav-link');
  const currentPath = window.location.pathname.toLowerCase();
  
  navLinks.forEach(link => {
    try {
      const linkUrl = new URL(link.href);
      const linkPath = linkUrl.pathname.toLowerCase();
      
      if (currentPath === linkPath) {
        link.classList.add('active');
        return;
      }
      if (linkPath.endsWith('index.php')) {
        const linkDir = linkPath.substring(0, linkPath.lastIndexOf('/'));
        if (linkDir && linkDir.length > 5 && currentPath.startsWith(linkDir)) {
          link.classList.add('active');
        }
      }
    } catch(e) {}
  });

  // --- Auto-dismiss alerts ---
  const alerts = document.querySelectorAll('.alert-lms.auto-dismiss');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  /**
   * Universal Confirmation System
   * Intercepts clicks on any element with data-confirm
   */
  document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-confirm]');
    if (!el) return;

    // If already confirmed by our system, let the original action proceed
    if (el.dataset.confirmed === "true") {
      return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();

    const msg = el.dataset.confirm || 'Are you sure?';
    const type = el.dataset.confirmType || 'confirm';
    const href = el.getAttribute('href');
    const form = el.closest('form');

    lmsConfirm(msg, {
      title: 'Confirmation',
      confirmText: 'Yes, Proceed',
      type: type
    }).then(confirmed => {
      if (confirmed) {
        // Mark as confirmed and re-trigger
        el.dataset.confirmed = "true";
        
        if (href && href !== '#' && href !== 'javascript:void(0)') {
          window.location.href = href;
        } else if (form) {
          // If it's a submit button, we should trigger the form submission
          // but specifically ensure hidden inputs for name/value are preserved
          if (el.name && el.value) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = el.name;
            hidden.value = el.value;
            form.appendChild(hidden);
          }
          
          // Re-trigger the click on the element itself so any other listeners run
          el.click(); 
        } else {
          el.click();
        }
      }
    });
  }, true); // Use capture phase to intercept early

  // --- Tooltip init ---
  if (typeof bootstrap !== 'undefined') {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
  }

  // --- Password visibility toggle ---
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
      const input = document.querySelector(this.dataset.target);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        this.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        this.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });

  // --- Stats counters ---
  animateCounters();

  // --- Notice Viewer ---
  const noticeModal = document.getElementById('viewNoticeModal');
  if (noticeModal) {
    document.addEventListener('click', function(e) {
      const card = e.target.closest('.notice-card-clickable, .notice-item-lms, .notice-premium-card');
      if (!card) return;

      // Store notice data in modal
      noticeModal.dataset.noticeId = card.dataset.realId || card.dataset.id;
      noticeModal.dataset.isRead = card.dataset.isRead;
      
      document.getElementById('notice-modal-title').textContent = card.dataset.title;
      document.getElementById('notice-modal-content').textContent = card.dataset.content;
      document.getElementById('notice-modal-author').textContent = card.dataset.author || 'Admin';
      document.getElementById('notice-modal-date').textContent = card.dataset.date;
      
      const avatar = document.getElementById('notice-modal-avatar');
      if(avatar) avatar.textContent = (card.dataset.author || 'Admin').substring(0, 1).toUpperCase();

      const bsModal = new bootstrap.Modal(noticeModal);
      bsModal.show();
    });

    // Modal read logic has been migrated exclusively to markNoticeAsReadGlobal() in modals.php 
    // to prevent session-lock race conditions and double-fetching.
  }

});