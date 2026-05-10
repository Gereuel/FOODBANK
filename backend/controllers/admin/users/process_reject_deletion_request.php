<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized");
    exit();
}

$account_id = intval($_POST['account_id'] ?? 0);
$request_id = intval($_POST['deletion_request_id'] ?? 0);

if (!$account_id || !$request_id) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields");
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE ACCOUNT_DELETION_REQUESTS
        SET Status = 'Rejected',
            Reviewed_At = NOW(),
            Reviewed_By = ?
        WHERE Request_ID = ?
          AND Account_ID = ?
          AND Status = 'Pending'
    ");
    $stmt->execute([$_SESSION['Account_ID'] ?? null, $request_id, $account_id]);

    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=deletion_request_rejected");
    exit();
} catch (PDOException $e) {
    error_log("Reject deletion request error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error");
    exit();
}
