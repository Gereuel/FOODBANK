<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
}

$account_id = intval($_POST['account_id'] ?? 0);
if (!$account_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid account.']); exit();
}

try {
    $token  = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("
        UPDATE ACCOUNTS 
        SET Reset_Token = ?, Reset_Token_Expiry = ? 
        WHERE Account_ID = ?
    ");
    $stmt->execute([$token, $expiry, $account_id]);

    $reset_link = app_absolute_url("/frontend/views/auth/reset-password.php?token={$token}");

    echo json_encode(['success' => true, 'reset_link' => $reset_link]);

} catch (PDOException $e) {
    error_log("Reset Token Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
