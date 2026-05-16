<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'AA') {
    header("Location: /foodbank/login.php?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=access_denied");
    exit();
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=invalid_request");
    exit();
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=missing_fields");
    exit();
}

if (strlen($newPassword) < 8) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=weak_password");
    exit();
}

if ($newPassword !== $confirmPassword) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=password_mismatch");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT Password_Hash FROM ACCOUNTS WHERE Account_ID = ? AND Account_Type = 'AA' LIMIT 1");
    $stmt->execute([$_SESSION['Account_ID']]);
    $account = $stmt->fetch();

    if (!$account || !password_verify($currentPassword, $account['Password_Hash'])) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=current_password");
        exit();
    }

    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Password_Hash = ? WHERE Account_ID = ?");
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['Account_ID']]);

    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&status=password_changed");
    exit();
} catch (PDOException $e) {
    error_log('Admin password update error: ' . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=db_error");
    exit();
}
