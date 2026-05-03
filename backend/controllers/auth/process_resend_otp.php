<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['pending_account_id'])) {
    echo json_encode(['success' => false]); exit();
}

$account_id = $_SESSION['pending_account_id'];
$method     = $_SESSION['otp_method'] ?? 'email';

// ── PLACEHOLDER: Replace '123456' with real OTP ────────────
$otp    = '123456'; // TODO: random_int(100000, 999999)
$expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

try {
    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET OTP_Code = ?, OTP_Expiry = ? WHERE Account_ID = ?");
    $stmt->execute([$otp, $expiry, $account_id]);

    // TODO: Re-send via email/SMS here (same placeholders as process_send_otp.php)

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
?>