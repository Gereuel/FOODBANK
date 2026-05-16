<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/messages_contacts.php';

if (!isset($_SESSION['Account_ID']) || !in_array($_SESSION['Account_Type'] ?? '', ['PA', 'FA'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$stmt = $pdo->prepare("
    SELECT Account_ID
    FROM ACCOUNTS
    WHERE Account_Type = 'AA'
      AND Status = 'Active'
    ORDER BY Account_ID ASC
    LIMIT 1
");
$stmt->execute();
$adminId = (int) $stmt->fetchColumn();

if ($adminId <= 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No active admin account found']);
    exit();
}

echo json_encode(['success' => true, 'contact' => get_message_contact($pdo, $adminId)]);
