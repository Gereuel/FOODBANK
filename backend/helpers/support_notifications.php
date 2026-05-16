<?php

function ensure_notifications_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS NOTIFICATIONS (
            Notification_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            Type VARCHAR(50),
            Message TEXT NOT NULL,
            Link VARCHAR(255),
            Is_Read TINYINT(1) DEFAULT 0,
            Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_account_read (Account_ID, Is_Read),
            CONSTRAINT fk_notifications_account
                FOREIGN KEY (Account_ID)
                REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function support_account_display_name(PDO $pdo, int $accountId): string
{
    $stmt = $pdo->prepare("
        SELECT
            a.Account_Type,
            a.Email,
            u.First_Name,
            u.Last_Name,
            fb.Organization_Name
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON u.User_ID = a.User_ID
        LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
        WHERE a.Account_ID = ?
        LIMIT 1
    ");
    $stmt->execute([$accountId]);
    $row = $stmt->fetch();

    if (!$row) {
        return 'A user';
    }

    if ($row['Account_Type'] === 'FA') {
        return $row['Organization_Name'] ?: ($row['Email'] ?? 'A food bank');
    }

    $name = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
    return $name !== '' ? $name : ($row['Email'] ?? 'A user');
}

function notify_admin_accounts(PDO $pdo, string $type, string $message, string $link = '/frontend/views/admin/support.php', ?int $onlyAdminAccountId = null): void
{
    ensure_notifications_table($pdo);

    if ($onlyAdminAccountId !== null && $onlyAdminAccountId > 0) {
        $adminIds = [$onlyAdminAccountId];
    } else {
        $stmt = $pdo->query("
            SELECT Account_ID
            FROM ACCOUNTS
            WHERE Account_Type = 'AA'
              AND Status = 'Active'
        ");
        $adminIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    if (!$adminIds) {
        return;
    }

    $insert = $pdo->prepare("
        INSERT INTO NOTIFICATIONS (Account_ID, Type, Message, Link)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($adminIds as $adminId) {
        $insert->execute([$adminId, $type, $message, $link]);
    }
}
