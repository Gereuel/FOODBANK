<?php
session_start();

require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check the entered email and password
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validation to ensure fields are not empty
    if (empty($email) || empty($password)) {
        header("Location: ../../login.php?error=empty_fields");
        exit();
    }

    try {
        // STEP 1: Find the account by email
        $stmt = $pdo->prepare("SELECT ACCOUNTS.*, USERS.First_Name, USERS.Last_Name 
                            FROM ACCOUNTS 
                            JOIN USERS ON ACCOUNTS.User_ID = USERS.User_ID 
                            WHERE ACCOUNTS.Email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        $account = $stmt->fetch();
        
        // STEP 2: Verify the account and password
        if ($account && password_verify($password, $account['Password_Hash'])) {
            
            session_regenerate_id(true);
            
            // STEP 3: Setup the Session Variables
            $_SESSION['Account_ID'] = $account['Account_ID'];
            $_SESSION['User_ID'] = $account['User_ID'];
            $_SESSION['Account_Type'] = $account['Account_Type'];
            $_SESSION['Custom_App_ID'] = $account['Custom_App_ID'];
            $_SESSION['First_Name'] = $account['First_Name'];
            $_SESSION['Last_Name'] = $account['Last_Name'];
            $_SESSION['Email'] = $account['Email'];
            
            // STEP 4: Redirect based on Account Type
            switch ($account['Account_Type']) {
                case 'AA': // Admin Account
                    header("Location: ../../frontend/views/admin/admin_index.php"); 
                    break;
                case 'PA': // Personal Account / Donor
                    header("Location: ../../frontend/views/individual/pa_index.php");
                    break;
                case 'FA': // Food Bank Account
                    // Adjust this file name to whatever your main foodbank page is named
                    header("Location: ../../frontend/views/foodBank/fb_index.php"); 
                    break;
                default:
                    // Fallback in case an unknown account type somehow logs in
                    header("Location: ../../login.php?error=unknown_account_type");
                    break;
            }
            exit(); // Don't forget this! It stops the script after redirecting.
            
        } else {
            // Login failed
            header("Location: ../../login.php?error=invalid_credentials");
            exit();
        }

    } catch (PDOException $e) {
        // Database failure handler
        die("Login System Error: " . $e->getMessage());
    }

} else {
    header("Location: ../../login.php");
    exit();
}
?>