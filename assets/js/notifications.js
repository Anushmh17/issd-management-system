/**
 * ISSD Management - Premium Notification System (v2.0 Enhanced)
 * Handles polling, categorization, and High-End UI alerts
 */
class NotificationManager {
    constructor() {
        this.baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
        this.pollInterval = 5000;
        this.notifList = document.querySelector('#notif-items-list');
        this.notifBadge = document.querySelector('#notif-badge');
        this.notifCountText = document.querySelector('#notif-count-text');
        this.headerSpinner = document.querySelector('#notif-header-spinner');
        this.toastContainer = null;
        this.ensureContainer();
        this.currentUrgentAlerts = [];
        this.lastNotifId = localStorage.getItem('lms_last_notif_id');
        this.dismissedAlerts = JSON.parse(localStorage.getItem('lms_alert_dismissals') || '{}');
        this.init();
    }

    init() {
        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), this.pollInterval);
        
        document.querySelectorAll('.notif-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); 
                document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Enhanced Skeleton Loading State
                if (this.notifList) {
                    this.notifList.innerHTML = this.renderSkeleton();
                }
                
                if (this.headerSpinner) this.headerSpinner.style.display = 'inline-block';
                this.fetchNotifications(tab.dataset.category);
            });
        });

        const dropdownMenu = document.querySelector('.notif-dropdown');
        if (dropdownMenu) {
            dropdownMenu.addEventListener('click', (e) => {
                const isLink = e.target.closest('a') && e.target.closest('a').getAttribute('href') !== 'javascript:void(0)';
                if (!isLink) e.stopPropagation();
            });
        }
    }
    
    renderSkeleton() {
        let html = '';
        for(let i=0; i<4; i++) {
            html += `
            <div class="p-3 border-bottom d-flex gap-3" style="opacity:0.5;">
                <div class="skeleton-loader" style="width:42px; height:42px; border-radius:12px; flex-shrink:0;"></div>
                <div style="flex:1;">
                    <div class="skeleton-loader" style="width:40%; height:8px; margin-bottom:10px;"></div>
                    <div class="skeleton-loader" style="width:80%; height:12px; margin-bottom:6px;"></div>
                    <div class="skeleton-loader" style="width:60%; height:10px;"></div>
                </div>
            </div>`;
        }
        return html;
    }

    ensureContainer() {
        this.toastContainer = document.getElementById('notif-toast-container');
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'notif-toast-container';
            const lmsContainer = document.getElementById('toastContainerLms');
            if (lmsContainer) lmsContainer.appendChild(this.toastContainer);
            else document.body.appendChild(this.toastContainer);
        }
    }

    async fetchNotifications(category = null) {
        if (!category) {
            const activeTab = document.querySelector('.notif-tab.active');
            category = activeTab ? activeTab.dataset.category : 'all';
        }

        try {
            const url = `${this.baseUrl}/api/notifications.php?action=list&category=${category}&t=${Date.now()}`;
            const resp = await fetch(url);
            const data = await resp.json();
            
            if (data.success) {
                if (data.notifications.length > 0) {
                    const latest = data.notifications[0];
                    const latestId = parseInt(latest.id);
                    if (this.lastNotifId && latestId > parseInt(this.lastNotifId)) {
                        if (!latest.is_read) this.showNewNotificationToast(latest);
                    }
                    this.lastNotifId = latestId;
                    localStorage.setItem('lms_last_notif_id', latestId);
                }

                this.renderNotifications(data.notifications, category);
                this.updateBadge(data.unreadCount);
                if (this.headerSpinner) this.headerSpinner.style.display = 'none';
            }
        } catch (err) {
            console.error('Failed to fetch:', err);
            if (this.headerSpinner) this.headerSpinner.style.display = 'none';
        }
    }

    renderNotifications(notifs, category = 'all') {
        if (!this.notifList) return;
        if (notifs.length === 0) {
            this.notifList.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center" style="height:100%; opacity:0.6;">
                    <div style="width:80px; height:80px; background:#f1f5f9; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:32px; margin-bottom:20px; border:4px solid #fff; box-shadow:0 10px 20px rgba(0,0,0,0.05);">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h6 class="fw-800 text-main m-0">No alerts yet</h6>
                    <p class="text-muted" style="font-size:12px;">We will notify you of any updates.</p>
                </div>`;
            return;
        }

        let html = '';
        html += notifs.map((n, index) => {
            const colors = {
                'call': { bg: '#fff1f2', text: '#e11d48', label: 'Call Follow-up' },
                'payment': { bg: '#f0fdf4', text: '#16a34a', label: 'Payment' },
                'system': { bg: '#f5f3ff', text: '#7c3aed', label: 'System' },
                'enrollment': { bg: '#eff6ff', text: '#2563eb', label: 'Enrollment' }
            };
            const c = colors[n.type] || { bg: '#f1f5f9', text: '#64748b', label: 'Other' };
            const isClosed = n.title.includes('Closed:');
            
            return `
            <a href="${n.link || '#'}" class="dropdown-item notif-item-enhanced ${n.is_read ? 'notif-read' : 'notif-unread'}" 
               style="white-space: normal; animation-delay: ${index * 0.05}s;" onclick="notificationManager.markRead(${n.id})">
                <div class="d-flex gap-3">
                    <div class="notif-icon-box d-flex align-items-center justify-content-center" 
                         style="background:${c.bg}; color:${c.text}; width:48px; height:48px; border-radius:14px; font-size:18px; flex-shrink:0; box-shadow: 0 4px 10px ${c.text}15;">
                        <i class="fas ${isClosed ? 'fa-check-circle' : (n.icon || 'fa-bell')}"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:9px; font-weight:800; text-transform:uppercase; color:${c.text}; background:${c.bg}; padding:3px 8px; border-radius:6px; letter-spacing:0.5px;">${c.label}</span>
                            <span style="font-size:10px; color:#94a3b8; font-weight:600;">${this.timeAgo(n.time)}</span>
                        </div>
                        <div class="fw-800 text-main" style="font-size:14px; line-height:1.2; margin-bottom:4px; ${n.is_read ? 'opacity:0.8;' : ''}">
                            ${n.title}
                        </div>
                        <div class="text-muted" style="font-size:12px; line-height:1.5;">${n.body || ''}</div>
                    </div>
                </div>
            </a>`;
        }).join('');

        this.notifList.innerHTML = html;
    }

    updateBadge(count) {
        if (this.notifBadge) {
            this.notifBadge.innerText = count || 0;
            this.notifBadge.style.display = (count > 0) ? 'block' : 'none';
        }
        if (this.notifCountText) this.notifCountText.innerText = (count || 0) + ' New';
    }

    async markRead(id) {
        const formData = new FormData();
        formData.append('id', id);
        try {
            await fetch(`${this.baseUrl}/api/notifications.php?action=read`, { method: 'POST', body: formData });
            this.fetchNotifications();
        } catch (err) { console.error('Failed', err); }
    }

    async markAllRead() {
        try {
            await fetch(`${this.baseUrl}/api/notifications.php?action=read_all`);
            this.fetchNotifications();
        } catch (err) { console.error('Failed', err); }
    }

    async clearRead() {
        this.confirmAction({
            title: 'Clear History?',
            message: 'All read items will be archived. You can still see them in Full History.',
            icon: 'fa-trash-can',
            btnText: 'Clear Now',
            onConfirm: async () => {
                const resp = await fetch(`${this.baseUrl}/api/notifications.php?action=clear`);
                const data = await resp.json();
                if (data.success) this.fetchNotifications();
            }
        });
    }

    confirmAction({ title, message, icon, btnText, onConfirm }) {
        const modalEl = document.getElementById('confirmModal');
        if (!modalEl) { if (confirm(message)) onConfirm(); return; }
        document.getElementById('confirm-modal-title').innerText = title || 'Are you sure?';
        document.getElementById('confirm-modal-message').innerText = message || '';
        document.getElementById('confirm-modal-btn').innerText = btnText || 'Confirm';
        document.getElementById('confirm-modal-icon').innerHTML = `<i class="fas ${icon || 'fa-question-circle'}"></i>`;
        const btn = document.getElementById('confirm-modal-btn');
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        newBtn.onclick = () => { onConfirm(); bsModal.hide(); };
        bsModal.show();
    }

    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        return Math.floor(hours / 24) + 'd ago';
    }

    showNewNotificationToast(notif) {
        const toast = document.createElement('div');
        toast.className = 'premium-toast new-notif animate__animated animate__fadeInUp';
        toast.innerHTML = `
            <div style="display:flex; align-items:center; gap:15px; padding:18px; background:rgba(255,255,255,0.95); backdrop-filter:blur(10px); border-radius:22px; box-shadow:0 30px 60px rgba(0,0,0,0.15); border:1px solid #fff;">
                <div style="width:50px; height:50px; border-radius:15px; background:var(--primary)15; color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:22px;">
                    <i class="fas ${notif.icon}"></i>
                </div>
                <div>
                    <div style="font-weight:800; font-size:15px; color:#1e293b;">${notif.title}</div>
                    <div style="font-size:12px; color:#64748b;">${notif.body}</div>
                </div>
            </div>`;
        this.toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
}
window.notificationManager = new NotificationManager();
