<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: /foodbank/login.php?error=unauthorized"); exit();
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbank_managers&error=invalid_request"); exit();
}

$foodbank_id = intval($_POST['foodbank_id'] ?? 0);

if (!$foodbank_id) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbank_managers&error=missing_fields"); exit();
}

try {
    // Clear manager info — don't delete the food bank itself
    $stmt = $pdo->prepare("
        UPDATE FOOD_BANKS
        SET Manager_First_Name = NULL,
            Manager_Last_Name  = NULL,
            Manager_Email      = NULL,
            Manager_Phone      = NULL,
            Manager_Address    = NULL
        WHERE FoodBank_ID = ?
    ");
    $stmt->execute([$foodbank_id]);

    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbank_managers&success=manager_removed"); exit();

} catch (PDOException $e) {
    error_log("Delete Manager Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=foodbank_managers&error=db_error"); exit();
}
?>
