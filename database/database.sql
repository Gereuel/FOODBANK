CREATE TABLE USERS (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Middle_Name VARCHAR(50) DEFAULT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Suffix VARCHAR(10) DEFAULT NULL,
    Address TEXT NOT NULL,
    Birthdate DATE NOT NULL
);

CREATE TABLE ACCOUNTS (
    Account_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Account_Type ENUM('PA', 'FA', 'AA') NOT NULL,
    Custom_App_ID VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Phone_Number VARCHAR(20) NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Date_Created DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Relationship
    CONSTRAINT fk_user_account
        FOREIGN KEY (User_ID) 
        REFERENCES USERS(User_ID) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);
    
ALTER TABLE USERS
ADD Profile_Picture MEDIUMBLOB DEFAULT NULL;

CREATE TABLE FOOD_BANKS (
    FoodBank_ID INT AUTO_INCREMENT PRIMARY KEY,
    Account_ID INT NOT NULL, -- Links to the Manager's login account (FA)
    
    Organization_Name VARCHAR(100) NOT NULL,
    Physical_Address TEXT NOT NULL,
    
    -- Public Contact Info (Often different from the manager's personal login email)
    Public_Email VARCHAR(100) DEFAULT NULL, 
    Public_Phone VARCHAR(20) DEFAULT NULL,
    
    -- Operating Hours
    Time_Open TIME NOT NULL,  -- e.g., '08:00:00'
    Time_Close TIME NOT NULL, -- e.g., '17:00:00'
    Operating_Days VARCHAR(50) DEFAULT 'Mon-Fri', -- e.g., 'Monday to Friday'
    
    -- Legal & Admin Verification
    Legal_Documents_URL VARCHAR(255) NOT NULL, -- Path to uploaded PDF/ZIP of permits
    Verification_Status ENUM('Pending', 'Approved', 'Suspended') DEFAULT 'Approved',
    Date_Registered DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key Relationship
    CONSTRAINT fk_foodbank_account
        FOREIGN KEY (Account_ID) 
        REFERENCES ACCOUNTS(Account_ID) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);

-- Account status (enable/disable)
ALTER TABLE ACCOUNTS
ADD COLUMN Status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active';

-- Two-Factor Authentication
ALTER TABLE ACCOUNTS
ADD COLUMN Two_FA_Enabled TINYINT(1) NOT NULL DEFAULT 0;

-- Password Reset Token
ALTER TABLE ACCOUNTS
ADD COLUMN Reset_Token VARCHAR(64) DEFAULT NULL;

ALTER TABLE ACCOUNTS
ADD COLUMN Reset_Token_Expiry DATETIME DEFAULT NULL;

CREATE TABLE DONATIONS (
    Donation_ID INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Who donated
    Donor_Account_ID INT NOT NULL,
    
    -- What was donated
    Item_Type ENUM('Food Items', 'Clothing', 'Cash Donation', 'Medicine', 'Other') NOT NULL,
    Item_Description TEXT DEFAULT NULL,
    Quantity VARCHAR(100) NOT NULL, -- e.g., '100k Items', '$1 Million', '2.3K Items'
    
    -- Where it's going
    FoodBank_ID INT NOT NULL,
    
    -- Pickup/Drop-off location
    Pickup_Address TEXT NOT NULL,
    
    -- Tracking
    Status ENUM('Pending', 'In Transit', 'Received', 'Cancelled') NOT NULL DEFAULT 'Pending',
    Date_Donated DATETIME DEFAULT CURRENT_TIMESTAMP,
    Date_Updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Notes TEXT DEFAULT NULL,

    CONSTRAINT fk_donation_donor
        FOREIGN KEY (Donor_Account_ID)
        REFERENCES ACCOUNTS(Account_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_donation_foodbank
        FOREIGN KEY (FoodBank_ID)
        REFERENCES FOOD_BANKS(FoodBank_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Add missing fields to DONATIONS table
ALTER TABLE DONATIONS
ADD COLUMN Tracking_Number VARCHAR(50) NOT NULL UNIQUE AFTER Donation_ID,
ADD COLUMN Item_Description VARCHAR(255) DEFAULT NULL,
ADD COLUMN Donation_Time TIME DEFAULT NULL,
ADD COLUMN Proof_Of_Delivery_URL VARCHAR(255) DEFAULT NULL,
ADD COLUMN Generated_On DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Update Item_Type ENUM to match Figma
ALTER TABLE DONATIONS
MODIFY COLUMN Item_Type ENUM('Food Items', 'Clothing', 'Cash Donation', 'Medicine', 'Perishable Goods', 'Other') NOT NULL;

-- Drop old quantity column and replace with cleaner structure
ALTER TABLE DONATIONS
CHANGE COLUMN Quantity Quantity_Description VARCHAR(100) NOT NULL;

-- Add org login credentials to FOOD_BANKS
ALTER TABLE FOOD_BANKS
ADD COLUMN Org_Email VARCHAR(100) DEFAULT NULL UNIQUE,
ADD COLUMN Org_Password_Hash VARCHAR(255) DEFAULT NULL,
ADD COLUMN Org_Status ENUM('Active', 'Suspended', 'Pending') NOT NULL DEFAULT 'Pending',
ADD COLUMN Org_Reset_Token VARCHAR(64) DEFAULT NULL,
ADD COLUMN Org_Reset_Token_Expiry DATETIME DEFAULT NULL;

-- Add a custom ID for food banks (like FB-2026-FA0001)
ALTER TABLE FOOD_BANKS
ADD COLUMN Custom_FoodBank_ID VARCHAR(50) DEFAULT NULL UNIQUE;

ALTER TABLE FOOD_BANKS
ADD COLUMN Manager_First_Name VARCHAR(50) DEFAULT NULL,
ADD COLUMN Manager_Last_Name VARCHAR(50) DEFAULT NULL,
ADD COLUMN Manager_Email VARCHAR(100) DEFAULT NULL,
ADD COLUMN Manager_Phone VARCHAR(20) DEFAULT NULL,
ADD COLUMN Manager_Address TEXT DEFAULT NULL;

ALTER TABLE ACCOUNTS
DROP COLUMN Two_FA_Enabled;

ALTER TABLE ACCOUNTS
ADD COLUMN OTP_Code VARCHAR(6) DEFAULT NULL,
ADD COLUMN OTP_Expiry DATETIME DEFAULT NULL,
ADD COLUMN OTP_Method ENUM('email', 'sms') DEFAULT NULL;