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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS SUPPORT_TICKET_REPLIES (
    Reply_ID INT AUTO_INCREMENT PRIMARY KEY,
    Ticket_ID INT NOT NULL,
    Sender_Account_ID INT NOT NULL,
    Body TEXT NOT NULL,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_support_replies_ticket (Ticket_ID, Created_At),
    INDEX idx_support_replies_sender (Sender_Account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
