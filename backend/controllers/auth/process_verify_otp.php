<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/helpers/auth_redirect.php';

if (!isset($_SESSION['pending_account_id'])) {
    redirect_to_dashboard_or_login();
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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ACCOUNT_LOGIN_ACTIVITY (
            Activity_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            IP_Address VARCHAR(45) DEFAULT NULL,
            User_Agent TEXT DEFAULT NULL,
            Login_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_login_activity_account (Account_ID),
            CONSTRAINT fk_login_activity_account
                FOREIGN KEY (Account_ID) REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        )
    ");

    $stmtActivity = $pdo->prepare("
        INSERT INTO ACCOUNT_LOGIN_ACTIVITY (Account_ID, IP_Address, User_Agent)
        VALUES (?, ?, ?)
    ");
    $stmtActivity->execute([
        $user['Account_ID'],
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    $dashboardPath = auth_dashboard_path($user['Account_Type']);
    header('Location: ' . ($dashboardPath ?? '/foodbank/login.php'));
    exit();

} catch (PDOException $e) {
    error_log("Verify OTP Error: " . $e->getMessage());
    header("Location: ../../../frontend/views/auth/otp.php?error=invalid"); exit();
}
?>
