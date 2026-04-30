pdo = PHP Data Objects

        // Auto-Increment for account created per year
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ACCOUNTS WHERE Account_Type = ? AND YEAR(Date_Created) = ?");
        $stmtCount->execute([$account_type, $current_year]);
        $sequence = $stmtCount->fetchColumn() + 1;
        
        // Format the ID at exactly 4 digits (sampple: 0004)
        $formatted_sequence = str_pad($sequence, 4, "0", STR_PAD_LEFT);
        $custom_app_id = "FB-" . $current_year . "-" . $account_type . $formatted_sequence;

📁 FOODBANK (Root)
│
├── 📁 frontend/               <-- Everything the user SEES and interacts with
│   ├── 📁 assets/             <-- (Your current assets folder is already perfect)
│   │   ├── 📁 css/
│   │   ├── 📁 images/
│   │   └── 📁 js/             <-- app.js lives here
│   │
│   ├── 📁 components/         <-- Reusable UI pieces for ALL roles
│   │   ├── sidebar.html       <-- Move your admin_sideBar here and make it dynamic
│   │   ├── topbar.html        
│   │   └── footer.html
│   │
│   └── 📁 views/              <-- The actual pages/screens, split by role
│       ├── 📁 admin/          <-- adminDashboard.html, user_management.html
│       ├── 📁 individual/     <-- pa_index.php (Personal Account / Donor pages)
│       └── 📁 foodbank/       <-- Food bank manager dashboards
│
├── 📁 backend/                <-- Everything that processes DATA behind the scenes
│   ├── 📁 config/             
│   │   └── database.php       <-- Your database connection stays here
│   │
│   ├── 📁 controllers/        <-- (Rename your 'logic' folder to this)
│   │   ├── process_login.php  <-- Handles the form submissions
│   │   ├── process_signup.php
│   │   └── logout.php
│   │
│   └── 📁 api/                <-- Where you put PHP files that your JS fetch() calls
│       ├── get_user_profile.php 
│       └── get_dashboard_stats.php
│
├── 📁 database/               
│   └── database.sql           <-- Your database backup/schema
│
├── index.html                 <-- The main landing page
├── login.php                  <-- Unified login page
└── signup.php                 <-- Unified signup page

<?php
// Mock user data - replace with your actual DB query
$users = [
    [
        'id'       => 'USR-2026-0001',
        'name'     => 'Juan dela Cruz',
        'email'    => 'juan@foodbank.org',
        'role'     => 'Admin',
        'location' => 'Guagua, Pampanga',
        'status'   => 'Active',
    ],
    [
        'id'       => 'USR-2026-0002',
        'name'     => 'Maria Santos',
        'email'    => 'maria@foodbank.org',
        'role'     => 'Manager',
        'location' => 'Angeles, Pampanga',
        'status'   => 'Active',
    ],
    [
        'id'       => 'USR-2026-0003',
        'name'     => 'Pedro Reyes',
        'email'    => 'pedro@fb.com',
        'role'     => 'Volunteer',
        'location' => 'San Fernando, Pampanga',
        'status'   => 'Inactive',
    ],
    [
        'id'       => 'USR-2026-0004',
        'name'     => 'Ana Gomez',
        'email'    => 'ana@fb.com',
        'role'     => 'Manager',
        'location' => 'Mabalacat, Pampanga',
        'status'   => 'Active',
    ],
];

$total_users   = 120;
$active_users  = 98;
$total_admins  = 10;
$current_page  = 1;
$per_page      = 4;
$total_pages   = ceil($total_users / $per_page);
?>