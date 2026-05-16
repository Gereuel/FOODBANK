<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json_response.php';
require_once __DIR__ . '/../../helpers/support_schema.php';

register_json_exception_handler('Get support ticket');

if (!isset($_SESSION['Account_ID']) || !in_array($_SESSION['Account_Type'] ?? '', ['PA', 'FA', 'AA'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

ensure_support_tables($pdo);

$ticketId = (int) ($_GET['ticket_id'] ?? 0);
$accountId = (int) $_SESSION['Account_ID'];
if ($ticketId <= 0 || !support_can_access_ticket($pdo, $ticketId, $accountId)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

$stmt = $pdo->prepare("
    SELECT
        t.*,
        a.Account_Type,
        a.Email,
        u.First_Name,
        u.Last_Name,
        fb.Organization_Name
    FROM SUPPORT_TICKETS t
    JOIN ACCOUNTS a ON a.Account_ID = t.Reporter_Account_ID
    LEFT JOIN USERS u ON u.User_ID = a.User_ID
    LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
    WHERE t.Ticket_ID = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

$repliesStmt = $pdo->prepare("
    SELECT
        r.Reply_ID,
        r.Sender_Account_ID,
        r.Body,
        r.Created_At,
        a.Account_Type,
        a.Email,
        u.First_Name,
        u.Last_Name,
        fb.Organization_Name
    FROM SUPPORT_TICKET_REPLIES r
    JOIN ACCOUNTS a ON a.Account_ID = r.Sender_Account_ID
    LEFT JOIN USERS u ON u.User_ID = a.User_ID
    LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
    WHERE r.Ticket_ID = ?
    ORDER BY r.Created_At ASC, r.Reply_ID ASC
");
$repliesStmt->execute([$ticketId]);

$formatSender = static function (array $row): string {
    if ($row['Account_Type'] === 'AA') {
        $name = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
        return $name !== '' ? $name : 'Admin Support';
    }
    if ($row['Account_Type'] === 'FA') {
        return $row['Organization_Name'] ?: ($row['Email'] ?? 'Food Bank');
    }
    $name = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
    return $name !== '' ? $name : ($row['Email'] ?? 'User');
};

$replies = [];
foreach ($repliesStmt->fetchAll() as $row) {
    $replies[] = [
        'reply_id' => (int) $row['Reply_ID'],
        'body' => $row['Body'],
        'sender_name' => $formatSender($row),
        'sender_type' => $row['Account_Type'],
        'is_mine' => (int) $row['Sender_Account_ID'] === $accountId,
        'created_at' => $row['Created_At'],
        'created_label' => support_ticket_time_label($row['Created_At']),
    ];
}

$reporterName = $formatSender($ticket);

echo json_encode([
    'success' => true,
    'ticket' => [
        'ticket_id' => (int) $ticket['Ticket_ID'],
        'category' => $ticket['Category'],
        'subject' => $ticket['Subject'],
        'description' => $ticket['Description'],
        'status' => $ticket['Status'],
        'priority' => $ticket['Priority'],
        'reporter_name' => $reporterName,
        'reporter_type' => $ticket['Account_Type'],
        'created_label' => support_ticket_time_label($ticket['Created_At']),
        'updated_label' => support_ticket_time_label($ticket['Updated_At']),
    ],
    'replies' => $replies,
]);
