<?php

function ensure_messages_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS MESSAGES (
            Message_ID INT AUTO_INCREMENT PRIMARY KEY,
            Sender_Account_ID INT NOT NULL,
            Receiver_Account_ID INT NOT NULL,
            Body TEXT NOT NULL,
            Is_Read TINYINT(1) NOT NULL DEFAULT 0,
            Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_messages_sender_receiver (Sender_Account_ID, Receiver_Account_ID),
            INDEX idx_messages_receiver_sender (Receiver_Account_ID, Sender_Account_ID),
            CONSTRAINT fk_messages_sender
                FOREIGN KEY (Sender_Account_ID)
                REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT fk_messages_receiver
                FOREIGN KEY (Receiver_Account_ID)
                REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
