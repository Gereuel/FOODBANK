<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/text_format.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $first_name = format_name_or_address($_POST['first_name']);
    // FIX 1: Use the Null Coalescing Operator (??) to safely handle missing form data
    $middle_name = isset($_POST['middle_name']) ? format_name_or_address($_POST['middle_name']) : null;
    $last_name = format_name_or_address($_POST['last_name']);
    $suffix = $_POST['suffix'] ?? null; 
    $address = format_name_or_address($_POST['address']);
    $birthdate = $_POST['birthdate'];
    
    $account_type = $_POST['account_type']; // 'PA' or 'FA' or 'AA'
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    if (!in_array($account_type, ['PA', 'FA', 'AA'], true)) {
        header("Location: ../../../signup.php?error=invalid_account_type");
        exit();
    }

    try {
        $birthdateValue = new DateTime($birthdate);
        $minimumBirthdate = (new DateTime('today'))->modify('-18 years');

        if ($birthdateValue > $minimumBirthdate) {
            header("Location: ../../../signup.php?error=underage");
            exit();
        }
    } catch (Exception $e) {
        header("Location: ../../../signup.php?error=invalid_birthdate");
        exit();
    }
    
    // Hash the password securely
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        // STEP 1: Insert into USERS table
        $stmtUser = $pdo->prepare("INSERT INTO USERS (First_Name, Middle_Name, Last_Name, Suffix, Address, Birthdate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUser->execute([$first_name, $middle_name, $last_name, $suffix, $address, $birthdate]);
        
        $new_user_id = $pdo->lastInsertId();
        
        // STEP 2: Generate the Custom ID Safely (Sample: FB-2026-PA0001)
        $current_year = date("Y");
        
        // FIX 2: Grab the absolute latest ID created for this specific year and type, rather than counting rows
        $stmtMax = $pdo->prepare("
            SELECT Custom_App_ID 
            FROM ACCOUNTS 
            WHERE Account_Type = ? AND YEAR(Date_Created) = ? 
            ORDER BY Account_ID DESC LIMIT 1
        ");
        $stmtMax->execute([$account_type, $current_year]);
        $last_id = $stmtMax->fetchColumn();
        
        if ($last_id) {
            // If an ID exists, extract the last 4 characters, convert to integer, and add 1
            $last_sequence = (int) substr($last_id, -4);
            $sequence = $last_sequence + 1;
        } else {
            // If this is the very first account of the year for this type, start at 1
            $sequence = 1;
        }
        
        // Format the ID to always have 4 digits (e.g., 0001, 0002)
        $formatted_sequence = str_pad($sequence, 4, "0", STR_PAD_LEFT);
        $custom_app_id = "FB-" . $current_year . "-" . $account_type . $formatted_sequence;
        
        // STEP 3: Insert into ACCOUNTS table
        $stmtAccount = $pdo->prepare("INSERT INTO ACCOUNTS (User_ID, Account_Type, Custom_App_ID, Email, Phone_Number, Password_Hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtAccount->execute([$new_user_id, $account_type, $custom_app_id, $email, $phone_number, $password_hash]);

        // Notify every admin that a public signup created a new account.
        $stmtAdmins = $pdo->query("SELECT Account_ID FROM ACCOUNTS WHERE Account_Type = 'AA'");
        $adminAccountIds = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($adminAccountIds)) {
            $stmtNotif = $pdo->prepare("
                INSERT INTO NOTIFICATIONS (Account_ID, Type, Message, Link)
                VALUES (?, ?, ?, ?)
            ");
            $notifMessage = "New user '{$first_name} {$last_name}' ({$account_type}) created an account.";
            $notifLink = app_url('/frontend/views/admin/user_management.php');

            foreach ($adminAccountIds as $adminAccountId) {
                $stmtNotif->execute([$adminAccountId, 'new_user_signup', $notifMessage, $notifLink]);
            }
        }
        
        // Save the changes
        $pdo->commit();
        
        header("Location: ../../../login.php?status=success");
        exit();

    } catch (PDOException $e) {
        // Rollback any partial database inserts if something fails
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $isDuplicateEmail = ($e->errorInfo[1] ?? null) === 1062
            && stripos($e->getMessage(), 'Email') !== false;

        if ($isDuplicateEmail) {
            header("Location: ../../../signup.php?error=email_exists");
            exit();
        }

        error_log("Registration failed: " . $e->getMessage());
        header("Location: ../../../signup.php?error=registration_failed");
        exit();
    }
} else {
    header("Location: ../../../signup.php");
    exit();
}
?>
