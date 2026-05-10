<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['Account_ID'])) {
    $first_name  = $_POST['first_name'];
    $last_name   = $_POST['last_name'];
    $middle_name = $_POST['middle_name'] ?? null;
    $suffix      = $_POST['suffix'] ?? null;
    $address     = $_POST['address'];
    $birthdate   = $_POST['birthdate'];

    try {
        $pdo->beginTransaction();

        // Update USERS table
        $stmt = $pdo->prepare("
            UPDATE USERS u
            JOIN ACCOUNTS a ON u.User_ID = a.User_ID
            SET u.First_Name = ?, u.Middle_Name = ?, u.Last_Name = ?, u.Suffix = ?, u.Address = ?, u.Birthdate = ?
            WHERE a.Account_ID = ?
        ");
        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $address, $birthdate, $_SESSION['Account_ID']]);

        $pdo->commit();
        header("Location: /foodbank/frontend/views/admin/admin_index.php?status=profile_updated");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Update Failed: " . $e->getMessage());
    }
} else {
    header("Location: /foodbank/frontend/views/admin/admin_index.php?error=access_denied");
    exit();
}
?>