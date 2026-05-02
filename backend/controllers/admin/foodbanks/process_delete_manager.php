<?php
session_start();
require_once '../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../../login.php?error=unauthorized"); exit();
}

$foodbank_id = intval($_POST['foodbank_id'] ?? 0);

if (!$foodbank_id) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields"); exit();
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

    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=manager_removed"); exit();

} catch (PDOException $e) {
    error_log("Delete Manager Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error"); exit();
}
?>