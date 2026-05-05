import './bootstrap';
import 'admin-lte/dist/js/adminlte.min.js';

const notificationState = {
    isOpen: false,
    loaded: false,
};

const notificationIcons = {
    lost_item: 'bi-search',
    found_item: 'bi-box-seam',
    incident: 'bi-exclamation-triangle',
    item_match: 'bi-shuffle',
};

const notificationColors = {
    danger: 'danger',
    warning: 'warning',
    success: 'success',
    info: 'info',
};

function notificationElements() {
    return {
        toggle: document.getElementById('notificationPanelToggle'),
        badge: document.getElementById('notificationUnreadBadge'),
        panel: document.getElementById('notificationPanel'),
        backdrop: document.getElementById('notificationPanelBackdrop'),
        close: document.getElementById('notificationPanelClose'),
        summary: document.getElementById('notificationPanelSummary'),
        markAll: document.getElementById('notificationMarkAllRead'),
        newCount: document.getElementById('notificationNewCount'),
        newList: document.getElementById('notificationNewList'),
        olderList: document.getElementById('notificationOlderList'),
    };
}

function routes() {
    return window.notificationRoutes || null;
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value || '';

    return element.innerHTML;
}

function setBadge(count) {
    const { badge } = notificationElements();

    if (! badge) {
        return;
    }

    badge.textContent = count > 99 ? '99+' : String(count);
    badge.classList.toggle('d-none', count < 1);
}

async function notificationFetch(url, options = {}) {
    const config = routes();

    return fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrf,
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
        ...options,
    });
}

async function loadUnreadCount() {
    const config = routes();

    if (! config?.unreadCount) {
        return;
    }

    const response = await notificationFetch(config.unreadCount);

    if (response.ok) {
        const payload = await response.json();
        setBadge(payload.unread_count || 0);
    }
}

function categoryLabel(category) {
    return String(category || 'notification').replaceAll('_', ' ');
}

function renderNotification(notification, unread = false) {
    const icon = notificationIcons[notification.category] || 'bi-bell';
    const color = notificationColors[notification.level] || notificationColors[notification.type] || 'secondary';
    const actionUrl = notification.action_url || routes()?.index || '#';
    const viewButton = actionUrl
        ? `<button type="button" class="btn btn-sm btn-outline-${color} notification-view" data-notification-id="${notification.id}" data-action-url="${escapeHtml(actionUrl)}">View</button>`
        : '';

    return `
        <article class="notification-item ${unread ? 'is-unread' : ''}" data-notification-id="${notification.id}" data-action-url="${escapeHtml(actionUrl)}">
            <div class="notification-avatar">${escapeHtml(notification.creator_initials)}</div>
            <div>
                <div class="notification-meta mb-1">
                    <span class="badge text-bg-${color}">
                        <i class="bi ${icon} me-1"></i>${escapeHtml(categoryLabel(notification.category))}
                    </span>
                    <span class="text-muted small">${escapeHtml(notification.time_ago)}</span>
                </div>
                <h4 class="h6 mb-1">${escapeHtml(notification.title)}</h4>
                <p class="notification-message mb-2">${escapeHtml(notification.message)}</p>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <span class="small text-muted">${escapeHtml(notification.created_by || 'System')}</span>
                    ${viewButton}
                </div>
            </div>
        </article>
    `;
}

function renderNotificationList(element, notifications, unread = false) {
    if (! element) {
        return;
    }

    if (! notifications.length) {
        element.innerHTML = '<div class="notification-empty">No notifications in this section.</div>';

        return;
    }

    element.innerHTML = notifications.map((notification) => renderNotification(notification, unread)).join('');
}

async function loadNotificationFeed() {
    const config = routes();
    const { newList, olderList, newCount, summary } = notificationElements();

    if (! config?.feed || ! newList || ! olderList) {
        return;
    }

    newList.innerHTML = '<div class="notification-empty">Loading notifications...</div>';

    const response = await notificationFetch(config.feed);

    if (! response.ok) {
        newList.innerHTML = '<div class="notification-empty">Notifications could not be loaded.</div>';

        return;
    }

    const payload = await response.json();
    const newNotifications = payload.new || [];
    const olderNotifications = payload.older || [];

    setBadge(payload.unread_count || 0);
    renderNotificationList(newList, newNotifications, true);
    renderNotificationList(olderList, olderNotifications, false);

    if (newCount) {
        newCount.textContent = String(newNotifications.length);
    }

    if (summary) {
        summary.textContent = `${payload.unread_count || 0} unread notifications`;
    }

    notificationState.loaded = true;
}

function openNotificationPanel() {
    const { panel, backdrop } = notificationElements();

    if (! panel || ! backdrop) {
        return;
    }

    notificationState.isOpen = true;
    panel.classList.add('is-open');
    panel.setAttribute('aria-hidden', 'false');
    backdrop.hidden = false;
    loadNotificationFeed();
}

function closeNotificationPanel() {
    const { panel, backdrop } = notificationElements();

    if (! panel || ! backdrop) {
        return;
    }

    notificationState.isOpen = false;
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    backdrop.hidden = true;
}

async function markNotificationRead(id) {
    const config = routes();

    if (! config?.markRead) {
        return;
    }

    await notificationFetch(config.markRead.replace('__ID__', id), { method: 'PATCH' });
}

async function markAllNotificationsRead() {
    const config = routes();

    if (! config?.markAllRead) {
        return;
    }

    const response = await notificationFetch(config.markAllRead, { method: 'PATCH' });

    if (response.ok) {
        await loadNotificationFeed();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const { toggle, close, backdrop, markAll, panel } = notificationElements();

    if (! routes() || ! toggle) {
        return;
    }

    toggle.addEventListener('click', openNotificationPanel);
    close?.addEventListener('click', closeNotificationPanel);
    backdrop?.addEventListener('click', closeNotificationPanel);
    markAll?.addEventListener('click', markAllNotificationsRead);

    panel?.addEventListener('click', async (event) => {
        const button = event.target.closest('.notification-view');
        const item = event.target.closest('.notification-item');
        const target = button || item;

        if (! target) {
            return;
        }

        const notificationId = target.dataset.notificationId;
        const actionUrl = target.dataset.actionUrl || routes().index;

        if (notificationId) {
            await markNotificationRead(notificationId);
        }

        window.location.href = actionUrl;
    });

    loadUnreadCount();
    setInterval(() => {
        if (notificationState.isOpen) {
            loadNotificationFeed();

            return;
        }

        loadUnreadCount();
    }, 20000);
});
