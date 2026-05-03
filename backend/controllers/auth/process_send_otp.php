<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['pending_account_id'])) {
    header("Location: ../../../login.php"); exit();
}

$method     = $_POST['method'] ?? 'email';
$account_id = $_SESSION['pending_account_id'];

// ── PLACEHOLDER: Replace '123456' with real OTP generation ──
// When integrating email: send via PHPMailer/SMTP
// When integrating SMS: send via Semaphore/Twilio API
$otp    = '123456'; // TODO: replace with random_int(100000, 999999)
$expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

try {
    $stmt = $pdo->prepare("
        UPDATE ACCOUNTS
        SET OTP_Code = ?, OTP_Expiry = ?, OTP_Method = ?
        WHERE Account_ID = ?
    ");
    $stmt->execute([$otp, $expiry, $method, $account_id]);

    $_SESSION['otp_method'] = $method;

    // ── PLACEHOLDER: Email sending ─────────────────────────
    if ($method === 'email') {
        // TODO: Replace this block with PHPMailer
        // Example:
        // $mail = new PHPMailer();
        // $mail->setFrom('noreply@foodbank.com');
        // $mail->addAddress($user_email);
        // $mail->Subject = 'Your Food Bank Verification Code';
        // $mail->Body = "Your code is: {$otp}. Expires in 5 minutes.";
        // $mail->send();
    }

    // ── PLACEHOLDER: SMS sending ───────────────────────────
    if ($method === 'sms') {
        // TODO: Replace this block with Semaphore/Twilio
        // Example (Semaphore):
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, [
        //     'apikey'  => 'YOUR_API_KEY',
        //     'number'  => $user_phone,
        //     'message' => "Your Food Bank verification code is: {$otp}",
        // ]);
        // curl_exec($ch);
    }

    header("Location: ../../../frontend/views/auth/otp.php"); exit();

} catch (PDOException $e) {
    error_log("OTP Error: " . $e->getMessage());
    header("Location: ../../../frontend/views/auth/verification.php?error=send_failed"); exit();
}
?>