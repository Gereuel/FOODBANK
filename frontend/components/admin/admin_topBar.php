<div class="topbar-actions">
    <button class="notification-btn" id="notification-toggle">
        <i class="fa-regular fa-bell"></i>
        <span class="notification-badge" id="notification-badge">0</span>
    </button>
    <div class="notification-dropdown" id="notification-dropdown">
        <div class="dropdown-header">
            <h4>Notifications</h4>
            <button class="mark-all-read-btn" id="mark-all-read-btn">Mark all as read</button>
        </div>
        <div class="dropdown-body" id="notification-list">
            <!-- Notifications will be loaded here by JavaScript -->
            <div class="no-notifications" id="no-notifications-message">
                No new notifications.
            </div>
        </div>
        <div class="dropdown-footer">
            <a href="#" class="nav-link" data-target="/foodbank/frontend/views/admin/notifications.php">Show All Notifications</a>
        </div>
    </div>
    <div class="user-profile">
        <div class="user-info">
            <span class="user-name" id="db-user-name">Loading...</span>
            <span class="user-email" id="db-user-email"></span>
        </div>
        <img src="/foodbank/frontend/assets/images/default-avatar.png" alt="Profile" class="user-avatar" id="db-user-avatar">
    </div>
</div>