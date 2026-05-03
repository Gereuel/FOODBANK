<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        header("Location: ../../../login.php?error=empty_fields");
        exit();
    }

    try {
        // FIX: LEFT JOIN so FA accounts (User_ID = NULL) are included
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.First_Name,
                u.Last_Name,
                fb.Organization_Name
            FROM ACCOUNTS a
            LEFT JOIN USERS u ON a.User_ID = u.User_ID
            LEFT JOIN FOOD_BANKS fb ON a.Account_ID = fb.Account_ID
            WHERE a.Email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $account = $stmt->fetch();

        if ($account && password_verify($password, $account['Password_Hash'])) {

            // Check if account is active
            // Handles both old 'Inactive' records and new 'Disabled' records
            if ($account['Status'] === 'Disabled' || $account['Status'] === 'Inactive') {
                header("Location: ../../../login.php?error=account_disabled");
                exit();
            }

            session_regenerate_id(true);

            // FIX: Don't log in yet — store pending and redirect to 2FA
            $_SESSION['pending_account_id'] = $account['Account_ID'];

            // Store display name for greeting after 2FA
            if ($account['Account_Type'] === 'FA') {
                $_SESSION['pending_display_name'] = $account['Organization_Name'];
            } else {
                $_SESSION['pending_display_name'] = $account['First_Name'] . ' ' . $account['Last_Name'];
            }

            header("Location: ../../../frontend/views/auth/verification.php");
            exit();

        } else {
            header("Location: ../../../login.php?error=invalid_credentials");
            exit();
        }

    } catch (PDOException $e) {
        die("Login System Error: " . $e->getMessage());
    }

} else {
    header("Location: ../../../login.php");
    exit();
}
?>