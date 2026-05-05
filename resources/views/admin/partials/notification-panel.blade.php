<div id="notificationPanelBackdrop" class="notification-panel-backdrop" hidden></div>

<aside id="notificationPanel" class="notification-panel" aria-labelledby="notificationPanelTitle" aria-hidden="true">
    <div class="notification-panel-header">
        <div>
            <p class="text-uppercase text-muted small fw-semibold mb-1">Activity</p>
            <h2 id="notificationPanelTitle" class="h5 mb-0">Notifications</h2>
        </div>
        <button type="button" id="notificationPanelClose" class="btn btn-sm btn-light rounded-circle" aria-label="Close notifications">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="notification-panel-toolbar">
        <span id="notificationPanelSummary" class="text-muted small">Latest activity</span>
        <button type="button" id="notificationMarkAllRead" class="btn btn-sm btn-outline-success">Mark all as read</button>
    </div>

    <div class="notification-panel-body">
        <section class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="h6 mb-0">New notifications</h3>
                <span id="notificationNewCount" class="badge text-bg-success">0</span>
            </div>
            <div id="notificationNewList" class="notification-list"></div>
        </section>

        <section>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="h6 mb-0">Older notifications</h3>
                <span class="badge text-bg-light">Read</span>
            </div>
            <div id="notificationOlderList" class="notification-list"></div>
        </section>
    </div>
</aside>
