// =====================================================
// LEARN Management - Main JavaScript
// =====================================================

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
      
      // Exact match
      if (currentPath === linkPath) {
        link.classList.add('active');
        return;
      }
      
      // Special case: "Sticky" match for modules using a directory structure (ending in index.php)
      if (linkPath.endsWith('index.php')) {
        const linkDir = linkPath.substring(0, linkPath.lastIndexOf('/'));
        if (linkDir && linkDir.length > 5 && currentPath.startsWith(linkDir)) {
          link.classList.add('active');
        }
      }
    } catch(e) {}
  });

  // --- Auto-dismiss alerts after 5s ---
  const alerts = document.querySelectorAll('.alert-lms.auto-dismiss');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // --- Sidebar Scroll Persistence ---
  const sidebarNav = document.querySelector('.sidebar-nav');
  if (sidebarNav) {
    // Restore scroll position
    const savedPos = sessionStorage.getItem('sidebarScroll');
    if (savedPos) {
      sidebarNav.scrollTop = savedPos;
    }
    // Save scroll position on scroll (debounced)
    let scrollTimeout;
    sidebarNav.addEventListener('scroll', () => {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(() => {
        sessionStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
      }, 100);
    });
    // Ensure it's saved on click too
    document.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        sessionStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
      });
    });
  }

  // --- Confirm delete dialogs ---
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      const msg = this.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // --- Tooltip init (Bootstrap) ---
  if (typeof bootstrap !== 'undefined') {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
  }

  // --- Search filter for tables ---
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', function () {
      const query = this.value.toLowerCase();
      const rows  = document.querySelectorAll('.searchable-table tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  // --- Animate stat cards on load ---
  animateCounters();

  // --- Password toggle visibility ---
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

  // --- AJAX Live Search for Tables ---
  const filterForm = document.querySelector('.students-filters');
  const resultsContainer = document.querySelector('.card-lms-body');
  
  if (filterForm && resultsContainer) {
    let debounceTimer;
    const updateResults = () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
          const url = new URL(window.location.href);
          const formData = new FormData(filterForm);
          for(let [k,v] of formData.entries()) {
              url.searchParams.set(k, v);
          }
          url.searchParams.set('page', 1); // Reset to page 1 on new filter
          url.searchParams.set('ajax', 1);

          // Update URL history without reload
          window.history.pushState({}, '', url.toString().replace('&ajax=1','').replace('?ajax=1',''));

          // Add loading state
          resultsContainer.style.opacity = '0.5';
          resultsContainer.style.pointerEvents = 'none';

          fetch(url)
              .then(r => r.text())
              .then(html => {
                  const parser = new DOMParser();
                  const doc = parser.parseFromString(html, 'text/html');
                  const newBody = doc.querySelector('.card-lms-body');
                  if (newBody) {
                      resultsContainer.innerHTML = newBody.innerHTML;
                  }
                  resultsContainer.style.opacity = '1';
                  resultsContainer.style.pointerEvents = 'all';
              })
              .catch(() => {
                  resultsContainer.style.opacity = '1';
                  resultsContainer.style.pointerEvents = 'all';
              });
      }, 400); // 400ms debounce
    };

    filterForm.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', updateResults);
        el.addEventListener('change', updateResults);
    });
    
    filterForm.addEventListener('submit', e => {
        e.preventDefault();
        updateResults();
    });
  }

  // --- Notice Viewer ---
  document.addEventListener('click', function(e) {
    const card = e.target.closest('.notice-card-clickable');
    if (!card) return;

    const modal = document.getElementById('viewNoticeModal');
    if (!modal) return;

    // Populate Modal
    document.getElementById('notice-modal-title').textContent = card.dataset.title;
    document.getElementById('notice-modal-content').textContent = card.dataset.content;
    document.getElementById('notice-modal-author').textContent = card.dataset.author;
    document.getElementById('notice-modal-date').textContent = card.dataset.date;
    
    // Set Avatar Initial
    document.getElementById('notice-modal-avatar').textContent = card.dataset.author.substring(0, 1).toUpperCase();

    // Show Modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Mark as Read (Client-side UI update + Persistence)
    const readBtn = modal.querySelector('.btn-lms.btn-primary');
    if (readBtn && !readBtn.getAttribute('data-listener')) {
        readBtn.setAttribute('data-listener', 'true');
        readBtn.addEventListener('click', function() {
            const noticeId = card.dataset.realId;
            
            // 1. Perspective: Only reduce count IF it's not already read
            // (We check if it has a 'Read' badge or opacity, but easier to just check realId)
            if (noticeId) {
                fetch(window.location.origin + '/Webbuilders Projects/LEARN Management/backend/notice_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notice_id: noticeId })
                });
            }

            const badge = document.querySelector('#notifDropdown .badge');
            if (badge) {
                let count = parseInt(badge.textContent);
                if (count > 0) {
                    count--;
                    if (count === 0) {
                        badge.remove();
                    } else {
                        badge.textContent = count;
                        const headerBadge = document.querySelector('.dropdown-menu .bg-primary .badge');
                        if (headerBadge) headerBadge.textContent = count + ' New';
                    }
                }
            }
        });
    }
  });

});

// -------------------------------------------------------
// Animate number counters
// -------------------------------------------------------
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

// -------------------------------------------------------
// Show toast notification
// -------------------------------------------------------
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container') || createToastContainer();
  const toast = document.createElement('div');
  toast.className = `lms-toast lms-toast-${type}`;
  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
  toast.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> <span>${message}</span>`;
  toast.style.cssText = `
    display:flex;align-items:center;gap:10px;
    padding:12px 18px;border-radius:10px;margin-bottom:10px;
    font-size:13.5px;font-weight:500;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
    animation:slideInRight 0.3s ease;
    background:${type === 'success' ? '#e0faf4' : type === 'error' ? '#ffe8e8' : type === 'warning' ? '#fff3e3' : '#e6f6ff'};
    color:${type === 'success' ? '#006b58' : type === 'error' ? '#b03030' : type === 'warning' ? '#8a5900' : '#0e6fa5'};
    border-left:4px solid ${type === 'success' ? '#00C9A7' : type === 'error' ? '#FF6B6B' : type === 'warning' ? '#FF9F43' : '#4CC9F0'};
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

// -------------------------------------------------------
// AJAX form helper
// -------------------------------------------------------
function submitForm(formId, successCb) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn  = form.querySelector('[type="submit"]');
    const orig = btn ? btn.innerHTML : '';
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'; btn.disabled = true; }
    const data = new FormData(form);
    fetch(form.action, { method: 'POST', body: data })
      .then(r => r.json())
      .then(res => {
        if (btn) { btn.innerHTML = orig; btn.disabled = false; }
        showToast(res.message, res.success ? 'success' : 'error');
        if (res.success && typeof successCb === 'function') successCb(res);
      })
      .catch(() => {
        if (btn) { btn.innerHTML = orig; btn.disabled = false; }
        showToast('Something went wrong. Please try again.', 'error');
      });
  });
}
