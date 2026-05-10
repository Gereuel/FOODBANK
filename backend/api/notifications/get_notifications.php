<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['Account_ID']) || $_SESSION['Account_Type'] !== 'AA') {
    echo json_encode(['error' => 'Unauthorized Access']);
    exit();
}

$admin_account_id = $_SESSION['Account_ID'];

try {
    // Fetch notifications for the current admin, ordered by creation date
    $stmt = $pdo->prepare("
        SELECT Notification_ID, Type, Message, Link, Is_Read, Created_At
        FROM NOTIFICATIONS
        WHERE Account_ID = ?
        ORDER BY Created_At DESC
        LIMIT 10 -- Limit to last 10 notifications for dropdown
    ");
    $stmt->execute([$admin_account_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($notifications);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
