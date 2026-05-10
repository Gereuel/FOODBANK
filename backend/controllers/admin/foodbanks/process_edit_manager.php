<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../../login.php?error=unauthorized"); exit();
}

$foodbank_id = intval($_POST['foodbank_id'] ?? 0);
$first_name  = trim($_POST['manager_first_name'] ?? '');
$last_name   = trim($_POST['manager_last_name']  ?? '');
$email       = trim($_POST['manager_email']       ?? '');
$phone       = trim($_POST['manager_phone']       ?? '');
$address     = trim($_POST['manager_address']     ?? '');

if (!$foodbank_id || !$first_name || !$last_name || !$email || !$phone) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields"); exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE FOOD_BANKS
        SET Manager_First_Name = ?,
            Manager_Last_Name  = ?,
            Manager_Email      = ?,
            Manager_Phone      = ?,
            Manager_Address    = ?
        WHERE FoodBank_ID = ?
    ");
    $stmt->execute([$first_name, $last_name, $email, $phone, $address, $foodbank_id]);

    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=manager_updated"); exit();

} catch (PDOException $e) {
    error_log("Edit Manager Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error"); exit();
}
?>