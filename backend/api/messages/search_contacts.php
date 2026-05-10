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
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'contacts' => []]);
    exit();
}

$term = '%' . $query . '%';

$stmt = $pdo->prepare("
    SELECT
        a.Account_ID,
        a.Account_Type,
        a.Custom_App_ID,
        a.Email,
        a.Phone_Number,
        u.First_Name,
        u.Last_Name,
        u.Address,
        u.Profile_Picture,
        u.Profile_Picture_URL,
        fb.Organization_Name,
        fb.Physical_Address,
        fb.Public_Email,
        fb.Public_Phone,
        fb.Custom_FoodBank_ID
    FROM ACCOUNTS a
    LEFT JOIN USERS u ON u.User_ID = a.User_ID
    LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
    WHERE a.Account_ID != ?
      AND a.Status = 'Active'
      AND a.Account_Type IN ('PA', 'FA')
      AND (
          a.Email LIKE ?
          OR a.Custom_App_ID LIKE ?
          OR CONCAT_WS(' ', u.First_Name, u.Last_Name) LIKE ?
          OR fb.Organization_Name LIKE ?
          OR fb.Custom_FoodBank_ID LIKE ?
      )
    ORDER BY
        CASE WHEN a.Account_Type = 'FA' THEN 0 ELSE 1 END,
        COALESCE(fb.Organization_Name, u.First_Name, a.Email)
    LIMIT 12
");
$stmt->execute([$currentAccountId, $term, $term, $term, $term, $term]);

$contacts = array_map('format_message_contact', $stmt->fetchAll());

echo json_encode(['success' => true, 'contacts' => $contacts]);
