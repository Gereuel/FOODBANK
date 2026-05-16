// =====================================================================================================================
// 1. Core Functions
// =====================================================================================================================

function appUrl(path) {
    const base = window.FOODBANK_BASE_URL || (window.location.pathname.startsWith('/foodbank/') ? '/foodbank' : '');
    const cleanPath = String(path || '')
        .replace(/^https?:\/\/[^/]+/i, '')
        .replace(/^\/+foodbank(?=\/|$)/i, '')
        .replace(/^\/+/, '');

    return `${base}/${cleanPath}`;
}

function formatNotificationDate(value) {
    if (!value) return '';

    const normalized = String(value).replace(' ', 'T') + '+08:00';
    const date = new Date(normalized);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
    });
}

// Function to dynamically load HTML components
function moveModalsToBody() {
    document.querySelectorAll('.modal').forEach(modal => {
        if (modal.parentElement && modal.parentElement !== document.body) {
            // Prevent duplication: if a modal with this ID already exists in body, remove it
            const existing = document.querySelector(`body > #${modal.id}`);
            if (existing && existing !== modal) {
                existing.remove();
            }
            document.body.appendChild(modal);
        }
    });
}

function loadComponent(containerId, filePath, callback = null) {
    const componentPath = appUrl(filePath);
    const container = document.getElementById(containerId);

    fetch(componentPath)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(data => {
            if (!container) return;
            container.innerHTML = data;
            if (containerId === 'main-display') {
                container.dataset.currentComponentPath = componentPath;
            }
            moveModalsToBody();
            
            // Manually execute scripts found in the injected HTML
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            if (callback) callback();
            // Initialize page-specific scripts after content loads
            initPageScripts();
        })
        .catch(error => console.error(`Error loading ${componentPath}:`, error));
}

function currentAdminPagePath() {
    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || '';
    const passthrough = new URLSearchParams();

    ['success', 'status', 'error', 'range', 'query'].forEach(key => {
        const value = params.get(key);
        if (value) passthrough.set(key, value);
    });

    const pages = {
        dashboard: '/frontend/views/admin/dashboard_home.php',
        home: '/frontend/views/admin/dashboard_home.php',
        users: '/frontend/views/admin/user_management.php',
        user_management: '/frontend/views/admin/user_management.php',
        password_security: '/frontend/views/admin/password-security.php',
        donations: '/frontend/views/admin/donations.php',
        foodbanks: '/frontend/views/admin/foodbanks.php',
        foodbank_managers: '/frontend/views/admin/foodbank-managers.php',
        reports: '/frontend/views/admin/reports.php',
        support: '/frontend/views/admin/support.php',
        settings: '/frontend/views/admin/settings.php',
        notifications: '/frontend/views/admin/notifications.php',
    };

    const target = pages[page] || pages.dashboard;
    const suffix = passthrough.toString();
    return appUrl(target + (suffix ? `?${suffix}` : ''));
}

// In toolbar.js
function initPageScripts() {
    if (typeof initToolbar       === 'function') initToolbar();
    if (typeof initAddModal      === 'function') initAddModal();
    
    // Priority: Always try to initialize notifications if the elements are present
    // even if it was previously "skipped" during a race condition.
    if (typeof initNotifications === 'function') {
        initNotifications();
    }

    if (typeof initViewModal     === 'function') initViewModal();
    if (typeof initEditModal     === 'function') initEditModal();
    if (typeof initDeleteModal   === 'function') initDeleteModal();
    if (typeof initSecurityModal === 'function') initSecurityModal();
    if (typeof initDonationModals === 'function') initDonationModals();
    if (typeof initFoodBankModals === 'function') initFoodBankModals();
    if (typeof initManagerModals === 'function') initManagerModals();
    if (typeof initReports === 'function' && document.getElementById('rptDonationTrendChart')) {
        initReports();
    }
    if (typeof initDashboard === 'function' && document.getElementById('dashDonationChart')) {
        initDashboard();
    }
    if (typeof initSettings === 'function' && document.getElementById('avatar-input')) {
        initSettings();
    }
    if (typeof initNotificationsPage === 'function' && document.getElementById('mark-all-read-page')) {
        initNotificationsPage();
    }
    initGlobalSearch();
}

// Function to update the Topbar Profile UI
function updateProfileUI(userData) {
    const nameElement = document.getElementById('db-user-name');
    const emailElement = document.getElementById('db-user-email');
    const avatarElement = document.getElementById('db-user-avatar');

    if (!nameElement || !emailElement || !avatarElement) return;

    nameElement.textContent = `${userData.First_Name} ${userData.Last_Name}`;
    emailElement.textContent = userData.Email;

    // FIXED: Use the absolute web path for the fallback image too!
    const defaultAvatar = appUrl('/frontend/assets/images/default-avatar.png');
    
    if (userData.Profile_Picture_URL && userData.Profile_Picture_URL !== "") {
        avatarElement.src = userData.Profile_Picture_URL;
    } else {
        avatarElement.src = defaultAvatar;
    }
}

// =====================================================================================================================
// 2. Initialize the Dashboard
// =====================================================================================================================

loadComponent('sidebar-container', appUrl('/frontend/components/admin/admin_sideBar.php'));

// Load Topbar, then fetch user data
loadComponent('topbar-container', appUrl('/frontend/components/admin/admin_topBar.php'), () => {
    // Use real admin data from PHP or fallback to mock data
    const adminData = typeof realAdminData !== 'undefined' ? realAdminData : {
        First_Name: "Juan", 
        Last_Name: "Dela Cruz",
        Email: "admin@foodbank.org",
        Profile_Picture_URL: null
    };
    console.log('Admin Data Being Used:', adminData); // Debug log
    updateProfileUI(adminData);
});

// Load the requested admin view, or the default dashboard.
loadComponent('main-display', currentAdminPagePath());


// =====================================================================================================================
// 3. Global Event Listeners (Routing & UI)
// =====================================================================================================================

document.addEventListener('click', function(e) {
    const componentPageLink = e.target.closest('#main-display .pagination a.page-btn');
    if (componentPageLink) {
        e.preventDefault();

        const mainDisplay = document.getElementById('main-display');
        const currentComponent = mainDisplay?.dataset.currentComponentPath;
        if (!currentComponent) return;

        const nextUrl = new URL(componentPageLink.getAttribute('href'), window.location.origin + currentComponent);
        loadComponent('main-display', nextUrl.pathname + nextUrl.search);
        return;
    }

    // A. DROPDOWN TOGGLE — matches <a class="nav-link dropdown-toggle">
    const dropdownToggle = e.target.closest('.has-dropdown > .dropdown-toggle');
    if (dropdownToggle) {
        e.preventDefault();
        const parentItem = dropdownToggle.closest('.has-dropdown');

        const isOpen = parentItem.classList.contains('open');

        // Close all open dropdowns first
        document.querySelectorAll('.has-dropdown.open').forEach(openItem => {
            openItem.classList.remove('open');
        });

        // If it wasn't open, open it now
        if (!isOpen) {
            parentItem.classList.add('open');
        }
        return; // prevent fall-through to nav-link handler
    }

    // B. SPA NAVIGATION — only links with data-target
    const navLink = e.target.closest('.nav-link[data-target]');
    if (navLink) {
        e.preventDefault();

        const targetFile = navLink.getAttribute('data-target');
        if (!targetFile) return;

        loadComponent('main-display', targetFile);

        // Clear all active states
        document.querySelectorAll('.sidebar-nav li.active, .sidebar-nav a.active').forEach(item => {
            item.classList.remove('active');
        });

        // Global UI Cleanups on Navigation
        const notificationDropdown = document.getElementById('notification-dropdown');
        if (notificationDropdown) notificationDropdown.classList.remove('show');
        
        document.querySelectorAll('.has-dropdown.open').forEach(openItem => {
            openItem.classList.remove('open');
        });

        // Handle Brand Link -> Dashboard Highlight
        if (targetFile.includes('dashboard_home.php')) {
            const dashLi = document.querySelector('.sidebar-nav > ul > li:first-child');
            if (dashLi) dashLi.classList.add('active');
        }

        // Set active on the closest dropdown-item, or nav-item if top-level
        const parentDropdownItem = navLink.closest('.submenu li');
        if (parentDropdownItem) {
            parentDropdownItem.classList.add('active');
            navLink.classList.add('active');
            // Also keep the parent nav-item open/highlighted
            const parentNavItem = navLink.closest('.has-dropdown');
            if (parentNavItem) {
                parentNavItem.classList.add('active', 'open');
            }
        } else {
            const parentNavItem = navLink.closest('.sidebar-nav > ul > li');
            if (parentNavItem) parentNavItem.classList.add('active');
        }
    }

    // C. PREVENT href="#" scroll-to-top on stub links
    const stubLink = e.target.closest('a[href="#"]');
    if (stubLink) {
        e.preventDefault();
    }
});

// =====================================================================================================================
// 4. Notification System
// =====================================================================================================================

let notificationPollingInterval, hasNotificationListeners = false;

window.initNotifications = function() {
    const notificationToggle = document.getElementById('notification-toggle');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationList = document.getElementById('notification-list');
    const notificationBadge = document.getElementById('notification-badge');
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    const noNotificationsMessage = document.getElementById('no-notifications-message');

    if (!notificationToggle || !notificationDropdown || !notificationList || !notificationBadge) {
        console.warn("Notification elements not found. Skipping notification initialization.");
        return;
    }

    // Prevent duplicate listeners when navigating between pages
    if (hasNotificationListeners) {
        fetchNotifications(); 
        return;
    }

    // Toggle dropdown visibility
    notificationToggle.addEventListener('click', function(e) {
        e.stopPropagation(); // Prevent document click from closing immediately
        notificationDropdown.classList.toggle('show');
        if (notificationDropdown.classList.contains('show')) {
            fetchNotifications(); // Fetch latest when opening
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationDropdown.contains(e.target) && !notificationToggle.contains(e.target)) {
            notificationDropdown.classList.remove('show');
        }
    });

    hasNotificationListeners = true;

    // Mark all as read button
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async function() {
            try {
                const response = await fetch(appUrl('/backend/api/notifications/mark_all_read.php'), { method: 'POST' });
                if (response.ok) {
                    fetchNotifications(); // Refresh list and badge
                } else {
                    console.error('Failed to mark all notifications as read.');
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        });
    }

    // Fetch and render notifications
    async function fetchNotifications() {
        try {
            const response = await fetch(appUrl('/backend/api/notifications/get_notifications.php'));
            if (!response.ok) throw new Error('Failed to fetch notifications');
            const notifications = await response.json();

            notificationList.innerHTML = ''; // Clear existing
            let unreadCount = 0;

            if (notifications.length === 0) {
                if (noNotificationsMessage) {
                    notificationList.appendChild(noNotificationsMessage);
                    noNotificationsMessage.style.display = 'block';
                }
            } else {
                if (noNotificationsMessage) noNotificationsMessage.style.display = 'none';
                notifications.forEach(notif => {
                    if (!notif.Is_Read) unreadCount++;
                    const item = document.createElement('div');
                    item.classList.add('notification-item');
                    if (!notif.Is_Read) item.classList.add('unread');
                    item.dataset.notificationId = notif.Notification_ID;
                    item.innerHTML = `
                        <div class="notification-icon"><i class="fas fa-bell"></i></div>
                        <div class="notification-content">
                            <div class="notification-message">${notif.Message}</div>
                            <div class="notification-time">${formatNotificationDate(notif.Created_At)}</div>
                        </div>
                    `;
                    item.addEventListener('click', async function() {
                        // Mark as read and navigate
                        await fetch(appUrl(`/backend/api/notifications/mark_as_read.php?id=${notif.Notification_ID}`), { method: 'POST' });
                        notificationDropdown.classList.remove('show'); // Close dropdown
                        if (notif.Link) {
                            loadComponent('main-display', notif.Link); // Navigate using SPA loader
                        }
                        fetchNotifications(); // Refresh to update badge and item status
                    });
                    notificationList.appendChild(item);
                });
            }
            notificationBadge.textContent = unreadCount > 0 ? unreadCount : '';
            notificationBadge.style.display = unreadCount > 0 ? 'flex' : 'none';

        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    // Initial fetch and poll every 30 seconds
    fetchNotifications();
    if (notificationPollingInterval) clearInterval(notificationPollingInterval); // Clear existing interval if any
    notificationPollingInterval = setInterval(fetchNotifications, 30000); // Poll every 30 seconds
};

// =====================================================================================================================
// 5. Global Search Functionality
// =====================================================================================================================
function initGlobalSearch() {
    const globalSearchInput = document.getElementById('global-search-input');
    if (globalSearchInput && !globalSearchInput.dataset.listener) {
        globalSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query) {
                    loadComponent('main-display', appUrl(`/frontend/views/admin/search_results.php?query=${encodeURIComponent(query)}`));
                    this.value = ''; 
                }
            }
        });
        globalSearchInput.dataset.listener = 'true';
    }
}

document.addEventListener('DOMContentLoaded', initGlobalSearch);
