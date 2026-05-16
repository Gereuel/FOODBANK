<?php

function ensure_support_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS SUPPORT_TICKETS (
            Ticket_ID INT AUTO_INCREMENT PRIMARY KEY,
            Reporter_Account_ID INT NOT NULL,
            Assigned_Admin_Account_ID INT NULL,
            Category VARCHAR(60) NOT NULL,
            Subject VARCHAR(160) NOT NULL,
            Description TEXT NOT NULL,
            Status VARCHAR(30) NOT NULL DEFAULT 'Open',
            Priority VARCHAR(30) NOT NULL DEFAULT 'Normal',
            Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            Updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_support_reporter (Reporter_Account_ID),
            INDEX idx_support_admin (Assigned_Admin_Account_ID),
            INDEX idx_support_status (Status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS SUPPORT_TICKET_REPLIES (
            Reply_ID INT AUTO_INCREMENT PRIMARY KEY,
            Ticket_ID INT NOT NULL,
            Sender_Account_ID INT NOT NULL,
            Body TEXT NOT NULL,
            Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_support_replies_ticket (Ticket_ID, Created_At),
            INDEX idx_support_replies_sender (Sender_Account_ID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function support_current_account_type(): string
{
    return $_SESSION['Account_Type'] ?? '';
}

function support_is_admin(): bool
{
    return support_current_account_type() === 'AA';
}

function support_can_access_ticket(PDO $pdo, int $ticketId, int $accountId): bool
{
    if (support_is_admin()) {
        return true;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM SUPPORT_TICKETS WHERE Ticket_ID = ? AND Reporter_Account_ID = ?");
    $stmt->execute([$ticketId, $accountId]);
    return (bool) $stmt->fetchColumn();
}

function support_ticket_time_label(?string $dateTime): string
{
    if (!$dateTime) {
        return '';
    }

    try {
        $time = (new DateTimeImmutable($dateTime, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Asia/Manila'));
    } catch (Exception $e) {
        return '';
    }

    return $time->format('M j, Y g:i A');
}
