<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please choose an image.']);
    exit();
}

$allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

if (!isset($allowed[$extension])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.']);
    exit();
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/foodbank/uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = 'avatar_' . (int) $_SESSION['Account_ID'] . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;
$publicUrl = '/foodbank/uploads/avatars/' . $filename;

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save image.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE USERS u
        JOIN ACCOUNTS a ON a.User_ID = u.User_ID
        SET u.Profile_Picture_URL = ?
        WHERE a.Account_ID = ?
    ");
    $stmt->execute([$publicUrl, $_SESSION['Account_ID']]);

    echo json_encode(['success' => true, 'avatar_url' => $publicUrl]);
} catch (PDOException $e) {
    error_log('PA avatar update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update avatar.']);
}
