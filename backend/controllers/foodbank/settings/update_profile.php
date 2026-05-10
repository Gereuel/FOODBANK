<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

function normalizeFoodbankOperatingDays($selectedDays): string
{
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

    if (!$selected) {
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

    return $isContiguous && count($selected) > 1
        ? $selected[0] . '-' . $selected[count($selected) - 1]
        : implode(', ', $selected);
}

$organizationName = trim($_POST['organization_name'] ?? '');
$publicEmail = trim($_POST['public_email'] ?? '');
$publicPhone = trim($_POST['public_phone'] ?? '');
$operatingDays = normalizeFoodbankOperatingDays($_POST['operating_day_values'] ?? []);
$timeOpen = trim($_POST['time_open'] ?? '');
$timeClose = trim($_POST['time_close'] ?? '');
$physicalAddress = trim($_POST['physical_address'] ?? '');
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$managerAddress = trim($_POST['manager_address'] ?? '');

if ($organizationName === '' || $physicalAddress === '' || $firstName === '' || $lastName === '' || $email === '' || $timeOpen === '' || $timeClose === '' || $operatingDays === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Organization, address, hours, operating days, manager name, and login email are required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT Account_ID FROM ACCOUNTS WHERE Email = ? AND Account_ID != ? LIMIT 1");
    $stmt->execute([$email, $_SESSION['Account_ID']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Login email is already used by another account.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT FoodBank_ID FROM FOOD_BANKS WHERE Org_Email = ? AND Account_ID != ? LIMIT 1");
    $stmt->execute([$email, $_SESSION['Account_ID']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Login email is already used by another food bank.']);
        exit();
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE USERS u
        JOIN ACCOUNTS a ON a.User_ID = u.User_ID
        SET u.First_Name = ?,
            u.Last_Name = ?,
            u.Address = ?
        WHERE a.Account_ID = ?
    ");
    $stmt->execute([$firstName, $lastName, $managerAddress, $_SESSION['Account_ID']]);

    $stmt = $pdo->prepare("UPDATE ACCOUNTS SET Email = ?, Phone_Number = ? WHERE Account_ID = ?");
    $stmt->execute([$email, $phone, $_SESSION['Account_ID']]);

    $stmt = $pdo->prepare("
        UPDATE FOOD_BANKS
        SET Organization_Name = ?,
            Physical_Address = ?,
            Public_Email = ?,
            Public_Phone = ?,
            Operating_Days = ?,
            Time_Open = ?,
            Time_Close = ?,
            Manager_First_Name = ?,
            Manager_Last_Name = ?,
            Manager_Email = ?,
            Manager_Phone = ?,
            Manager_Address = ?,
            Org_Email = ?
        WHERE Account_ID = ?
    ");
    $stmt->execute([
        $organizationName,
        $physicalAddress,
        $publicEmail ?: null,
        $publicPhone ?: null,
        $operatingDays ?: null,
        $timeOpen,
        $timeClose,
        $firstName,
        $lastName,
        $email,
        $phone ?: null,
        $managerAddress ?: null,
        $email,
        $_SESSION['Account_ID'],
    ]);

    $_SESSION['Email'] = $email;
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Food bank profile updated.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Foodbank profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update profile.']);
}
