<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized");
    exit();
}

if (empty($_POST['donation_id']) || empty($_POST['status']) || empty($_POST['quantity_description'])) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=missing_fields");
    exit();
}

$donation_id = intval($_POST['donation_id']);
$status      = trim($_POST['status']);
$quantity    = trim($_POST['quantity_description']);
$notes       = trim($_POST['notes'] ?? '');

$allowed_statuses = ['Pending', 'In Transit', 'Received', 'Cancelled'];
if (!in_array($status, $allowed_statuses)) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=invalid_data");
    exit();
}

// Handle proof of delivery upload
$proof_url = null;
$has_new_proof = !empty($_FILES['proof_of_delivery']['name']);

if ($has_new_proof) {
    $upload_dir  = app_path('uploads/proof/');
    $ext         = pathinfo($_FILES['proof_of_delivery']['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    if (!in_array(strtolower($ext), $allowed_ext)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=invalid_file");
        exit();
    }

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename  = uniqid('proof_', true) . '.' . $ext;
    $proof_url = app_url('/uploads/proof/' . $filename);

    if (!move_uploaded_file($_FILES['proof_of_delivery']['tmp_name'], $upload_dir . $filename)) {
        header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=upload_failed");
        exit();
    }
}

try {
    if ($has_new_proof) {
        // Delete old proof file if exists
        $stmt_old = $pdo->prepare("SELECT Proof_Of_Delivery_URL FROM DONATIONS WHERE Donation_ID = ?");
        $stmt_old->execute([$donation_id]);
        $old = $stmt_old->fetchColumn();

        if ($old) {
            $old_path = $_SERVER['DOCUMENT_ROOT'] . $old;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }

        $stmt = $pdo->prepare("
            UPDATE DONATIONS
            SET Status                = ?,
                Quantity_Description  = ?,
                Notes                 = ?,
                Proof_Of_Delivery_URL = ?,
                Date_Updated          = NOW()
            WHERE Donation_ID = ?
        ");
        $stmt->execute([$status, $quantity, $notes ?: null, $proof_url, $donation_id]);

    } else {
        $stmt = $pdo->prepare("
            UPDATE DONATIONS
            SET Status               = ?,
                Quantity_Description = ?,
                Notes                = ?,
                Date_Updated         = NOW()
            WHERE Donation_ID = ?
        ");
        $stmt->execute([$status, $quantity, $notes ?: null, $donation_id]);
    }

    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&success=donation_updated");
    exit();

} catch (PDOException $e) {
    error_log("Edit Donation Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=db_error");
    exit();
}
?>
