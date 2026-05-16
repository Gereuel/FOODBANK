<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/messages_contacts.php';
require_once __DIR__ . '/../../helpers/support_notifications.php';

if (!isset($_SESSION['Account_ID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentAccountId = (int) $_SESSION['Account_ID'];
$contactId = (int) ($_POST['contact_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($contactId <= 0 || $contactId === $currentAccountId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid contact']);
    exit();
}

if ($body === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit();
}

if (strlen($body) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is too long']);
    exit();
}

$contact = get_message_contact($pdo, $contactId);
if (!$contact) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Contact not found']);
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO MESSAGES (Sender_Account_ID, Receiver_Account_ID, Body, Created_At)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$currentAccountId, $contactId, $body, gmdate('Y-m-d H:i:s')]);

$messageId = (int) $pdo->lastInsertId();
$stmt = $pdo->prepare("
    SELECT Message_ID, Sender_Account_ID, Receiver_Account_ID, Body, Is_Read, Created_At
    FROM MESSAGES
    WHERE Message_ID = ?
");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

$senderType = $_SESSION['Account_Type'] ?? '';
if (in_array($senderType, ['PA', 'FA'], true) && ($contact['account_type'] ?? '') === 'AA') {
    $senderName = support_account_display_name($pdo, $currentAccountId);
    notify_admin_accounts(
        $pdo,
        'support_chat',
        "{$senderName} sent a new support chat message.",
        '/frontend/views/admin/support.php',
        $contactId
    );
}

echo json_encode([
    'success' => true,
    'message' => [
        'message_id' => (int) $message['Message_ID'],
        'body' => $message['Body'],
        'is_mine' => true,
        'created_at' => $message['Created_At'],
        'date_label' => message_date_label($message['Created_At']),
        'time_label' => message_clock_label($message['Created_At']),
        'is_read' => (bool) $message['Is_Read'],
    ],
]);
