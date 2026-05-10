<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
}

$account_id = intval($_POST['account_id'] ?? 0);
$two_fa     = intval($_POST['two_fa'] ?? 0);

if (!$account_id || !in_array($two_fa, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit();
}

try {
    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Two_FA_Enabled = ? WHERE Account_ID = ?");
    $stmt->execute([$two_fa, $account_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Toggle 2FA Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>