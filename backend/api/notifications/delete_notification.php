<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'AA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$notificationId = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($notificationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM NOTIFICATIONS WHERE Notification_ID = ? AND Account_ID = ?");
    $stmt->execute([$notificationId, $_SESSION['Account_ID']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Delete notification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
