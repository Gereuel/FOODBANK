<?php
session_start();
require_once '../config/database.php';

// --- CRITICAL SECURITY CHECK ---
// Only allow access if the user is logged in AND is an Admin ('AA')
if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access: Only administrators can perform this action.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Gather Personal Info
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? null; 
    $last_name = $_POST['last_name'];
    $suffix = $_POST['suffix'] ?? null; 
    $address = $_POST['address'];
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
        
        // Save the changes
        $pdo->commit();
        
        // Redirect back to the admin dashboard with a success message
        header("Location: /foodbank/frontend/views/admin/admin_index.php?status=user_added");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Account Creation Failed: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file without submitting a form
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=access_denied");
    exit();
}
?>