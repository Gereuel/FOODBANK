<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/messages_contacts.php';

if (!isset($_SESSION['Account_ID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentAccountId = (int) $_SESSION['Account_ID'];
$contactId = (int) ($_GET['contact_id'] ?? 0);

if ($contactId <= 0 || $contactId === $currentAccountId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid contact']);
    exit();
}

$contact = get_message_contact($pdo, $contactId);
if (!$contact) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Contact not found']);
    exit();
}

$pdo->prepare("
    UPDATE MESSAGES
    SET Is_Read = 1
    WHERE Sender_Account_ID = ? AND Receiver_Account_ID = ?
")->execute([$contactId, $currentAccountId]);

$stmt = $pdo->prepare("
    SELECT Message_ID, Sender_Account_ID, Receiver_Account_ID, Body, Is_Read, Created_At
    FROM MESSAGES
    WHERE (Sender_Account_ID = ? AND Receiver_Account_ID = ?)
       OR (Sender_Account_ID = ? AND Receiver_Account_ID = ?)
    ORDER BY Created_At ASC, Message_ID ASC
");
$stmt->execute([$currentAccountId, $contactId, $contactId, $currentAccountId]);

$messages = [];
foreach ($stmt->fetchAll() as $row) {
    $messages[] = [
        'message_id' => (int) $row['Message_ID'],
        'body' => $row['Body'],
        'is_mine' => (int) $row['Sender_Account_ID'] === $currentAccountId,
        'created_at' => $row['Created_At'],
        'date_label' => message_date_label($row['Created_At']),
        'time_label' => message_clock_label($row['Created_At']),
        'is_read' => (bool) $row['Is_Read'],
    ];
}

echo json_encode([
    'success' => true,
    'contact' => $contact,
    'messages' => $messages,
]);
