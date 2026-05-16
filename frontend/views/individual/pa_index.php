<?php
// pa_index.php
session_start();

require_once '../../../backend/config/database.php';

if (!isset($_SESSION['Account_ID'])) {
    header("Location: ../../../login.php");
    exit();
}

if ($_SESSION['Account_Type'] !== 'PA') {
    header("Location: ../../../login.php?error=unauthorized");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT u.*, a.Email, a.Account_Type, a.Custom_App_ID
                          FROM USERS u
                          JOIN ACCOUNTS a ON u.User_ID = a.User_ID
                          WHERE a.Account_ID = ? LIMIT 1");
    $stmt->execute([$_SESSION['Account_ID']]);

    $donor = $stmt->fetch();

    if (!$donor) {
        header("Location: ../../../login.php?error=user_not_found");
        exit();
    }

    $firstName = $donor['First_Name'];
    $lastName  = $donor['Last_Name'];
    $email     = $donor['Email'];

    $headerBannerImage = '/foodbank/frontend/assets/images/header-banner.png';

} catch (PDOException $e) {
    die("Error fetching donor credentials: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Bank App</title>

    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/global/_variables.css">
    <link rel="stylesheet" href="../../assets/css/global/_resets.css">
    <link rel="stylesheet" href="../../assets/css/global/_layout.css">
    <link rel="stylesheet" href="../../assets/css/global/_typography.css">

    <!-- Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap">
    
    <!-- Map Library-->
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" />

    <!-- Component CSS -->
    <link rel="stylesheet" href="../../assets/css/components/individual/indi_navigation.css">
    <link rel="stylesheet" href="../../assets/css/components/individual/indi_header.css">
    <link rel="stylesheet" href="../../assets/css/components/support/support_widget.css?v=<?php echo time(); ?>">

    <!-- Page CSS (home page) -->
    <link rel="stylesheet" href="../../assets/css/pages/individual/pa_foodbanks.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/pages/individual/pa_donors.css">
    <link rel="stylesheet" href="../../assets/css/pages/individual/pa_donations.css">
    <link rel="stylesheet" href="../../assets/css/pages/individual/pa_messages.css">
    <link rel="stylesheet" href="../../assets/css/pages/individual/pa_settings.css?v=<?php echo time(); ?>">

    <!-- Icon -->
    <link rel="icon" type="image/png" href="/foodbank/frontend/assets/images/logo.png">

    <style>
        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
            background-color: #f5f3ef;
        }

        .main-content {
            flex: 1;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
            background-color: #f5f3ef;
        }

        #pa-page-content {
            min-width: 0;
        }

        .main-content .stats-row,
        .main-content .section-header,
        .main-content .foodbanks-grid {
            padding-left: 50px;
            padding-right: 50px;
        }
    </style>
</head>
<body>
    <div class="app-container">

        <aside class="sidebar">
            <?php include('../../components/individual/indi_navigation.php'); ?>
        </aside>

        <main class="main-content" id="pa-main-content">
            <!-- Header is always rendered here by index — it never reloads -->
            <?php include('../../components/individual/indi_header.php'); ?>

            <!-- SPA content area — pa-app.js injects page content here -->
            <div id="pa-page-content">
                <?php include('../../views/individual/pa_foodbanks.php'); ?>
            </div>
        </main>

    </div>

    <?php include('../../components/support/support_widget.php'); ?>

    <!-- PA Main JS -->
    <script src="../../assets/js/Individual/pa-app.js"></script>
    <script src="../../assets/js/support-widget.js?v=<?php echo time(); ?>"></script>

    <!-- Map JS-->
    <script src="https://unpkg.com/maplibre-gl/dist/maplibre-gl.js"></script>

</body>
</html>
