<?php
// indi_navigation.php
// Individual/Donor Sidebar Navigation Component
//
// Variables expected in scope from index.php (pa_index.php):
//   $firstName  — donor's first name
//   $lastName   — donor's last name
//   $email      — donor's email address
//   $donor      — full PDO row (used for Profile_Picture if present)

$_nav_fullName = isset($firstName, $lastName)
    ? htmlspecialchars(trim($firstName . ' ' . $lastName))
    : 'Guest';

$_nav_email = isset($email) ? htmlspecialchars($email) : '';

$_nav_avatar = (!empty($donor['Profile_Picture']))
    ? htmlspecialchars($donor['Profile_Picture'])
    : '/foodbank/frontend/assets/images/default-avatar.png';
?>

<!-- ── Sidebar Header: Logo ─────────────────────────────────── -->
<a href="#" class="nav-link sidebar-brand-link" 
   data-target="/foodbank/frontend/views/individual/pa_home_page.php"
   style="text-decoration: none; display: block;">
    <div class="sidebar-header">
        <img src="/foodbank/frontend/assets/images/logo.png" alt="Food Bank Logo" class="logo-img">
        <div class="logo-text">
            <h1><span class="logo-food">Food</span> Bank</h1>
            <span class="logo-sub">APP</span>
        </div>
    </div>
</a>

<!-- ── User Profile Card ────────────────────────────────────── -->
<div class="sidebar-profile">
    <img
        src="<?php echo $_nav_avatar; ?>"
        alt="<?php echo $_nav_fullName; ?>"
        class="profile-avatar"
        onerror="this.src='/foodbank/frontend/assets/images/default-avatar.png'"
    >
    <div class="profile-info">
        <span class="profile-name"><?php echo $_nav_fullName; ?></span>
        <span class="profile-email"><?php echo $_nav_email; ?></span>
    </div>
</div>

<!-- ── Navigation Links ─────────────────────────────────────── -->
<nav class="sidebar-nav">
    <ul class="nav-list">

        <!-- Food Banks -->
        <li class="active">
            <a href="#" class="nav-item nav-link"
               data-target="/foodbank/frontend/views/individual/pa_home_page.php">
                <i class="fas fa-heart"></i>
                <span class="menu-text">Food Banks</span>
            </a>
        </li>

        <!-- Messages -->
        <li>
            <a href="#" class="nav-item nav-link"
               data-target="/foodbank/frontend/views/individual/pa_messages.php">
                <i class="far fa-comment-dots"></i>
                <span class="menu-text">Messages</span>
            </a>
        </li>

        <!-- Donors -->
        <li>
            <a href="#" class="nav-item nav-link"
               data-target="/foodbank/frontend/views/individual/pa_donors.php">
                <i class="fas fa-user-group"></i>
                <span class="menu-text">Donors</span>
            </a>
        </li>

        <!-- Donation -->
        <li>
            <a href="#" class="nav-item nav-link"
               data-target="/foodbank/frontend/views/individual/pa_donations.php">
                <i class="fas fa-hand-holding-heart"></i>
                <span class="menu-text">Donation</span>
            </a>
        </li>

        <!-- Account -->
        <li>
            <a href="#" class="nav-item nav-link"
               data-target="/foodbank/frontend/views/individual/pa_account.php">
                <i class="far fa-user-circle"></i>
                <span class="menu-text">Account</span>
            </a>
        </li>

    </ul>
</nav>

<!-- ── Sidebar Footer: Logout ───────────────────────────────── -->
<div class="sidebar-footer">
    <a href="/foodbank/backend/controllers/auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span class="menu-text">Logout</span>
    </a>
</div>