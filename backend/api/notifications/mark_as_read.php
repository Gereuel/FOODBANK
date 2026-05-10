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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = $_GET['id'] ?? null;

    try {
        if ($notification_id === null) {
            // Mark all as read if no specific ID is provided
            $stmt = $pdo->prepare("UPDATE NOTIFICATIONS SET Is_Read = TRUE WHERE Account_ID = ?");
            $stmt->execute([$admin_account_id]);
            echo json_encode(['success' => 'All notifications marked as read']);
        } else {
            // Mark specific notification as read
            $stmt = $pdo->prepare("UPDATE NOTIFICATIONS SET Is_Read = TRUE WHERE Notification_ID = ? AND Account_ID = ?");
            $stmt->execute([$notification_id, $admin_account_id]);
            echo json_encode(['success' => 'Notification marked as read']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
