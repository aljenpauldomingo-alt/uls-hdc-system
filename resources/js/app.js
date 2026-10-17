import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Alpine = Alpine;

// Follow the device's light/dark setting live, until the user picks a theme with the toggle.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    let saved = null;
    try {
        saved = localStorage.getItem('theme');
    } catch (e) {}

    if (saved !== 'dark' && saved !== 'light') {
        window.applyTheme(event.matches, false);
    }
});

// Fields hidden by conditional logic (x-show) cannot be focused, so a "required" on them would silently
// block the whole form. Lift it just while the browser validates; the server still checks everything.
document.addEventListener('click', (event) => {
    const button = event.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
    const form = button && button.form;

    if (! form) {
        return;
    }

    const lifted = [...form.querySelectorAll(':required')]
        .filter((field) => field.offsetParent === null && field.type !== 'hidden');

    lifted.forEach((field) => { field.required = false; });
    setTimeout(() => lifted.forEach((field) => { field.required = true; }), 0);
}, true);

const realtimeNavigation = document.querySelector('[data-realtime-user-id]');
const realtimeUserId = realtimeNavigation?.dataset.realtimeUserId;
const realtimeKey = import.meta.env.VITE_REVERB_APP_KEY;

if (realtimeUserId && realtimeKey) {
    window.Pusher = Pusher;

    const echo = new Echo({
        broadcaster: 'reverb',
        key: realtimeKey,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    const receivedNotifications = new Set();

    echo.private(`App.Models.User.${realtimeUserId}`).notification((notification) => {
        if (!notification.id || receivedNotifications.has(notification.id)) {
            return;
        }

        receivedNotifications.add(notification.id);

        document.querySelectorAll('[data-notification-bell]').forEach((bell) => {
            const count = bell.querySelector('[data-unread-count]');
            const unreadCount = Number(count.dataset.count) + 1;

            count.dataset.count = String(unreadCount);
            count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            count.classList.remove('hidden');
            bell.setAttribute('aria-label', `${unreadCount} unread notification${unreadCount === 1 ? '' : 's'}`);
        });

        const responsiveCount = document.querySelector('[data-unread-count-text]');

        if (responsiveCount) {
            responsiveCount.textContent = `(${Number(responsiveCount.textContent.replace(/[^\d]/g, '') || 0) + 1})`;
            responsiveCount.classList.remove('hidden');
        }

        const notificationList = document.querySelector('[data-notification-list]');

        if (notificationList && !notificationList.querySelector(`[data-notification-id="${CSS.escape(notification.id)}"]`)) {
            notificationList.querySelector('[data-notification-empty-state]')?.remove();

            const item = document.createElement('a');
            item.href = notificationList.dataset.openUrl.replace('__notification__', encodeURIComponent(notification.id));
            item.dataset.notificationId = notification.id;
            item.className = 'flex items-start gap-3 border-b border-gray-100 bg-indigo-50 px-4 py-4 last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:bg-indigo-500/10 dark:hover:bg-gray-700/60';

            const unreadDot = document.createElement('span');
            unreadDot.className = 'mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-600';
            unreadDot.setAttribute('aria-hidden', 'true');

            const content = document.createElement('span');
            content.className = 'min-w-0 flex-1';

            const message = document.createElement('span');
            message.className = 'block text-sm font-semibold text-gray-900 dark:text-gray-100';
            message.textContent = notification.message || 'New notification';

            const timestamp = document.createElement('span');
            timestamp.className = 'mt-0.5 block text-xs text-gray-500 dark:text-gray-400';
            timestamp.textContent = `Just now · Unread`;

            content.append(message, timestamp);
            item.append(unreadDot, content);
            notificationList.prepend(item);
        }

        document.querySelector('[data-mark-all-read]')?.classList.remove('hidden');

        const toast = document.querySelector('[data-notification-toast]');

        if (toast) {
            toast.querySelector('[data-notification-toast-message]').textContent = notification.message || 'New notification';
            toast.classList.remove('hidden');
            window.setTimeout(() => toast.classList.add('hidden'), 7000);
        }
    });
} else if (realtimeUserId && !realtimeKey) {
    console.error('Realtime notifications are not configured. Set the VITE_REVERB_APP_KEY environment variable.');
}

// Global Real-time Polling Component
Alpine.data('realtimeTable', (url, interval = 5000) => ({
    isPaused: false,
    init() {
        // Pause refreshing if the user is actively interacting with the table or any inputs
        document.addEventListener('focusin', (e) => {
            if (e.target.closest('form') || e.target.closest('[data-realtime-wrapper]')) {
                this.isPaused = true;
            }
        });

        document.addEventListener('focusout', (e) => {
            if (e.target.closest('form') || e.target.closest('[data-realtime-wrapper]')) {
                this.isPaused = false;
            }
        });

        // Also pause if hovering over a dropdown inside the table
        document.addEventListener('mouseover', (e) => {
            if (e.target.closest('select') || e.target.closest('details')) {
                this.isPaused = true;
            }
        });
        document.addEventListener('mouseout', (e) => {
            if (e.target.closest('select') || e.target.closest('details')) {
                this.isPaused = false;
            }
        });

        setInterval(async () => {
            // If the user is typing or has a dropdown open, skip this refresh cycle
            if (this.isPaused) return;

            try {
                const response = await fetch(url, { 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' } 
                });
                if (!response.ok) return;
                
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newContent = doc.querySelector(`[data-realtime-wrapper="${this.$el.dataset.realtimeWrapper}"]`);
                
                if (newContent && newContent.innerHTML !== this.$el.innerHTML) {
                    this.$el.innerHTML = newContent.innerHTML;
                }
            } catch (e) {
                // Silently fail if network hiccups
            }
        }, interval);
    }
}));

Alpine.start();
