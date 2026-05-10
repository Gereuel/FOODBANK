<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

$accountId = (int) $_SESSION['Account_ID'];

try {
    $stmtUser = $pdo->prepare("SELECT User_ID FROM ACCOUNTS WHERE Account_ID = ? AND Account_Type = 'PA' LIMIT 1");
    $stmtUser->execute([$accountId]);
    $userId = $stmtUser->fetchColumn();

    if (!$userId) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Account not found.']);
        exit;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ACCOUNT_DELETION_REQUESTS (
            Request_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            User_ID INT DEFAULT NULL,
            Reason TEXT DEFAULT NULL,
            Status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
            Requested_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            Reviewed_At DATETIME DEFAULT NULL,
            Reviewed_By INT DEFAULT NULL,
            INDEX idx_deletion_request_account (Account_ID),
            INDEX idx_deletion_request_status (Status),
            CONSTRAINT fk_delete_request_account
                FOREIGN KEY (Account_ID) REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_delete_request_user
                FOREIGN KEY (User_ID) REFERENCES USERS(User_ID)
                ON DELETE SET NULL ON UPDATE CASCADE
        )
    ");

    $stmtPending = $pdo->prepare("
        SELECT Request_ID
        FROM ACCOUNT_DELETION_REQUESTS
        WHERE Account_ID = ?
          AND Status = 'Pending'
        LIMIT 1
    ");
    $stmtPending->execute([$accountId]);

    if (!$stmtPending->fetchColumn()) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO ACCOUNT_DELETION_REQUESTS (Account_ID, User_ID, Reason)
            VALUES (?, ?, ?)
        ");
        $stmtInsert->execute([$accountId, $userId, 'Requested by user from account settings.']);
    }

    $stmtDisable = $pdo->prepare("
        UPDATE ACCOUNTS
        SET Status = 'Inactive'
        WHERE Account_ID = ?
          AND Account_Type = 'PA'
    ");
    $stmtDisable->execute([$accountId]);

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Your deletion request has been sent to the admin.',
        'redirect' => '/foodbank/login.php?message=deletion_requested'
    ]);
} catch (PDOException $e) {
    error_log('PA deletion request error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to request account deletion.']);
}
