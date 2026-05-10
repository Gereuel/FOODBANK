<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/helpers/messages_schema.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/helpers/messages_contacts.php';

if (!isset($_SESSION['Account_ID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

ensure_messages_table($pdo);

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

if (!get_message_contact($pdo, $contactId)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Contact not found']);
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO MESSAGES (Sender_Account_ID, Receiver_Account_ID, Body)
    VALUES (?, ?, ?)
");
$stmt->execute([$currentAccountId, $contactId, $body]);

$messageId = (int) $pdo->lastInsertId();
$stmt = $pdo->prepare("
    SELECT Message_ID, Sender_Account_ID, Receiver_Account_ID, Body, Is_Read, Created_At
    FROM MESSAGES
    WHERE Message_ID = ?
");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

echo json_encode([
    'success' => true,
    'message' => [
        'message_id' => (int) $message['Message_ID'],
        'body' => $message['Body'],
        'is_mine' => true,
        'created_at' => $message['Created_At'],
        'time_label' => date('g:i A', strtotime($message['Created_At'])),
        'is_read' => (bool) $message['Is_Read'],
    ],
]);
