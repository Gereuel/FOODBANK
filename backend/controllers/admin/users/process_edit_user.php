<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Security check
if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized");
    exit();
}

// Validate required fields
$required = ['user_id', 'account_id', 'account_type', 'email', 'phone_number', 'first_name', 'last_name', 'address', 'birthdate'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields");
        exit();
    }
}

// Sanitize inputs
$user_id      = intval($_POST['user_id']);
$account_id   = intval($_POST['account_id']);
$account_type = trim($_POST['account_type']);
$email        = trim($_POST['email']);
$phone        = trim($_POST['phone_number']);
$first_name   = trim($_POST['first_name']);
$middle_name  = trim($_POST['middle_name'] ?? '');
$last_name    = trim($_POST['last_name']);
$suffix       = trim($_POST['suffix'] ?? '');
$address      = trim($_POST['address']);
$birthdate    = trim($_POST['birthdate']);

// Validate account type
$allowed_types = ['PA', 'FA', 'AA'];
if (!in_array($account_type, $allowed_types)) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=invalid_account_type");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=invalid_email");
    exit();
}

// Validate birthdate format
if (!strtotime($birthdate)) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=invalid_birthdate");
    exit();
}

try {
    // Check if email is already taken by another account
    $stmt_check = $pdo->prepare("
        SELECT Account_ID FROM ACCOUNTS 
        WHERE Email = ? AND Account_ID != ?
    ");
    $stmt_check->execute([$email, $account_id]);
    if ($stmt_check->fetch()) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=email_taken");
        exit();
    }

    // Begin transaction — update both tables atomically
    $pdo->beginTransaction();

    // Update ACCOUNTS table
    $stmt_account = $pdo->prepare("
        UPDATE ACCOUNTS 
        SET Account_Type = ?,
            Email        = ?,
            Phone_Number = ?
        WHERE Account_ID = ? AND User_ID = ?
    ");
    $stmt_account->execute([
        $account_type,
        $email,
        $phone,
        $account_id,
        $user_id
    ]);

    // Update USERS table
    $stmt_user = $pdo->prepare("
        UPDATE USERS
        SET First_Name  = ?,
            Middle_Name = ?,
            Last_Name   = ?,
            Suffix      = ?,
            Address     = ?,
            Birthdate   = ?
        WHERE User_ID = ?
    ");
    $stmt_user->execute([
        $first_name,
        $middle_name ?: null,
        $last_name,
        $suffix ?: null,
        $address,
        $birthdate,
        $user_id
    ]);

    $pdo->commit();

    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=user_updated");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Edit User Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error");
    exit();
}
?>