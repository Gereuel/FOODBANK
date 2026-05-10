<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/helpers/schema_columns.php';

function get_message_contact(PDO $pdo, int $accountId): ?array
{
    $managerAvatarSelect = db_column_exists($pdo, 'FOOD_BANKS', 'Manager_Profile_Picture_URL')
        ? 'fb.Manager_Profile_Picture_URL'
        : 'NULL AS Manager_Profile_Picture_URL';

    $stmt = $pdo->prepare("
        SELECT
            a.Account_ID,
            a.Account_Type,
            a.Custom_App_ID,
            a.Email,
            a.Phone_Number,
            u.First_Name,
            u.Last_Name,
            u.Address,
            u.Profile_Picture,
            u.Profile_Picture_URL,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Public_Email,
            fb.Public_Phone,
            fb.Custom_FoodBank_ID,
            {$managerAvatarSelect}
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON u.User_ID = a.User_ID
        LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
        WHERE a.Account_ID = ?
          AND a.Status = 'Active'
          AND a.Account_Type IN ('PA', 'FA')
        LIMIT 1
    ");
    $stmt->execute([$accountId]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return format_message_contact($row);
}

function format_message_contact(array $row): array
{
    $isFoodBank = $row['Account_Type'] === 'FA';
    $name = $isFoodBank
        ? ($row['Organization_Name'] ?: trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? '')))
        : trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));

    if ($name === '') {
        $name = $row['Email'] ?? 'Unknown Contact';
    }

    $email = $isFoodBank
        ? ($row['Public_Email'] ?: ($row['Email'] ?? ''))
        : ($row['Email'] ?? '');
    $phone = $isFoodBank
        ? ($row['Public_Phone'] ?: ($row['Phone_Number'] ?? ''))
        : ($row['Phone_Number'] ?? '');
    $address = $isFoodBank
        ? ($row['Physical_Address'] ?? '')
        : ($row['Address'] ?? '');
    $avatarUrl = $isFoodBank
        ? (($row['Manager_Profile_Picture_URL'] ?? '') ?: (($row['Profile_Picture_URL'] ?? '') ?: profile_picture_data_uri($row['Profile_Picture'] ?? null)))
        : (($row['Profile_Picture_URL'] ?? '') ?: profile_picture_data_uri($row['Profile_Picture'] ?? null));

    return [
        'account_id' => (int) $row['Account_ID'],
        'account_type' => $row['Account_Type'],
        'name' => $name,
        'subtitle' => $isFoodBank ? 'Food Bank' : 'Individual',
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'avatar_url' => $avatarUrl,
        'initials' => contact_initials($name),
        'custom_id' => $isFoodBank ? ($row['Custom_FoodBank_ID'] ?? null) : ($row['Custom_App_ID'] ?? null),
    ];
}

function profile_picture_data_uri($profilePicture): ?string
{
    if (empty($profilePicture)) {
        return null;
    }

    if (is_resource($profilePicture)) {
        $profilePicture = stream_get_contents($profilePicture);
    }

    if (!is_string($profilePicture) || $profilePicture === '') {
        return null;
    }

    $mimeType = 'image/jpeg';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedType = $finfo->buffer($profilePicture);
        if (is_string($detectedType) && strpos($detectedType, 'image/') === 0) {
            $mimeType = $detectedType;
        }
    }

    return 'data:' . $mimeType . ';base64,' . base64_encode($profilePicture);
}

function contact_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? '';
    $last = count($parts) > 1 ? ($parts[count($parts) - 1][0] ?? '') : '';
    $initials = strtoupper($first . $last);

    return $initials !== '' ? $initials : '??';
}

function message_time_label(string $dateTime): string
{
    $timestamp = strtotime($dateTime);
    if (!$timestamp) {
        return '';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' mins ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'hrs ago';
    }

    return date('M j', $timestamp);
}
