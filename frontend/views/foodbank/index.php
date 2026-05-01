<?php
session_start();

// Include database configuration
require_once '../../../backend/config/database.php';

// This will check if the user is logged in
if (!isset($_SESSION['Account_ID'])) {
    header("Location: ../../../login.php");
    exit();
}

// Check if user is a Food Bank Account (Manager)
if ($_SESSION['Account_Type'] !== 'FA') {
    header("Location: ../../../login.php?error=unauthorized");
    exit();
}

// Fetch the logged-in food bank manager's credentials
try {
    $stmt = $pdo->prepare("SELECT u.*, a.Email, a.Account_Type, a.Custom_App_ID 
                          FROM USERS u 
                          JOIN ACCOUNTS a ON u.User_ID = a.User_ID 
                          WHERE a.Account_ID = ? LIMIT 1");
    $stmt->execute([$_SESSION['Account_ID']]);
    
    $manager = $stmt->fetch();
    
    if (!$manager) {
        header("Location: ../../../login.php?error=user_not_found");
        exit();
    }
    
    // Store manager data in variables for use in the page
    $firstName = $manager['First_Name'];
    $lastName = $manager['Last_Name'];
    $email = $manager['Email'];
    
} catch (PDOException $e) {
    die("Error fetching food bank manager credentials: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Bank Manager - Dashboard</title>
    <link rel="icon" href="favicon.ico">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Food Bank Manager Dashboard</div>
        <div class="nav-links">
            <h2>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</h2>
            <p><?php echo htmlspecialchars($email); ?></p>
            <a href="../../../backend/controllers/auth/logout.php" style="color: red; text-decoration: none; margin-left: 15px;">Log Out</a>
        </div>
    </nav>

    <script>
        function preventBack() { window.history.forward(); }
        setTimeout("preventBack()", 0);
        window.onunload = function () { null };
    </script>
</body>
</html>