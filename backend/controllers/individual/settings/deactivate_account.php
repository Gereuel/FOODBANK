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

require_once __DIR__ . '/../../../config/database.php';

try {
    $stmt = $pdo->prepare("
        UPDATE ACCOUNTS
        SET Status = 'Inactive'
        WHERE Account_ID = ?
          AND Account_Type = 'PA'
    ");
    $stmt->execute([$_SESSION['Account_ID']]);

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Your account has been deactivated.',
        'redirect' => '/foodbank/login.php?message=account_deactivated'
    ]);
} catch (PDOException $e) {
    error_log('PA account deactivation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to deactivate account.']);
}
