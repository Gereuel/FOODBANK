<?php
$_fb_nav_name = htmlspecialchars($foodBankName ?? 'Food Bank');
$_fb_nav_email = htmlspecialchars($email ?? '');
$_fb_nav_avatar = !empty($manager['Profile_Picture_URL'])
    ? htmlspecialchars($manager['Profile_Picture_URL'])
    : (!empty($manager['Manager_Profile_Picture_URL'])
        ? htmlspecialchars($manager['Manager_Profile_Picture_URL'])
        : '/foodbank/frontend/assets/images/default-avatar.png');
?>

<a href="#" class="nav-link sidebar-brand-link" data-target="/foodbank/frontend/views/foodbank/fb_home.php">
    <div class="sidebar-header">
        <img src="/foodbank/frontend/assets/images/logo.png" alt="Food Bank Logo" class="logo-img">
        <div class="logo-text">
            <h1><span class="logo-food">Food</span> Bank</h1>
            <span class="logo-sub">APP</span>
        </div>
    </div>
</a>

<div class="sidebar-profile">
    <img src="<?php echo $_fb_nav_avatar; ?>" alt="<?php echo $_fb_nav_name; ?>" class="profile-avatar" onerror="this.src='/foodbank/frontend/assets/images/default-avatar.png'">
    <div class="profile-info">
        <span class="profile-name"><?php echo $_fb_nav_name; ?></span>
        <span class="profile-email"><?php echo $_fb_nav_email; ?></span>
    </div>
</div>

<nav class="sidebar-nav">
    <ul class="nav-list">
        <li class="active">
            <a href="#" class="nav-item nav-link" data-target="/foodbank/frontend/views/foodbank/fb_home.php">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text">Home</span>
            </a>
        </li>
        <li>
            <a href="#" class="nav-item nav-link" data-target="/foodbank/frontend/views/foodbank/fb_messages.php">
                <i class="far fa-comment-dots"></i>
                <span class="menu-text">Messages</span>
            </a>
        </li>
        <li>
            <a href="#" class="nav-item nav-link" data-target="/foodbank/frontend/views/foodbank/fb_donors.php">
                <i class="fas fa-user-group"></i>
                <span class="menu-text">Donors</span>
            </a>
        </li>
        <li>
            <a href="#" class="nav-item nav-link" data-target="/foodbank/frontend/views/foodbank/fb_donations.php">
                <i class="fas fa-hand-holding-heart"></i>
                <span class="menu-text">Donations</span>
            </a>
        </li>
        <li>
            <a href="#" class="nav-item nav-link" data-target="/foodbank/frontend/views/foodbank/fb_account.php">
                <i class="far fa-user-circle"></i>
                <span class="menu-text">Account</span>
            </a>
        </li>
    </ul>
</nav>

<div class="sidebar-footer">
    <a href="/foodbank/backend/controllers/auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span class="menu-text">Logout</span>
    </a>
</div>
