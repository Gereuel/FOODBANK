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

try {
    $stmt = $pdo->prepare("UPDATE NOTIFICATIONS SET Is_Read = TRUE WHERE Account_ID = ?");
    $stmt->execute([$_SESSION['Account_ID']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Mark all notifications read error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
