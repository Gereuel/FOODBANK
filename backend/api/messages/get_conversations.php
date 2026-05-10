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

$stmt = $pdo->prepare("
    SELECT m.*
    FROM MESSAGES m
    JOIN (
        SELECT
            CASE
                WHEN Sender_Account_ID = ? THEN Receiver_Account_ID
                ELSE Sender_Account_ID
            END AS Counterpart_ID,
            MAX(Message_ID) AS Latest_Message_ID
        FROM MESSAGES
        WHERE Sender_Account_ID = ? OR Receiver_Account_ID = ?
        GROUP BY Counterpart_ID
    ) latest ON latest.Latest_Message_ID = m.Message_ID
    ORDER BY m.Created_At DESC, m.Message_ID DESC
");
$stmt->execute([$currentAccountId, $currentAccountId, $currentAccountId]);
$rows = $stmt->fetchAll();

$conversations = [];
foreach ($rows as $row) {
    $counterpartId = (int) ($row['Sender_Account_ID'] == $currentAccountId
        ? $row['Receiver_Account_ID']
        : $row['Sender_Account_ID']);

    $contact = get_message_contact($pdo, $counterpartId);
    if (!$contact) {
        continue;
    }

    $conversations[] = [
        'contact' => $contact,
        'last_message' => [
            'body' => $row['Body'],
            'is_mine' => (int) $row['Sender_Account_ID'] === $currentAccountId,
            'created_at' => $row['Created_At'],
            'time_label' => message_time_label($row['Created_At']),
            'is_read' => (bool) $row['Is_Read'],
        ],
    ];
}

echo json_encode(['success' => true, 'conversations' => $conversations]);
