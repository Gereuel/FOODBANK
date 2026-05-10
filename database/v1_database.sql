CREATE TABLE USERS (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Middle_Name VARCHAR(50),
    Last_Name VARCHAR(50) NOT NULL,
    Suffix VARCHAR(10),
    Address TEXT NOT NULL,
    Birthdate DATE NOT NULL,
    Profile_Picture MEDIUMBLOB,
    Profile_Picture_URL VARCHAR(255)
);

CREATE TABLE ACCOUNTS (
    Account_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Account_Type ENUM('PA','FA','AA') NOT NULL,
    Custom_App_ID VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Phone_Number VARCHAR(20) NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Status ENUM('Active','Inactive') DEFAULT 'Active',
    OTP_Code VARCHAR(6),
    OTP_Expiry DATETIME,
    OTP_Method ENUM('email','sms'),
    Reset_Token VARCHAR(64),
    Reset_Token_Expiry DATETIME,
    Date_Created DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (User_ID)
        REFERENCES USERS(User_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE FOOD_BANKS (
    FoodBank_ID INT AUTO_INCREMENT PRIMARY KEY,
    Account_ID INT NOT NULL,
    Custom_FoodBank_ID VARCHAR(50) UNIQUE,

    Organization_Name VARCHAR(100) NOT NULL,
    Physical_Address TEXT NOT NULL,
    Public_Email VARCHAR(100),
    Public_Phone VARCHAR(20),

    Time_Open TIME NOT NULL,
    Time_Close TIME NOT NULL,
    Operating_Days VARCHAR(50) DEFAULT 'Mon-Fri',

    Legal_Documents_URL VARCHAR(255) NOT NULL,
    Verification_Status ENUM('Pending','Approved','Suspended') DEFAULT 'Approved',
    Date_Registered DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Org login
    Org_Email VARCHAR(100) UNIQUE,
    Org_Password_Hash VARCHAR(255),
    Org_Status ENUM('Active','Suspended','Pending') DEFAULT 'Pending',
    Org_Reset_Token VARCHAR(64),
    Org_Reset_Token_Expiry DATETIME,

    -- Manager info
    Manager_First_Name VARCHAR(50),
    Manager_Last_Name VARCHAR(50),
    Manager_Email VARCHAR(100),
    Manager_Phone VARCHAR(20),
    Manager_Address TEXT,

    FOREIGN KEY (Account_ID)
        REFERENCES ACCOUNTS(Account_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE DONATIONS (
    Donation_ID INT AUTO_INCREMENT PRIMARY KEY,
    Tracking_Number VARCHAR(50) NOT NULL UNIQUE,

    Donor_Account_ID INT NOT NULL,
    FoodBank_ID INT NOT NULL,

    Item_Type ENUM('Food Items','Clothing','Cash Donation','Medicine','Perishable Goods','Other') NOT NULL,
    Item_Description VARCHAR(255),
    Quantity_Description VARCHAR(100) NOT NULL,

    Pickup_Address TEXT NOT NULL,
    Donation_Time TIME,
    Proof_Of_Delivery_URL VARCHAR(255),

    Status ENUM('Pending','In Transit','Received','Cancelled') DEFAULT 'Pending',
    Notes TEXT,

    Date_Donated DATETIME DEFAULT CURRENT_TIMESTAMP,
    Date_Updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Generated_On DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (Donor_Account_ID)
        REFERENCES ACCOUNTS(Account_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,

    FOREIGN KEY (FoodBank_ID)
        REFERENCES FOOD_BANKS(FoodBank_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE NOTIFICATIONS (
    Notification_ID INT AUTO_INCREMENT PRIMARY KEY,
    Account_ID INT NOT NULL,
    Type VARCHAR(50),
    Message TEXT NOT NULL,
    Link VARCHAR(255),
    Is_Read TINYINT(1) DEFAULT 0,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (Account_ID)
        REFERENCES ACCOUNTS(Account_ID)
        ON DELETE CASCADE
);

CREATE TABLE MESSAGES (
    Message_ID INT AUTO_INCREMENT PRIMARY KEY,
    Sender_Account_ID INT NOT NULL,
    Receiver_Account_ID INT NOT NULL,
    Body TEXT NOT NULL,
    Is_Read TINYINT(1) NOT NULL DEFAULT 0,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_messages_sender_receiver (Sender_Account_ID, Receiver_Account_ID),
    INDEX idx_messages_receiver_sender (Receiver_Account_ID, Sender_Account_ID),

    FOREIGN KEY (Sender_Account_ID)
        REFERENCES ACCOUNTS(Account_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,

    FOREIGN KEY (Receiver_Account_ID)
        REFERENCES ACCOUNTS(Account_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);
