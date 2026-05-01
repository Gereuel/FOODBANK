<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    header("Location: ../../../login.php"); exit();
}

$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$account_id       = $_SESSION['pending_account_id'];

if (!$new_password || !$confirm_password) {
    header("Location: ../../../frontend/views/auth/reset-password.php?error=missing_fields"); exit();
}

if (strlen($new_password) < 8) {
    header("Location: ../../../frontend/views/auth/reset-password.php?error=too_short"); exit();
}

if ($new_password !== $confirm_password) {
    header("Location: ../../../frontend/views/auth/reset-password.php?error=mismatch"); exit();
}

try {
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Password_Hash = ? WHERE Account_ID = ?");
    $stmt->execute([$hash, $account_id]);

    // Clear session
    unset($_SESSION['pending_account_id'], $_SESSION['reset_mode'], $_SESSION['reset_verified'], $_SESSION['otp_method']);

    header("Location: ../../../login.php?status=password_reset"); exit();

} catch (PDOException $e) {
    error_log("Reset Password Error: " . $e->getMessage());
    header("Location: ../../../frontend/views/auth/reset-password.php?error=db_error"); exit();
}
?>