<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json_response.php';
require_once __DIR__ . '/../../helpers/support_schema.php';
require_once __DIR__ . '/../../helpers/support_notifications.php';

register_json_exception_handler('Create support ticket');

if (!isset($_SESSION['Account_ID']) || !in_array($_SESSION['Account_Type'] ?? '', ['PA', 'FA'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

ensure_support_tables($pdo);

$category = trim($_POST['category'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'Normal');
$allowedCategories = ['Account', 'Donation', 'Food Bank', 'Technical', 'Report', 'Other'];
$allowedPriorities = ['Low', 'Normal', 'High', 'Urgent'];

if (!in_array($category, $allowedCategories, true)) {
    $category = 'Other';
}

if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'Normal';
}

if ($subject === '' || $description === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Subject and details are required']);
    exit();
}

if (strlen($subject) > 160 || strlen($description) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket content is too long']);
    exit();
}

$adminStmt = $pdo->prepare("
    SELECT Account_ID
    FROM ACCOUNTS
    WHERE Account_Type = 'AA'
      AND Status = 'Active'
    ORDER BY Account_ID ASC
    LIMIT 1
");
$adminStmt->execute();
$adminId = (int) $adminStmt->fetchColumn();
$adminId = $adminId > 0 ? $adminId : null;

$stmt = $pdo->prepare("
    INSERT INTO SUPPORT_TICKETS (
        Reporter_Account_ID,
        Assigned_Admin_Account_ID,
        Category,
        Subject,
        Description,
        Priority,
        Created_At,
        Updated_At
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$now = gmdate('Y-m-d H:i:s');
$stmt->execute([(int) $_SESSION['Account_ID'], $adminId, $category, $subject, $description, $priority, $now, $now]);

$ticketId = (int) $pdo->lastInsertId();
$reporterName = support_account_display_name($pdo, (int) $_SESSION['Account_ID']);
notify_admin_accounts(
    $pdo,
    'support_ticket',
    "{$reporterName} submitted support ticket #{$ticketId}: {$subject}",
    '/frontend/views/admin/support.php'
);

echo json_encode(['success' => true, 'ticket_id' => $ticketId]);
