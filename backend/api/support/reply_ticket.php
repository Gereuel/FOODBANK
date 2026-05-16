<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json_response.php';
require_once __DIR__ . '/../../helpers/support_schema.php';
require_once __DIR__ . '/../../helpers/support_notifications.php';

register_json_exception_handler('Reply support ticket');

if (!isset($_SESSION['Account_ID']) || !in_array($_SESSION['Account_Type'] ?? '', ['PA', 'FA', 'AA'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

ensure_support_tables($pdo);

$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
$accountId = (int) $_SESSION['Account_ID'];

if ($ticketId <= 0 || !support_can_access_ticket($pdo, $ticketId, $accountId)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

if ($body === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reply is required']);
    exit();
}

if (strlen($body) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reply is too long']);
    exit();
}

$now = gmdate('Y-m-d H:i:s');
$stmt = $pdo->prepare("
    INSERT INTO SUPPORT_TICKET_REPLIES (Ticket_ID, Sender_Account_ID, Body, Created_At)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$ticketId, $accountId, $body, $now]);

$statusSql = support_is_admin() ? ", Status = IF(Status = 'Open', 'In Progress', Status)" : '';
$update = $pdo->prepare("UPDATE SUPPORT_TICKETS SET Updated_At = ? {$statusSql} WHERE Ticket_ID = ?");
$update->execute([$now, $ticketId]);

if (!support_is_admin()) {
    $ticketStmt = $pdo->prepare("
        SELECT Subject, Assigned_Admin_Account_ID
        FROM SUPPORT_TICKETS
        WHERE Ticket_ID = ?
        LIMIT 1
    ");
    $ticketStmt->execute([$ticketId]);
    $ticket = $ticketStmt->fetch();

    if ($ticket) {
        $senderName = support_account_display_name($pdo, $accountId);
        $assignedAdminId = isset($ticket['Assigned_Admin_Account_ID'])
            ? (int) $ticket['Assigned_Admin_Account_ID']
            : null;

        notify_admin_accounts(
            $pdo,
            'support_reply',
            "{$senderName} replied to support ticket #{$ticketId}: {$ticket['Subject']}",
            '/frontend/views/admin/support.php',
            $assignedAdminId ?: null
        );
    }
}

echo json_encode(['success' => true]);
