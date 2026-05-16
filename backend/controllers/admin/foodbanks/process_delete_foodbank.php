<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized"); exit();
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbanks&error=invalid_request"); exit();
}

$foodbank_id = intval($_POST['foodbank_id'] ?? 0);
if (!$foodbank_id) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbanks&error=missing_fields"); exit();
}

try {
    // Get legal doc URL to delete file
    $stmt_doc = $pdo->prepare("SELECT Legal_Documents_URL, Account_ID FROM FOOD_BANKS WHERE FoodBank_ID = ?");
    $stmt_doc->execute([$foodbank_id]);
    $fb = $stmt_doc->fetch();

    $pdo->beginTransaction();

    // Delete food bank (CASCADE will handle donations)
    $stmt = $pdo->prepare("DELETE FROM FOOD_BANKS WHERE FoodBank_ID = ?");
    $stmt->execute([$foodbank_id]);

    // Delete linked FA account
    if ($fb && $fb['Account_ID']) {
        $stmt_acc = $pdo->prepare("DELETE FROM ACCOUNTS WHERE Account_ID = ?");
        $stmt_acc->execute([$fb['Account_ID']]);
    }

    $pdo->commit();

    // Delete legal document file
    if ($fb && $fb['Legal_Documents_URL']) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $fb['Legal_Documents_URL'];
        if (file_exists($file_path)) unlink($file_path);
    }

    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbanks&success=foodbank_deleted"); exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Delete FoodBank Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbanks&error=db_error"); exit();
}
?>
