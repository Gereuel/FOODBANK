<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json_response.php';
require_once __DIR__ . '/../../helpers/support_schema.php';

register_json_exception_handler('Update support ticket status');

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'AA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

ensure_support_tables($pdo);

$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$allowed = ['Open', 'In Progress', 'Resolved', 'Closed'];

if ($ticketId <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$stmt = $pdo->prepare("
    UPDATE SUPPORT_TICKETS
    SET Status = ?, Assigned_Admin_Account_ID = ?, Updated_At = ?
    WHERE Ticket_ID = ?
");
$stmt->execute([$status, (int) $_SESSION['Account_ID'], gmdate('Y-m-d H:i:s'), $ticketId]);

echo json_encode(['success' => true]);
