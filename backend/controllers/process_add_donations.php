<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../login.php?error=unauthorized");
    exit();
}

$required = ['donor_account_id', 'item_type', 'quantity_description', 'foodbank_id', 'pickup_address', 'status', 'donation_time'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=missing_fields");
        exit();
    }
}

$donor_account_id    = intval($_POST['donor_account_id']);
$item_type           = trim($_POST['item_type']);
$item_description    = trim($_POST['item_description'] ?? '');
$quantity            = trim($_POST['quantity_description']);
$foodbank_id         = intval($_POST['foodbank_id']);
$pickup_address      = trim($_POST['pickup_address']);
$status              = trim($_POST['status']);
$donation_time       = trim($_POST['donation_time']);
$notes               = trim($_POST['notes'] ?? '');

// Validate enums
$allowed_types    = ['Food Items', 'Clothing', 'Cash Donation', 'Medicine', 'Perishable Goods', 'Other'];
$allowed_statuses = ['Pending', 'In Transit', 'Received', 'Cancelled'];

if (!in_array($item_type, $allowed_types) || !in_array($status, $allowed_statuses)) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=invalid_data");
    exit();
}

// Handle proof of delivery upload
$proof_url = null;
if (!empty($_FILES['proof_of_delivery']['name'])) {
    $upload_dir  = '../../../uploads/proof/';
    $ext         = pathinfo($_FILES['proof_of_delivery']['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    if (!in_array(strtolower($ext), $allowed_ext)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=invalid_file");
        exit();
    }

    $filename  = uniqid('proof_', true) . '.' . $ext;
    $proof_url = '/foodbank/uploads/proof/' . $filename;
    move_uploaded_file($_FILES['proof_of_delivery']['tmp_name'], $upload_dir . $filename);
}

try {
    $pdo->beginTransaction();

    // Auto-generate Tracking Number: FB-YYYY-RP0001
    $year      = date('Y');
    $stmt_last = $pdo->prepare("
        SELECT Tracking_Number FROM DONATIONS 
        WHERE Tracking_Number LIKE ? 
        ORDER BY Donation_ID DESC LIMIT 1
    ");
    $stmt_last->execute(["FB-{$year}-RP%"]);
    $last = $stmt_last->fetchColumn();

    if ($last) {
        $last_num = intval(substr($last, -4));
        $new_num  = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    $tracking_number = "FB-{$year}-RP{$new_num}";

    // Insert donation
    $stmt = $pdo->prepare("
        INSERT INTO DONATIONS (
            Tracking_Number,
            Donor_Account_ID,
            Item_Type,
            Item_Description,
            Quantity_Description,
            FoodBank_ID,
            Pickup_Address,
            Status,
            Donation_Time,
            Proof_Of_Delivery_URL,
            Notes,
            Date_Donated,
            Generated_On
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $tracking_number,
        $donor_account_id,
        $item_type,
        $item_description ?: null,
        $quantity,
        $foodbank_id,
        $pickup_address,
        $status,
        $donation_time,
        $proof_url,
        $notes ?: null
    ]);

    $pdo->commit();
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&success=donation_added");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Add Donation Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=db_error");
    exit();
}
?>