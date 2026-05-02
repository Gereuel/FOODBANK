// ==========================================
// 1. Core Functions
// ==========================================

// Function to dynamically load HTML components
function moveModalsToBody() {
    document.querySelectorAll('.modal').forEach(modal => {
        if (modal.parentElement && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
}

function loadComponent(containerId, filePath, callback = null) {
    fetch(filePath)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(data => {
            document.getElementById(containerId).innerHTML = data;
            moveModalsToBody();
            if (callback) callback();
            // Initialize page-specific scripts after content loads
            initPageScripts();
        })
        .catch(error => console.error(`Error loading ${filePath}:`, error));
}

// In toolbar.js
function initPageScripts() {
    if (typeof initToolbar       === 'function') initToolbar();
    if (typeof initAddModal      === 'function') initAddModal();
    if (typeof initViewModal     === 'function') initViewModal();
    if (typeof initEditModal     === 'function') initEditModal();
    if (typeof initDeleteModal   === 'function') initDeleteModal();
    if (typeof initSecurityModal === 'function') initSecurityModal();
    if (typeof initDonationModals === 'function') initDonationModals();
    if (typeof initFoodBankModals === 'function') initFoodBankModals();
    if (typeof initManagerModals === 'function') initManagerModals();
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
    const defaultAvatar = '/foodbank/frontend/assets/images/default-avatar.png'; 
    
    if (userData.Profile_Picture_URL && userData.Profile_Picture_URL !== "") {
        avatarElement.src = userData.Profile_Picture_URL;
    } else {
        avatarElement.src = defaultAvatar;
    }
}


// ==========================================
// 2. Initialize the Dashboard
// ==========================================

// FIXED: Added '/foodbank/' at the start of the paths
loadComponent('sidebar-container', '/foodbank/frontend/components/admin/admin_sideBar.php');

// Load Topbar, then fetch user data
loadComponent('topbar-container', '/foodbank/frontend/components/admin/admin_topBar.php', () => {
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

// Load the default Dashboard view 
loadComponent('main-display', '/foodbank/frontend/views/admin/dashboard_home.php');


// ==========================================
// 3. Global Event Listeners (Routing & UI)
// ==========================================

document.addEventListener('click', function(e) {

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
