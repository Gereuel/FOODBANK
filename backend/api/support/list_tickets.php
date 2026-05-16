<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json_response.php';
require_once __DIR__ . '/../../helpers/support_schema.php';

register_json_exception_handler('List support tickets');

if (!isset($_SESSION['Account_ID']) || !in_array($_SESSION['Account_Type'] ?? '', ['PA', 'FA', 'AA'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

ensure_support_tables($pdo);

$isAdmin = support_is_admin();
$params = [];
$where = '';
if (!$isAdmin) {
    $where = 'WHERE t.Reporter_Account_ID = ?';
    $params[] = (int) $_SESSION['Account_ID'];
}

$stmt = $pdo->prepare("
    SELECT
        t.Ticket_ID,
        t.Category,
        t.Subject,
        t.Description,
        t.Status,
        t.Priority,
        t.Created_At,
        t.Updated_At,
        a.Account_Type,
        a.Email,
        u.First_Name,
        u.Last_Name,
        fb.Organization_Name
    FROM SUPPORT_TICKETS t
    JOIN ACCOUNTS a ON a.Account_ID = t.Reporter_Account_ID
    LEFT JOIN USERS u ON u.User_ID = a.User_ID
    LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
    {$where}
    ORDER BY t.Updated_At DESC, t.Ticket_ID DESC
    LIMIT 100
");
$stmt->execute($params);

$tickets = [];
foreach ($stmt->fetchAll() as $row) {
    $reporterName = $row['Account_Type'] === 'FA'
        ? ($row['Organization_Name'] ?: $row['Email'])
        : trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
    if ($reporterName === '') {
        $reporterName = $row['Email'] ?? 'Unknown';
    }

    $tickets[] = [
        'ticket_id' => (int) $row['Ticket_ID'],
        'category' => $row['Category'],
        'subject' => $row['Subject'],
        'description' => $row['Description'],
        'status' => $row['Status'],
        'priority' => $row['Priority'],
        'reporter_name' => $reporterName,
        'reporter_type' => $row['Account_Type'],
        'created_at' => $row['Created_At'],
        'updated_at' => $row['Updated_At'],
        'updated_label' => support_ticket_time_label($row['Updated_At']),
    ];
}

echo json_encode(['success' => true, 'tickets' => $tickets]);
