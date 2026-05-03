<?php
session_start();
require_once '../../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
}

$account_id = intval($_POST['account_id'] ?? 0);
$status     = $_POST['status'] ?? '';

if (!$account_id || !in_array($status, ['Active', 'Inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit();
}

// FIX: Compare Account_IDs directly — $_SESSION['User_ID'] may not be set
if (intval($_SESSION['Account_ID']) === $account_id) {
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