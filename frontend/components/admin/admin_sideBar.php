<!-- Sidebar Header -->
<div class="sidebar-header">
    <img src="/foodbank/frontend/assets/images/logo.png" alt="Logo" class="logo-img">
    <div class="logo-text">
        <h1>Food Bank</h1>
        <span>Admin Panel</span>
    </div>
</div>

<!-- Sidebar Navigation -->
<nav class="sidebar-nav">
    <ul>
        <!-- Standard Active Link -->
        <li class="active">
            <a href="#" data-target="/foodbank/frontend/views/admin/dashboard_home.php">
                <i class="fas fa-home"></i>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>

        <!-- Dropdown Menu Item -->
        <li class="has-dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-users"></i>
                <span class="menu-text">Users</span>
                <i class="fas fa-chevron-down icon-chevron-down"></i>
            </a>
            <!-- The 'submenu' class ensures this is hidden by default via CSS -->
            <ul class="submenu">
                <li><a href="#" class="nav-link dropdown-item" data-target="/foodbank/frontend/views/admin/user_management.php">Overview</a></li>
                <li><a href="#" class="nav-link dropdown-item" data-target="/foodbank/frontend/views/admin/password-security.php">Password & Security</a></li>
                <li><a href="#" class="nav-link dropdown-item" data-target="/foodbank/frontend/views/admin/donations.php">Donations</a></li>
            </ul>
        </li>

        <!-- Dropdown Menu Item -->
        <li class="has-dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-hand-holding-heart"></i>
                <span class="menu-text">Food Bank</span>
                <i class="fas fa-chevron-down icon-chevron-down"></i>
            </a>
            <!-- The 'submenu' class ensures this is hidden by default via CSS -->
            <ul class="submenu">
                <li><a href="#" class="nav-link dropdown-item" data-target="/foodbank/frontend/views/admin/foodbanks.php">Overview</a></li>
                <li><a href="#" class="nav-link dropdown-item" data-target="/foodbank/frontend/views/admin/foodbank-managers.php">Managers Info</a></li>
            </ul>
        </li>
        
        <!-- Standard Link -->
        <li>
            <a href="#">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Reports</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-gear"></i>
                <span class="menu-text">Settings</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Sidebar Footer (Logout) -->
<div class="sidebar-footer">
    <a href="/foodbank/backend/controllers/auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span class="menu-text">Logout</span>
    </a>
</div>
