<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
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
$maxBytes = 2 * 1024 * 1024;

if (!isset($allowed[$extension])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.']);
    exit();
}

if ((int) $_FILES['avatar']['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Profile image must be 2MB or smaller.']);
    exit();
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($_FILES['avatar']['tmp_name']);
if ($mimeType !== $allowed[$extension]) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The selected file is not a valid image.']);
    exit();
}

$uploadDir = app_path('uploads/avatars/');
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare image storage.']);
    exit();
}

$filename = 'fb_avatar_' . (int) $_SESSION['Account_ID'] . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;
$publicUrl = app_url('/uploads/avatars/' . $filename);

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save image.']);
    exit();
}

try {
    if (!db_column_exists($pdo, 'FOOD_BANKS', 'Manager_Profile_Picture_URL')) {
        throw new RuntimeException('Manager profile photo column is missing.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE FOOD_BANKS SET Manager_Profile_Picture_URL = ? WHERE Account_ID = ?");
    $stmt->execute([$publicUrl, $_SESSION['Account_ID']]);
    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Food bank account was not found.');
    }

    $stmt = $pdo->prepare("
        UPDATE USERS u
        JOIN ACCOUNTS a ON a.User_ID = u.User_ID
        SET u.Profile_Picture_URL = ?
        WHERE a.Account_ID = ?
    ");
    $stmt->execute([$publicUrl, $_SESSION['Account_ID']]);

    $pdo->commit();

    echo json_encode(['success' => true, 'avatar_url' => $publicUrl]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($targetPath) && is_file($targetPath)) {
        unlink($targetPath);
    }
    error_log('Foodbank avatar update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update avatar.']);
}
