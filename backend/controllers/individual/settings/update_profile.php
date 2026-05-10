<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$firstName = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT Account_ID FROM ACCOUNTS WHERE Email = ? AND Account_ID != ? LIMIT 1");
    $stmt->execute([$email, $_SESSION['Account_ID']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email is already used by another account.']);
        exit();
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE USERS u
        JOIN ACCOUNTS a ON a.User_ID = u.User_ID
        SET u.First_Name = ?,
            u.Middle_Name = ?,
            u.Last_Name = ?,
            u.Address = ?
        WHERE a.Account_ID = ?
    ");
    $stmt->execute([$firstName, $middleName ?: null, $lastName, $address, $_SESSION['Account_ID']]);

    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Email = ?, Phone_Number = ? WHERE Account_ID = ?");
    $stmt->execute([$email, $phone, $_SESSION['Account_ID']]);

    $_SESSION['Email'] = $email;
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Profile updated.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PA profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update profile.']);
}
