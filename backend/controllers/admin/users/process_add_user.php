<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../helpers/text_format.php';

// --- CRITICAL SECURITY CHECK ---
// Only allow access if the user is logged in AND is an Admin ('AA')
if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access: Only administrators can perform this action.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Gather Personal Info
    $first_name = format_name_or_address($_POST['first_name']);
    $middle_name = isset($_POST['middle_name']) ? format_name_or_address($_POST['middle_name']) : null;
    $last_name = format_name_or_address($_POST['last_name']);
    $suffix = $_POST['suffix'] ?? null; 
    $address = format_name_or_address($_POST['address']);
    $birthdate = $_POST['birthdate'];
    
    // 2. Gather Account Info
    $account_type = $_POST['account_type']; // Will be 'PA', 'FA', or 'AA'
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    
    // Hash the password securely
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        // STEP 1: Insert into USERS table
        $stmtUser = $pdo->prepare("INSERT INTO USERS (First_Name, Middle_Name, Last_Name, Suffix, Address, Birthdate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUser->execute([$first_name, $middle_name, $last_name, $suffix, $address, $birthdate]);
        
        $new_user_id = $pdo->lastInsertId();
        
        // STEP 2: Generate the Custom ID Safely using the Foolproof method
        $current_year = date("Y");
        $id_prefix = "FB-" . $current_year . "-" . $account_type;
        
        $stmtMax = $pdo->prepare("SELECT Custom_App_ID FROM ACCOUNTS WHERE Custom_App_ID LIKE ? ORDER BY Custom_App_ID DESC LIMIT 1");
        $stmtMax->execute([$id_prefix . '%']);
        $last_id = $stmtMax->fetchColumn();
        
        if ($last_id) {
            $last_sequence = (int) substr($last_id, -4);
            $sequence = $last_sequence + 1;
        } else {
            $sequence = 1;
        }
        
        $formatted_sequence = str_pad($sequence, 4, "0", STR_PAD_LEFT);
        $custom_app_id = $id_prefix . $formatted_sequence;
        
        // STEP 3: Insert into ACCOUNTS table
        $stmtAccount = $pdo->prepare("INSERT INTO ACCOUNTS (User_ID, Account_Type, Custom_App_ID, Email, Phone_Number, Password_Hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtAccount->execute([$new_user_id, $account_type, $custom_app_id, $email, $phone_number, $password_hash]);
        
        $new_account_id = $pdo->lastInsertId();
        
        // Create a notification for the admin
        $stmt_notif = $pdo->prepare("
            INSERT INTO NOTIFICATIONS (Account_ID, Type, Message, Link)
            VALUES (?, ?, ?, ?)
        ");
        $notif_message = "New user '{$first_name} {$last_name}' ({$account_type}) registered.";
        $stmt_notif->execute([$_SESSION['Account_ID'], 'new_user', $notif_message, app_url('/frontend/views/admin/user_management.php')]);

        $pdo->commit();
        
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=users&success=user_added");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Account Creation Failed: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file without submitting a form
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=users&error=access_denied");
    exit();
}
?>
