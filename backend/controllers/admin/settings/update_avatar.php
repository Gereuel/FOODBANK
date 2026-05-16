<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Security Check
if (!isset($_SESSION['Account_ID']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: /foodbank/login.php?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Validation
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB limit
    
    if (!in_array($file['type'], $allowed_types)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=invalid_file_type");
        exit();
    }
    
    if ($file['size'] > $max_size) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=file_too_large");
        exit();
    }

    // Setup Directory
    $upload_dir = app_path('uploads/avatars/');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate Unique Name to prevent caching and naming conflicts
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $_SESSION['Account_ID'] . '_' . time() . '.' . $extension;
    $target_path = $upload_dir . $filename;
    $public_url = app_url('/uploads/avatars/' . $filename);

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        try {
            // Update the USERS table by joining with ACCOUNTS via the session ID
            $stmt = $pdo->prepare("
                UPDATE USERS u
                JOIN ACCOUNTS a ON u.User_ID = a.User_ID
                SET u.Profile_Picture_URL = ?
                WHERE a.Account_ID = ?
            ");
            $stmt->execute([$public_url, $_SESSION['Account_ID']]);

            header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&status=profile_updated");
            exit();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
}
header("Location: /foodbank/frontend/views/admin/admin_index.php?page=settings&error=upload_failed");
exit();
