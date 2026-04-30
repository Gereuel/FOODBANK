<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
}

$account_id = intval($_POST['account_id'] ?? 0);
$status     = $_POST['status'] ?? '';

if (!$account_id || !in_array($status, ['Active', 'Inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit();
}

// Prevent admin from disabling their own account
$stmt_check = $pdo->prepare("SELECT User_ID FROM ACCOUNTS WHERE Account_ID = ?");
$stmt_check->execute([$account_id]);
$target = $stmt_check->fetch();
if ($target && $target['User_ID'] === intval($_SESSION['User_ID'])) {
    echo json_encode(['success' => false, 'message' => 'You cannot disable your own account.']); exit();
}

try {
    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Status = ? WHERE Account_ID = ?");
    $stmt->execute([$status, $account_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Toggle Status Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>