<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized"); exit();
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

$required = ['organization_name', 'physical_address', 'org_email', 'org_password',
             'manager_first_name', 'manager_last_name', 'manager_email', 'manager_phone',
             'manager_address', 'time_open', 'time_close'];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields"); exit();
    }
}

if ($_POST['org_password'] !== $_POST['org_password_confirm']) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=password_mismatch"); exit();
}

$org_name            = trim($_POST['organization_name']);
$physical_address    = trim($_POST['physical_address']);
$org_email           = trim($_POST['org_email']);
$org_password_hash   = password_hash($_POST['org_password'], PASSWORD_DEFAULT);
$verification_status = $_POST['verification_status'] ?? 'Pending';
$org_status          = $_POST['org_status'] ?? 'Pending';
$time_open           = $_POST['time_open'];
$time_close          = $_POST['time_close'];
$operating_days      = normalizeOperatingDays($_POST['operating_day_values'] ?? []);
$public_email        = trim($_POST['public_email'] ?? '');
$public_phone        = trim($_POST['public_phone'] ?? '');
$mgr_first           = trim($_POST['manager_first_name']);
$mgr_last            = trim($_POST['manager_last_name']);
$mgr_email           = trim($_POST['manager_email']);
$mgr_phone           = trim($_POST['manager_phone']);
$mgr_address         = trim($_POST['manager_address']);
$map_image_url       = uploadMapImage();

if ($operating_days === '') {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=missing_fields"); exit();
}

// Handle legal documents upload
$legal_url = '';
if (!empty($_FILES['legal_documents']['name'])) {
    $upload_dir  = '../../../../uploads/legal/';
    $ext         = pathinfo($_FILES['legal_documents']['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['pdf', 'zip'];

    if (!in_array(strtolower($ext), $allowed_ext)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=invalid_file"); exit();
    }

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename  = uniqid('legal_', true) . '.' . $ext;
    $legal_url = '/foodbank/uploads/legal/' . $filename;
    move_uploaded_file($_FILES['legal_documents']['tmp_name'], $upload_dir . $filename);
}

try {
    ensureMapImageColumn($pdo);

    // Check if org email already exists
    $stmt_check = $pdo->prepare("SELECT FoodBank_ID FROM FOOD_BANKS WHERE Org_Email = ?");
    $stmt_check->execute([$org_email]);
    if ($stmt_check->fetch()) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?error=email_taken"); exit();
    }

    $pdo->beginTransaction();

    // Auto-generate Custom_FoodBank_ID: FB-YYYY-FA0001
    $year      = date('Y');
    $stmt_last = $pdo->prepare("SELECT Custom_FoodBank_ID FROM FOOD_BANKS WHERE Custom_FoodBank_ID LIKE ? ORDER BY FoodBank_ID DESC LIMIT 1");
    $stmt_last->execute(["FB-{$year}-FA%"]);
    $last = $stmt_last->fetchColumn();

    $new_num        = $last ? str_pad(intval(substr($last, -4)) + 1, 4, '0', STR_PAD_LEFT) : '0001';
    $custom_fb_id   = "FB-{$year}-FA{$new_num}";

    // Create FA account (no User_ID since org B)
    $stmt_account = $pdo->prepare("
        INSERT INTO ACCOUNTS (User_ID, Account_Type, Custom_App_ID, Email, Phone_Number, Password_Hash)
        VALUES (NULL, 'FA', ?, ?, ?, ?)
    ");
    $stmt_account->execute([$custom_fb_id, $org_email, $mgr_phone, $org_password_hash]);
    $account_id = $pdo->lastInsertId();

    // Insert food bank
    $stmt_fb = $pdo->prepare("
        INSERT INTO FOOD_BANKS (
            Account_ID, Organization_Name, Physical_Address,
            Public_Email, Public_Phone,
            Time_Open, Time_Close, Operating_Days,
            Legal_Documents_URL, Verification_Status,
            Org_Email, Org_Password_Hash, Org_Status,
            Custom_FoodBank_ID,
            Manager_First_Name, Manager_Last_Name,
            Manager_Email, Manager_Phone, Manager_Address,
            Map_Image_URL
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_fb->execute([
        $account_id, $org_name, $physical_address,
        $public_email ?: null, $public_phone ?: null,
        $time_open, $time_close, $operating_days,
        $legal_url, $verification_status,
        $org_email, $org_password_hash, $org_status,
        $custom_fb_id,
        $mgr_first, $mgr_last,
        $mgr_email, $mgr_phone, $mgr_address,
        $map_image_url
    ]);

    // Create a notification for the admin
    $stmt_notif = $pdo->prepare("
        INSERT INTO NOTIFICATIONS (Account_ID, Type, Message, Link)
        VALUES (?, ?, ?, ?)
    ");
    $notif_message = "New food bank '{$org_name}' registered and awaiting verification.";
    $stmt_notif->execute([$_SESSION['Account_ID'], 'new_foodbank', $notif_message, '/foodbank/frontend/views/admin/foodbanks.php']);

    $pdo->commit();
    header("Location: /foodbank/frontend/views/admin/admin_index.php?success=foodbank_added"); exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Add FoodBank Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=db_error"); exit();
}
?>
