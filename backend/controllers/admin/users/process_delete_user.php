<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

// Security check
if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized");
    exit();
}

// Validate required fields
if (empty($_POST['user_id']) || empty($_POST['account_id'])) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields");
    exit();
}

$user_id    = intval($_POST['user_id']);
$account_id = intval($_POST['account_id']);
$deletion_request_id = intval($_POST['deletion_request_id'] ?? 0);

// Prevent admin from deleting their own account
if ($user_id === intval($_SESSION['User_ID'])) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=cannot_delete_self");
    exit();
}

try {
    $pdo->beginTransaction();

    if ($deletion_request_id > 0) {
        $stmt_request = $pdo->prepare("
            UPDATE ACCOUNT_DELETION_REQUESTS
            SET Status = 'Approved',
                Reviewed_At = NOW(),
                Reviewed_By = ?
            WHERE Request_ID = ?
              AND Account_ID = ?
              AND Status = 'Pending'
        ");
        $stmt_request->execute([$_SESSION['Account_ID'] ?? null, $deletion_request_id, $account_id]);
    }

    // Delete account first (foreign key constraint)
    $stmt_account = $pdo->prepare("DELETE FROM ACCOUNTS WHERE Account_ID = ? AND User_ID = ?");
    $stmt_account->execute([$account_id, $user_id]);

    // Delete user
    $stmt_user = $pdo->prepare("DELETE FROM USERS WHERE User_ID = ?");
    $stmt_user->execute([$user_id]);

    $pdo->commit();

    $success = $deletion_request_id > 0 ? 'deletion_request_approved' : 'user_deleted';
    header("Location: /foodbank/frontend/views/admin/admin_index.php?success={$success}");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Delete User Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error");
    exit();
}
?>
