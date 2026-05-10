<?php
$_fb_header_name = htmlspecialchars($foodBankName ?? 'Food Bank');
$_fb_header_banner = htmlspecialchars($headerBannerImage ?? '/foodbank/frontend/assets/images/header-banner.png');
?>

<div class="fb-header-wrapper">
    <div class="fb-header-accent"></div>
    <section class="fb-welcome-header">
        <img src="<?php echo $_fb_header_banner; ?>" alt="" class="banner-img" onerror="this.style.display='none'">
        <div class="fb-welcome-header__content">
            <h2>Welcome back, <?php echo $_fb_header_name; ?>!</h2>
            <p>Manage donations, donors, and conversations</p>
        </div>
    </section>
</div>
