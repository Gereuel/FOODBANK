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

function normalizeOperatingDays($selectedDays): string {
    if (!is_array($selectedDays)) {
        return '';
    }

    $dayOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $selected = [];

    foreach ($dayOrder as $day) {
        if (in_array($day, $selectedDays, true)) {
            $selected[] = $day;
        }
    }

    if (count($selected) === 7) {
        return 'Daily';
    }

    if (empty($selected)) {
        return '';
    }

    $indexes = array_map(fn($day) => array_search($day, $dayOrder, true), $selected);
    $isContiguous = true;
    for ($i = 1; $i < count($indexes); $i++) {
        if ($indexes[$i] !== $indexes[$i - 1] + 1) {
            $isContiguous = false;
            break;
        }
    }

    if ($isContiguous && count($selected) > 1) {
        return $selected[0] . '-' . $selected[count($selected) - 1];
    }

    return implode(', ', $selected);
}

function ensureMapImageColumn(PDO $pdo): void {
    try {
        $pdo->exec("ALTER TABLE FOOD_BANKS ADD COLUMN Map_Image_URL VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) !== 1060) {
            throw $e;
        }
    }
}

function uploadMapImage(): ?string {
    if (empty($_FILES['map_image']['name'])) {
        return null;
    }

    $ext = strtolower(pathinfo($_FILES['map_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=invalid_file"); exit();
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/foodbank/uploads/foodbank_maps/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('map_', true) . '.' . $ext;
    if (!move_uploaded_file($_FILES['map_image']['tmp_name'], $uploadDir . $filename)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=upload_failed"); exit();
    }

    return '/foodbank/uploads/foodbank_maps/' . $filename;
}

$org_name         = trim($_POST['organization_name'] ?? '');
$physical_address = trim($_POST['physical_address'] ?? '');
$org_email        = trim($_POST['org_email'] ?? '');
$verification     = $_POST['verification_status'] ?? 'Pending';
$org_status       = $_POST['org_status'] ?? 'Pending';
$time_open        = $_POST['time_open'] ?? '';
$time_close       = $_POST['time_close'] ?? '';
$operating_days   = normalizeOperatingDays($_POST['operating_day_values'] ?? []);
$public_email     = trim($_POST['public_email'] ?? '');
$public_phone     = trim($_POST['public_phone'] ?? '');
$mgr_first        = trim($_POST['manager_first_name'] ?? '');
$mgr_last         = trim($_POST['manager_last_name'] ?? '');
$mgr_email        = trim($_POST['manager_email'] ?? '');
$mgr_phone        = trim($_POST['manager_phone'] ?? '');
$mgr_address      = trim($_POST['manager_address'] ?? '');
$map_image_url    = uploadMapImage();

if ($operating_days === '') {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields"); exit();
}

try {
    ensureMapImageColumn($pdo);

    // Check email conflict
    $stmt_check = $pdo->prepare("SELECT FoodBank_ID FROM FOOD_BANKS WHERE Org_Email = ? AND FoodBank_ID != ?");
    $stmt_check->execute([$org_email, $foodbank_id]);
    if ($stmt_check->fetch()) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=email_taken"); exit();
    }

    $mapSql = $map_image_url ? ", Map_Image_URL = ?" : "";

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
            {$mapSql}
        WHERE FoodBank_ID = ?
    ");
    $params = [
        $org_name, $physical_address, $org_email,
        $verification, $org_status,
        $time_open, $time_close, $operating_days,
        $public_email ?: null, $public_phone ?: null,
        $mgr_first, $mgr_last, $mgr_email, $mgr_phone, $mgr_address
    ];

    if ($map_image_url) {
        $params[] = $map_image_url;
    }

    $params[] = $foodbank_id;
    $stmt->execute($params);

    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=foodbank_updated"); exit();

} catch (PDOException $e) {
    error_log("Edit FoodBank Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error"); exit();
}
?>
