<?php
session_start();

require_once '../../../backend/config/database.php';
require_once '../../../backend/helpers/schema_columns.php';

if (!isset($_SESSION['Account_ID'])) {
    header('Location: ../../../login.php');
    exit();
}

if (($_SESSION['Account_Type'] ?? '') !== 'FA') {
    header('Location: ../../../login.php?error=unauthorized');
    exit();
}

try {
    $managerAvatarSelect = db_column_exists($pdo, 'FOOD_BANKS', 'Manager_Profile_Picture_URL')
        ? 'fb.Manager_Profile_Picture_URL'
        : 'NULL AS Manager_Profile_Picture_URL';

    $stmt = $pdo->prepare("
        SELECT
            u.*,
            a.Account_ID,
            a.Email,
            a.Phone_Number,
            a.Account_Type,
            a.Custom_App_ID,
            fb.FoodBank_ID,
            fb.Custom_FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Public_Email,
            fb.Public_Phone,
            fb.Time_Open,
            fb.Time_Close,
            fb.Operating_Days,
            fb.Verification_Status,
            fb.Org_Status,
            fb.Manager_First_Name,
            fb.Manager_Last_Name,
            {$managerAvatarSelect},
            fb.Date_Registered
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON u.User_ID = a.User_ID
        LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
        WHERE a.Account_ID = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$manager) {
        session_unset();
        session_destroy();
        header('Location: ../../../login.php?error=user_not_found');
        exit();
    }

    $firstName = $manager['First_Name'] ?: ($manager['Manager_First_Name'] ?? '');
    $lastName = $manager['Last_Name'] ?: ($manager['Manager_Last_Name'] ?? '');
    $email = $manager['Email'] ?? '';
    $foodBankName = $manager['Organization_Name'] ?: 'Food Bank';
    $headerBannerImage = '/foodbank/frontend/assets/images/header-banner.png';
} catch (PDOException $e) {
    die('Error fetching food bank account: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Bank - Dashboard</title>

    <link rel="stylesheet" href="../../assets/css/global/_variables.css">
    <link rel="stylesheet" href="../../assets/css/global/_resets.css">
    <link rel="stylesheet" href="../../assets/css/global/_layout.css">
    <link rel="stylesheet" href="../../assets/css/global/_typography.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap">

    <link rel="stylesheet" href="../../assets/css/components/foodbank/fb_navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/components/foodbank/fb_header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/components/support/support_widget.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/pages/individual/pa_messages.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/pages/foodbank/fb_dashboard.css?v=<?php echo time(); ?>">

    <link rel="icon" type="image/png" href="/foodbank/frontend/assets/images/logo.png">
</head>
<body>
    <div class="fb-app-container">
        <aside class="sidebar fb-sidebar">
            <?php include('../../components/foodbank/fb_navigation.php'); ?>
        </aside>

        <main class="fb-main-content" id="fb-main-content">
            <?php include('../../components/foodbank/fb_header.php'); ?>

            <div id="fb-page-content">
                <?php include('fb_home.php'); ?>
            </div>
        </main>
    </div>

    <?php include('../../components/support/support_widget.php'); ?>

    <script src="../../assets/js/fb-app.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/support-widget.js?v=<?php echo time(); ?>"></script>
</body>
</html>
