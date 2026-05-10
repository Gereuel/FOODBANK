<?php
// Start session and verify user is logged in
session_start();

// Include database configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['Account_ID'])) {
    header("Location: ../../../login.php");
    exit();
}

// Check if user is an admin
if ($_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../login.php?error=unauthorized");
    exit();
}

// Fetch the logged-in admin's credentials
try {
    $stmt = $pdo->prepare("SELECT u.*, a.Email, a.Account_Type, a.Custom_App_ID 
                          FROM USERS u 
                          JOIN ACCOUNTS a ON u.User_ID = a.User_ID 
                          WHERE a.Account_ID = ? LIMIT 1");
    $stmt->execute([$_SESSION['Account_ID']]);
    
    $admin = $stmt->fetch();
    
    // Debug: Log what was fetched
    error_log("DEBUG - Admin Data Fetched: " . json_encode($admin));
    error_log("DEBUG - Session Account_ID: " . $_SESSION['Account_ID']);
    
    if (!$admin) {
        header("Location: ../../../login.php?error=user_not_found");
        exit();
    }
    
    // Store admin data in variables for use in the page
    $adminId = $admin['User_ID'];
    $adminFirstName = $admin['First_Name'];
    $adminLastName = $admin['Last_Name'];
    $adminEmail = $admin['Email'];
    $adminProfilePic = $admin['Profile_Picture_URL'];
    
    // Debug: Verify variables are set
    error_log("DEBUG - Admin Name: " . $adminFirstName . " " . $adminLastName);
    error_log("DEBUG - Admin Email: " . $adminEmail);
    
} catch (PDOException $e) {
    die("Error fetching admin credentials: " . $e->getMessage());
}
?>

<?php
// Pre-fetch donor and foodbank lists for donation modal dropdowns
try {
    $stmt_donors = $pdo->query("
        SELECT a.Account_ID, a.Custom_App_ID, u.First_Name, u.Last_Name
        FROM ACCOUNTS a JOIN USERS u ON a.User_ID = u.User_ID
        WHERE a.Account_Type = 'PA'
        ORDER BY u.First_Name
    ");
    $donors = $stmt_donors->fetchAll();

    $stmt_banks = $pdo->query("
        SELECT fb.FoodBank_ID, fb.Organization_Name
        FROM FOOD_BANKS fb
        WHERE fb.Verification_Status = 'Approved'
        ORDER BY fb.Organization_Name
    ");
    $foodbanks = $stmt_banks->fetchAll();
} catch (PDOException $e) {
    $donors    = [];
    $foodbanks = [];
}
?>

<?php require_once 'modals/donation-report-modal.php'; ?>
<?php require_once 'modals/add-donation-modal.php'; ?>
<?php require_once 'modals/edit-donation-modal.php'; ?>
<?php require_once 'modals/delete-donation-modal.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Bank - Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@700&display=swap"/>
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/global/_variables.css">
    <link rel="stylesheet" href="../../assets/css/global/_resets.css">
    <link rel="stylesheet" href="../../assets/css/global/_layout.css">
    <link rel="stylesheet" href="../../assets/css/global/_typography.css">
    
    <!-- Category CSS -->
    <link rel="stylesheet" href="../../assets/css/pages/admin/adminDashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/pages/admin/user_management.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/pages/admin/admin-animations.css?v=<?php echo time(); ?>">
    
    <link rel="icon" type="image/png" href="/foodbank/frontend/assets/images/logo.png">

</head>
<body>
    <div class="app-container">
        <aside id="sidebar-container" class="sidebar"></aside>

        <main class="main-content">
            <header id="topbar-container" class="topbar"></header>

            <div id="main-display"></div>
        </main>
    </div>

    <!-- Pass admin data to JavaScript -->
    <script>
        const realAdminData = {
            First_Name: "<?php echo htmlspecialchars($adminFirstName); ?>",
            Last_Name: "<?php echo htmlspecialchars($adminLastName); ?>",
            Email: "<?php echo htmlspecialchars($adminEmail); ?>",
            Profile_Picture_URL: "<?php echo htmlspecialchars($adminProfilePic); ?>"
        };
        console.log('Real Admin Data from PHP:', realAdminData);
        console.log('First Name Value:', "<?php echo htmlspecialchars($adminFirstName); ?>");
        console.log('Last Name Value:', "<?php echo htmlspecialchars($adminLastName); ?>");
        console.log('Email Value:', "<?php echo htmlspecialchars($adminEmail); ?>");
    </script>

    <script src="../../assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/donation-modals.js?v=<?php echo time(); ?>"></script>
    
    <!-- Modal Scripts -->
    <script src="../../assets/js/modals/add-user-modal.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/view-user-modal.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/delete-user-modal.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/edit-user-modal.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/toolbar.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/security-user-modal.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/foodbank-modals.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/modals/manager-modals.js?v=<?php echo time(); ?>"></script>
</body>
</html>
