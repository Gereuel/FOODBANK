<?php
// indi_header.php
// Individual/Donor Welcome Header Banner Component
//
// Variables expected in scope from index.php:
//   $firstName         — donor's first name
//   $headerBannerImage — (optional) path to banner photo, e.g.:
//                        $headerBannerImage = '/foodbank/frontend/assets/images/banner.jpg';

$_header_firstName = isset($firstName) ? htmlspecialchars($firstName) : 'there';
$_header_bannerImg = isset($headerBannerImage)
    ? htmlspecialchars($headerBannerImage)
    : '/foodbank/frontend/assets/images/header-banner.png';
?>

<!-- ── Header Wrapper: green bar + card live inside here ─────── -->
<div class="header-wrapper">

    <!-- Green accent strip — peeks above the card -->
    <div class="header-accent-bar"></div>

    <!-- Welcome card — floats over the green strip -->
    <div class="welcome-header">

    <!-- Full-bleed background image -->
    <img
        src="<?php echo $_header_bannerImg; ?>"
        alt=""
        class="banner-img"
        onerror="this.style.display='none'"
    >

    <!-- Text overlay -->
    <div class="welcome-header__content">
        <h2 class="welcome-title">Welcome back, <?php echo $_header_firstName; ?>!</h2>
        <p class="welcome-subtitle">Let's make a difference today</p>
    </div>

    </div>

</div><!-- /.header-wrapper -->