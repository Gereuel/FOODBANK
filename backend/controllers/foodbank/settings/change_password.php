<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
    exit();
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit();
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT Password_Hash FROM ACCOUNTS WHERE Account_ID = ? LIMIT 1");
    $stmt->execute([$_SESSION['Account_ID']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account || !password_verify($currentPassword, $account['Password_Hash'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Password_Hash = ? WHERE Account_ID = ?");
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['Account_ID']]);

    echo json_encode(['success' => true, 'message' => 'Password updated.']);
} catch (PDOException $e) {
    error_log('Foodbank password update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update password.']);
}
