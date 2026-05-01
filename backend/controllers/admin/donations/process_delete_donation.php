<?php
session_start();
require_once '../../../config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    header("Location: ../../../../login.php?error=unauthorized");
    exit();
}

if (empty($_POST['donation_id'])) {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=missing_fields");
    exit();
}

$donation_id = intval($_POST['donation_id']);

try {
    // Get proof file path before deleting
    $stmt_proof = $pdo->prepare("SELECT Proof_Of_Delivery_URL FROM DONATIONS WHERE Donation_ID = ?");
    $stmt_proof->execute([$donation_id]);
    $proof_url = $stmt_proof->fetchColumn();

    // Delete the donation record
    $stmt = $pdo->prepare("DELETE FROM DONATIONS WHERE Donation_ID = ?");
    $stmt->execute([$donation_id]);

    // Delete proof file from disk if it exists
    if ($proof_url) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $proof_url;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&success=donation_deleted");
    exit();

} catch (PDOException $e) {
    error_log("Delete Donation Error: " . $e->getMessage());
    header("Location: /foodbank/frontend/views/admin/admin_index.php?page=donations&error=db_error");
    exit();
}
?>