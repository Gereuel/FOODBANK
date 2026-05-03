<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

$email = trim($_POST['email'] ?? '');

if (!$email) {
    header("Location: ../../../frontend/views/auth/forgot-password.php?error=missing_email"); exit();
}

try {
    $stmt = $pdo->prepare("SELECT Account_ID, Email, Phone_Number FROM ACCOUNTS WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $account = $stmt->fetch();

    if (!$account) {
        header("Location: ../../../frontend/views/auth/forgot-password.php?error=not_found"); exit();
    }

    // Store in session for OTP flow
    $_SESSION['pending_account_id'] = $account['Account_ID'];
    $_SESSION['reset_mode']         = true;

    header("Location: ../../../frontend/views/auth/verification.php"); exit();

} catch (PDOException $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    header("Location: ../../../frontend/views/auth/forgot-password.php?error=not_found"); exit();
}
?>