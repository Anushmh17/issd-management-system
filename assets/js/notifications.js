/**
 * ISSD Management - Premium Notification System
 * Handles polling, categorization, and UI alerts
 */
class NotificationManager {
    constructor() {
        this.baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
        this.pollInterval = 5000; // Poll every 5s for even faster response
        this.notifList = document.querySelector('#notif-items-list');
        this.notifBadge = document.querySelector('#notif-badge');
        this.notifCountText = document.querySelector('#notif-count-text');
        this.toastContainer = null;
        this.ensureContainer();
        
        // Initialize from storage to persist across page reloads
        this.lastNotifId = localStorage.getItem('lms_last_notif_id');
        this.dismissedAlerts = JSON.parse(sessionStorage.getItem('lms_dismissed_alerts') || '[]');
        this.init();
    }

    init() {
        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), this.pollInterval);
        
        // Tab switching logic
        document.querySelectorAll('.notif-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent dropdown from closing
                document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.fetchNotifications(tab.dataset.category);
            });
        });

        // Prevent dropdown from closing when clicking inside the list
        if (this.notifList) {
            this.notifList.addEventListener('click', (e) => {
                if (!e.target.closest('a')) {
                    e.stopPropagation();
                }
            });
        }
    }
    
    ensureContainer() {
        this.toastContainer = document.getElementById('notif-toast-container');
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'notif-toast-container';
            // Use existing LMS toast container if it exists for z-index consistency
            const lmsContainer = document.getElementById('toastContainerLms');
            if (lmsContainer) {
                lmsContainer.appendChild(this.toastContainer);
            } else {
                document.body.appendChild(this.toastContainer);
            }
        }
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
                        // Don't show toast for items already marked as read (like dismissed alerts)
                        if (!latest.is_read) {
                            this.showNewNotificationToast(latest);
                        }
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
        
        const colors = {
            'call': '#e11d48',
            'payment': '#16a34a',
            'system': '#7c3aed',
            'enrollment': '#2563eb'
        };
        const iconColor = colors[notif.type] || '#64748b';

        toast.innerHTML = `
            <div class="toast-content" style="display:flex; align-items:center; gap:15px; padding:15px; background:rgba(255,255,255,0.95); backdrop-filter:blur(10px); border-radius:18px; box-shadow:0 20px 40px rgba(0,0,0,0.12); border:1px solid rgba(255,255,255,0.5);">
                <div style="width:48px; height:48px; border-radius:14px; background:${iconColor}15; color:${iconColor}; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:20px; box-shadow: inset 0 0 0 1px ${iconColor}20;">
                    <i class="fas ${notif.icon}"></i>
                </div>
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <span style="font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:${iconColor};">New Notification</span>
                        <span style="font-size:10px; color:#94a3b8;">Just now</span>
                    </div>
                    <div style="font-weight:700; font-size:14px; color:#1e293b; line-height:1.2; margin-bottom:2px;">${notif.title}</div>
                    <div style="font-size:12px; color:#64748b; line-height:1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${notif.body}</div>
                </div>
                <button type="button" class="btn-close" style="font-size:10px; padding:10px; margin:-10px;" onclick="this.closest('.premium-toast').remove()"></button>
            </div>
            <div class="toast-progress" style="position:absolute; bottom:0; left:0; height:3px; background:${iconColor}; width:100%; border-bottom-left-radius:18px; border-bottom-right-radius:18px; transform-origin: left; animation: toast-progress 6s linear forwards;"></div>
        `;

        toast.onclick = (e) => {
            if (!e.target.closest('.btn-close')) {
                window.location.href = notif.link;
            }
        };

        this.toastContainer.appendChild(toast);
        
        // Play subtle sound
        try {
            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
            audio.volume = 0.15;
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
            const type = n.type || 'system';
            const c = colors[type] || { bg: '#f1f5f9', text: '#64748b' };
            const title = n.title || 'Notification';
            const isClosed = title.includes('Closed:');
            const icon = n.icon || 'fa-bell';
            
            return `
            <a href="${n.link || '#'}" class="dropdown-item p-3 border-bottom d-flex gap-3 ${n.is_read ? 'notif-read' : 'notif-unread'}" 
               style="white-space: normal; transition: all 0.2s;" onclick="notificationManager.markRead(${n.id})">
                <div class="notif-icon-box d-flex align-items-center justify-content-center" 
                     style="background:${c.bg}; color:${c.text}; width:42px; height:42px; border-radius:12px; font-size:16px; flex-shrink:0;">
                    <i class="fas ${isClosed ? 'fa-check-circle' : icon}"></i>
                </div>
                <div style="flex:1;">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="fw-700 text-main" style="font-size:13px; line-height:1.2; padding-right:10px;">
                            ${title}
                            ${isClosed ? '<span class="badge bg-secondary ms-1" style="font-size:8px; vertical-align:middle; opacity:0.8;">Closed</span>' : ''}
                        </div>
                        ${n.is_read ? '' : '<span class="notif-dot" style="background:' + (c.text || '#64748b') + '; margin-top:5px; flex-shrink:0;"></span>'}
                    </div>
                    <div class="text-muted" style="font-size:11.5px; line-height:1.4; margin-bottom:6px;">${n.body || ''}</div>
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
            this.notifBadge.innerText = count || 0;
            this.notifBadge.style.display = (count > 0) ? 'block' : 'none';
        }
        if (this.notifCountText) {
            this.notifCountText.innerText = (count || 0) + ' New';
        }
    }

    showUrgentAlerts(alerts) {
        alerts.forEach(alert => {
            const alertKey = `${alert.type}-${alert.id}`;
            const alertId = `call-alert-${alertKey}`;
            
            // Don't show if already dismissed in this session or already visible
            if (this.dismissedAlerts.includes(alertKey)) return;
            if (document.getElementById(alertId)) return;

            const toast = document.createElement('div');
            toast.id = alertId;
            toast.className = 'premium-toast call-alert animate__animated animate__slideInRight';
            
            const isLead = alert.type === 'lead';
            const icon = isLead ? 'fa-phone-volume' : 'fa-headset';
            const targetPage = isLead ? 'leads/index.php' : 'students/index.php';
            const label = isLead ? 'Lead Call' : 'Student Call';

            toast.innerHTML = `
                <div class="toast-header" style="background:${isLead ? '#fff1f2' : '#eef2ff'}; color:${isLead ? '#e11d48' : '#4338ca'}; padding: 10px 15px;">
                    <i class="fas ${icon} me-2" style="font-size:12px;"></i>
                    <strong class="me-auto" style="font-size:11px;">${label}</strong>
                    <button type="button" class="btn-close" style="font-size:8px;" onclick="notificationManager.dismiss('${alert.type}', '${alert.id}', '${alert.name.replace(/'/g, "\\'")}', '${(alert.note || '').replace(/'/g, "\\'")}')"></button>
                </div>
                <div class="toast-body" style="padding: 12px 15px;">
                    <div class="fw-700" style="font-size:13px; margin-bottom:2px;">${alert.name}</div>
                    <div class="text-muted mb-2" style="font-size:11px; line-height:1.2;">${alert.note || 'Scheduled for today'}</div>
                    <div class="d-flex gap-2">
                        <a href="tel:${alert.phone}" class="btn btn-success btn-sm" style="flex:1; font-size:11px; padding: 4px 8px;">
                            <i class="fas fa-phone me-1"></i> Call
                        </a>
                        <a href="${this.baseUrl}/admin/${targetPage}?highlight_id=${alert.id}" class="btn btn-primary btn-sm" style="flex:1; font-size:11px; padding: 4px 8px;" onclick="notificationManager.dismiss('${alert.type}', '${alert.id}', '${alert.name.replace(/'/g, "\\'")}', '${(alert.note || '').replace(/'/g, "\\'")}')">
                            <i class="fas fa-eye me-1"></i> View
                        </a>
                    </div>
                </div>
            `;
            this.toastContainer.appendChild(toast);
        });
    }

    async dismiss(type, id, name, note) {
        const key = `${type}-${id}`;
        const alertId = `call-alert-${key}`;
        const toast = document.getElementById(alertId);
        if (toast) {
            toast.classList.add('animate__fadeOutRight');
            setTimeout(() => toast.remove(), 1000);
        }
        
        if (!this.dismissedAlerts.includes(key)) {
            this.dismissedAlerts.push(key);
            sessionStorage.setItem('lms_dismissed_alerts', JSON.stringify(this.dismissedAlerts));
            
            // Persistent record of this "closed" alert
            const formData = new FormData();
            formData.append('type', 'call');
            formData.append('title', `Closed: ${type === 'lead' ? 'Lead' : 'Student'} Call`);
            formData.append('message', `Finished follow-up for ${name}. Note: ${note || 'None'}`);
            formData.append('link', `${this.baseUrl}/admin/${type === 'lead' ? 'leads' : 'students'}/index.php?highlight_id=${id}`);
            
            try {
                await fetch(`${this.baseUrl}/api/notifications.php?action=dismiss`, {
                    method: 'POST',
                    body: formData
                });
                this.fetchNotifications(); // Refresh list to show the new "closed" entry
            } catch (err) {
                console.error('Failed to save dismissed alert', err);
            }
        }
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

    async clearRead() {
        if (!confirm('Are you sure you want to clear all read notifications?')) return;
        try {
            const resp = await fetch(`${this.baseUrl}/api/notifications.php?action=clear`);
            const data = await resp.json();
            if (data.success) {
                this.fetchNotifications();
            }
        } catch (err) {
            console.error('Failed to clear read notifications', err);
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

// Global instance
window.notificationManager = new NotificationManager();

