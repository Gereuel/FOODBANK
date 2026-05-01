<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['pending_account_id'])) {
    header("Location: ../../../login.php"); exit();
}

$otp_input  = trim($_POST['otp_code'] ?? '');
$account_id = $_SESSION['pending_account_id'];
$is_reset   = isset($_SESSION['reset_mode']) && $_SESSION['reset_mode'] === true;

try {
    $stmt = $pdo->prepare("
        SELECT OTP_Code, OTP_Expiry, Account_Type
        FROM ACCOUNTS
        WHERE Account_ID = ?
    ");
    $stmt->execute([$account_id]);
    $account = $stmt->fetch();

    if (!$account) {
        header("Location: ../../../login.php"); exit();
    }

    // Check expiry
    if (strtotime($account['OTP_Expiry']) < time()) {
        header("Location: ../../../frontend/views/auth/otp.php?error=expired"); exit();
    }

    // Check code
    if ($otp_input !== $account['OTP_Code']) {
        header("Location: ../../../frontend/views/auth/otp.php?error=invalid"); exit();
    }

    // Clear OTP
    $pdo->prepare("UPDATE ACCOUNTS SET OTP_Code = NULL, OTP_Expiry = NULL WHERE Account_ID = ?")->execute([$account_id]);

    // ── Reset mode: go to reset password page ──────────────
    if ($is_reset) {
        $_SESSION['reset_verified'] = true;
        header("Location: ../../../frontend/views/auth/reset-password.php"); exit();
    }

    // ── Login mode: complete login ─────────────────────────
    $stmt2 = $pdo->prepare("
        SELECT a.*, u.First_Name, u.Last_Name
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON a.User_ID = u.User_ID
        WHERE a.Account_ID = ?
    ");
    $stmt2->execute([$account_id]);
    $user = $stmt2->fetch();

    $_SESSION['Account_ID']   = $user['Account_ID'];
    $_SESSION['Account_Type'] = $user['Account_Type'];
    $_SESSION['User_ID']      = $user['User_ID'];
    $_SESSION['Email']        = $user['Email'];
    unset($_SESSION['pending_account_id'], $_SESSION['otp_method']);

    // Redirect by role
    $redirects = [
        'AA' => 'frontend/views/admin/admin_index.php',
        'FA' => 'frontend/views/foodbank/index.php',
        'PA' => 'frontend/views/individual/pa_index.php',
    ];
    header("Location: ../../../" . ($redirects[$user['Account_Type']] ?? 'login.php'));
    exit();

} catch (PDOException $e) {
    error_log("Verify OTP Error: " . $e->getMessage());
    header("Location: ../../../frontend/views/auth/otp.php?error=invalid"); exit();
}
?>