<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized"); exit();
}

$foodbank_id = intval($_POST['foodbank_id'] ?? 0);
if (!$foodbank_id) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields"); exit();
}

$org_name         = trim($_POST['organization_name'] ?? '');
$physical_address = trim($_POST['physical_address'] ?? '');
$org_email        = trim($_POST['org_email'] ?? '');
$verification     = $_POST['verification_status'] ?? 'Pending';
$org_status       = $_POST['org_status'] ?? 'Pending';
$time_open        = $_POST['time_open'] ?? '';
$time_close       = $_POST['time_close'] ?? '';
$operating_days   = trim($_POST['operating_days'] ?? '');
$public_email     = trim($_POST['public_email'] ?? '');
$public_phone     = trim($_POST['public_phone'] ?? '');
$mgr_first        = trim($_POST['manager_first_name'] ?? '');
$mgr_last         = trim($_POST['manager_last_name'] ?? '');
$mgr_email        = trim($_POST['manager_email'] ?? '');
$mgr_phone        = trim($_POST['manager_phone'] ?? '');
$mgr_address      = trim($_POST['manager_address'] ?? '');

try {
    // Check email conflict
    $stmt_check = $pdo->prepare("SELECT FoodBank_ID FROM FOOD_BANKS WHERE Org_Email = ? AND FoodBank_ID != ?");
    $stmt_check->execute([$org_email, $foodbank_id]);
    if ($stmt_check->fetch()) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=email_taken"); exit();
    }

    $stmt = $pdo->prepare("
        UPDATE FOOD_BANKS SET
            Organization_Name   = ?,
            Physical_Address    = ?,
            Org_Email           = ?,
            Verification_Status = ?,
            Org_Status          = ?,
            Time_Open           = ?,
            Time_Close          = ?,
            Operating_Days      = ?,
            Public_Email        = ?,
            Public_Phone        = ?,
            Manager_First_Name  = ?,
            Manager_Last_Name   = ?,
            Manager_Email       = ?,
            Manager_Phone       = ?,
            Manager_Address     = ?
        WHERE FoodBank_ID = ?
    ");
    $stmt->execute([
        $org_name, $physical_address, $org_email,
        $verification, $org_status,
        $time_open, $time_close, $operating_days,
        $public_email ?: null, $public_phone ?: null,
        $mgr_first, $mgr_last, $mgr_email, $mgr_phone, $mgr_address,
        $foodbank_id
    ]);

    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=foodbank_updated"); exit();

} catch (PDOException $e) {
    error_log("Edit FoodBank Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error"); exit();
}
?>