/**
 * LEARN Management - Premium Notification System
 * Handles polling, categorization, and UI alerts
 */
class NotificationManager {
    constructor() {
        this.baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
        this.pollInterval = 5000; // Poll every 5s for even faster response
        this.notifList = document.querySelector('#notif-items-list');
        this.notifBadge = document.querySelector('#notif-badge');
        this.notifCountText = document.querySelector('#notif-count-text');
        
        // Initialize from storage to persist across page reloads
        this.lastNotifId = localStorage.getItem('lms_last_notif_id');
        this.init();
    }

    init() {
        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), this.pollInterval);
        
        // Tab switching logic
        document.querySelectorAll('.notif-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.fetchNotifications(tab.dataset.category);
            });
        });
    }

    async fetchNotifications(category = 'all') {
        try {
            const url = `${this.baseUrl}/api/notifications.php?action=list&category=${category}`;
            const resp = await fetch(url);
            if (!resp.ok) throw new Error(`HTTP error! status: ${resp.status}`);
            const data = await resp.json();
            
            if (data.success) {
                // Check for truly NEW notifications to show pop-up
                if (data.notifications.length > 0) {
                    const latest = data.notifications[0];
                    const latestId = parseInt(latest.id);
                    
                    // If we have a stored ID and the new one is greater, show toast
                    if (this.lastNotifId && latestId > parseInt(this.lastNotifId)) {
                        this.showNewNotificationToast(latest);
                    }
                    
                    // Always update tracker and storage
                    this.lastNotifId = latestId;
                    localStorage.setItem('lms_last_notif_id', latestId);
                }

                this.renderNotifications(data.notifications);
                this.updateBadge(data.unreadCount);
                if (data.urgentCalls && data.urgentCalls.length > 0) {
                    this.showUrgentAlerts(data.urgentCalls);
                }
            }
        } catch (err) {
            console.error('Failed to fetch notifications:', err);
        }
    }

    showNewNotificationToast(notif) {
        const toast = document.createElement('div');
        toast.className = 'premium-toast new-notif animate__animated animate__fadeInUp';
        toast.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; z-index: 9999;
            background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 16px; width: 320px; border-left: 5px solid var(--primary);
            display: flex; gap: 15px; align-items: center; cursor: pointer;
        `;
        
        const colors = {
            'call': '#e11d48',
            'payment': '#16a34a',
            'system': '#7c3aed',
            'enrollment': '#2563eb'
        };
        const iconColor = colors[notif.type] || '#64748b';

        toast.innerHTML = `
            <div style="width:45px; height:45px; border-radius:12px; background:${iconColor}15; color:${iconColor}; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:18px;">
                <i class="fas ${notif.icon}"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; font-size:13px; margin-bottom:2px;">New Alert</div>
                <div style="font-size:12px; color:#444; line-height:1.3;">${notif.title}</div>
            </div>
            <button type="button" class="btn-close" style="font-size:10px;" onclick="this.parentElement.remove()"></button>
        `;

        toast.onclick = () => {
            window.location.href = notif.link;
        };

        document.body.appendChild(toast);
        
        // Play subtle sound if browser allows
        try {
            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
            audio.volume = 0.2;
            audio.play();
        } catch(e) {}

        setTimeout(() => {
            toast.classList.replace('animate__fadeInUp', 'animate__fadeOutDown');
            setTimeout(() => toast.remove(), 1000);
        }, 6000);
    }

    renderNotifications(notifs) {
        if (!this.notifList) return;
        
        if (notifs.length === 0) {
            this.notifList.innerHTML = `
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-bell-slash mb-2 d-block" style="font-size:24px;opacity:0.3;"></i>
                    <div style="font-size:12px;">No notifications in this category</div>
                </div>`;
            return;
        }

        this.notifList.innerHTML = notifs.map(n => {
            const colors = {
                'call': { bg: '#fff1f2', text: '#e11d48' },
                'payment': { bg: '#f0fdf4', text: '#16a34a' },
                'system': { bg: '#f5f3ff', text: '#7c3aed' },
                'enrollment': { bg: '#eff6ff', text: '#2563eb' }
            };
            const c = colors[n.type] || { bg: '#f1f5f9', text: '#64748b' };
            
            return `
            <a href="${n.link}" class="dropdown-item p-3 border-bottom d-flex gap-3 ${n.is_read ? 'notif-read' : 'notif-unread'}" 
               style="white-space: normal; transition: all 0.2s;" onclick="notificationManager.markRead(${n.id})">
                <div class="notif-icon-box d-flex align-items-center justify-content-center" 
                     style="background:${c.bg}; color:${c.text}; width:42px; height:42px; border-radius:12px; font-size:16px; flex-shrink:0;">
                    <i class="fas ${n.icon}"></i>
                </div>
                <div style="flex:1;">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="fw-700 text-main" style="font-size:13px; line-height:1.2; padding-right:10px;">
                            ${n.title}
                        </div>
                        ${n.is_read ? '' : '<span class="notif-dot" style="background:'+c.text+'; margin-top:5px; flex-shrink:0;"></span>'}
                    </div>
                    <div class="text-muted" style="font-size:11.5px; line-height:1.4; margin-bottom:6px;">${n.body}</div>
                    <div style="font-size:10px; color:#94a3b8; display:flex; align-items:center; gap:5px;">
                        <i class="fas fa-clock" style="font-size:11px;"></i> ${this.timeAgo(n.time)}
                    </div>
                </div>
            </a>
            `;
        }).join('');
    }

    updateBadge(count) {
        if (this.notifBadge) {
            this.notifBadge.innerText = count;
            this.notifBadge.style.display = count > 0 ? 'block' : 'none';
        }
        if (this.notifCountText) {
            this.notifCountText.innerText = count + ' New';
        }
    }

    showUrgentAlerts(calls) {
        calls.forEach(call => {
            const alertId = `call-alert-${call.id}`;
            if (document.getElementById(alertId)) return;

            const toast = document.createElement('div');
            toast.id = alertId;
            toast.className = 'premium-toast call-alert animate__animated animate__slideInRight';
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-phone-volume me-2"></i>
                    <strong class="me-auto">Urgent Call Reminder</strong>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
                <div class="toast-body">
                    <div class="fw-700">${call.full_name}</div>
                    <div class="text-muted mb-2">${call.follow_up_note || 'Scheduled call today'}</div>
                    <div class="d-flex gap-2">
                        <a href="tel:${call.phone_number}" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-phone me-1"></i> Call Now
                        </a>
                        <button class="btn btn-outline-secondary btn-sm" onclick="notificationManager.snooze(${call.id})">Snooze</button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Auto hide after 15 seconds if not interacted
            setTimeout(() => {
                if (toast) toast.classList.add('animate__fadeOutRight');
                setTimeout(() => toast?.remove(), 1000);
            }, 15000);
        });
    }

    async markRead(id) {
        const formData = new FormData();
        formData.append('id', id);
        try {
            await fetch(`${this.baseUrl}/api/notifications.php?action=read`, {
                method: 'POST',
                body: formData
            });
            // Refresh the current list to remove the now-read item
            const activeTab = document.querySelector('.notif-tab.active');
            const category = activeTab ? activeTab.dataset.category : 'all';
            this.fetchNotifications(category);
        } catch (err) {
            console.error('Failed to mark notification as read', err);
        }
    }

    snooze(id) {
        const toast = document.getElementById(`call-alert-${id}`);
        if (toast) {
            toast.classList.add('animate__fadeOutRight');
            setTimeout(() => toast.remove(), 500);
        }
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
}

const notificationManager = new NotificationManager();
