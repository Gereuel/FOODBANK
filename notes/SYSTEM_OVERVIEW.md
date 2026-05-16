# Food Bank System Overview

## System Purpose

The Food Bank system is a PHP and MySQL web application for coordinating donations between donors, food bank organizations, and administrators. It supports public registration, role-based dashboards, donation record tracking, food bank management, messaging, notifications, and reporting.

## Technology Stack

- Backend: PHP with PDO
- Database: MySQL
- Frontend: HTML, CSS, JavaScript
- Local runtime: XAMPP
- Charts: Chart.js on admin dashboard and reports
- Maps: MapLibre GL on donor food bank map sections

## Account Roles

The system uses `ACCOUNTS.Account_Type` to separate user access:

- `AA`: Admin account
- `PA`: Personal donor account
- `FA`: Food bank organization account

Each account has a generated custom ID, unique email address, phone number, password hash, status, and creation date.

## Public Pages

- Landing page with food bank system information and public statistics.
- Login page for all account roles.
- Signup page for donor, food bank, and admin account creation.
- Forgot password and reset password pages.
- OTP pages for account verification flows.

## Authentication and Security Features

- Role-based access checks on admin, donor, and food bank pages.
- Passwords are stored using PHP password hashing.
- Duplicate email protection through a database `UNIQUE` constraint on `ACCOUNTS.Email`.
- Duplicate signup emails redirect back to the signup page with a user-friendly notice.
- Signup requires users to be at least 18 years old based on birthdate validation.
- Invalid account types are rejected during signup.
- Reset token and OTP fields exist in the account schema.
- Sessions are used to control access to role dashboards.
- Admin-only pages stop unauthorized users from accessing protected content.

## Admin Features

### Dashboard

The admin dashboard shows system activity at a glance:

- Total donations.
- Active food banks.
- Food banks pending approval.
- Total system users.
- New users in the last 7 days.
- Donation trend chart for recent activity.
- Donation distribution chart by item type.
- Recent donation report table.

### User Management

Admins can manage registered users and accounts:

- View all accounts across donors, food banks, and admins.
- See total users with admin-account count.
- See total donors.
- See total food bank accounts.
- See pending account deletion requests.
- Search users by name, email, phone, ID, or address.
- Filter users by role and status.
- Add users.
- Edit donor and admin accounts.
- Delete eligible users.
- View user details.
- Toggle account status through backend user controllers.
- Handle account deletion requests.

### Food Bank Management

Admins can manage food bank organization records:

- Add food bank accounts.
- Edit food bank organization information.
- Delete food bank records.
- View food bank details.
- Manage food bank managers.
- Track organization status and verification status.
- Store public contact details, address, legal document URL, operating hours, and manager information.

### Donation Management

Admins can manage donation reports:

- Add donation reports.
- Edit donation reports.
- Delete donation reports.
- View donation records with donor and food bank information.
- Track donation status: `Pending`, `In Transit`, `Received`, and `Cancelled`.
- Filter donations by status and item type.
- Search donation records by tracking number, item details, donor details, food bank, and status.
- Upload or display proof of delivery where available.
- Paginate donation records.

### Reports

The reports page provides analytics over selectable date ranges:

- Last 7 days.
- Last 30 days.
- Last 3 months.
- Last year.

Reports include:

- Total donations.
- Unique donors.
- Active food banks receiving donations.
- Donation trend chart.
- Donations by item type chart.
- Top donors.
- User activity metrics.
- Registration trends.
- Food bank donation activity.
- CSV and PDF export controls.

### Notifications

The admin shell includes notification support:

- Notifications are stored per account.
- Notifications have type, message, link, read status, and creation time.
- Admins are notified when public signup creates a new account.
- Notification API supports fetching and marking notifications as read.

### Settings

Admin settings support profile-related updates, including avatar/profile update controllers.

## Donor Features

Donor accounts use the Personal Account (`PA`) dashboard.

### Donor Dashboard

The donor dashboard provides:

- A sidebar and header layout.
- Active food bank count.
- Active donor count.
- Donation count.
- Featured or nearby approved food banks.
- Open/closed status calculation from food bank operating days and hours.

### Food Bank Discovery

Donors can browse food banks:

- View approved and active food banks.
- See organization name, address, phone, operating days, and hours.
- Favorite food banks using `PA_FOOD_BANK_FAVORITES`.
- Access all food bank listings and map-based sections.

### Donation History

Donors can view their donation records:

- Donation item type.
- Quantity description.
- Assigned food bank.
- Donation date and time.
- Status.
- Tracking number.
- Proof of delivery.
- Additional notes.
- Donation report modal with donor, donation, food bank, and proof details.

### Donor Messaging

Donors can message other relevant accounts:

- Search contacts.
- View conversation list.
- Open chat panel.
- Send messages.
- View contact profile details in chat.

### Donor Settings

Donors can manage their account:

- Update profile information.
- Change password.
- Update avatar.
- Deactivate account.
- Request account deletion.

## Food Bank Account Features

Food bank accounts use the Food Bank Account (`FA`) dashboard.

### Food Bank Dashboard

Food bank users can view:

- Received donations.
- Pending or in-transit donations.
- Unique donor count.
- Unread message count.
- Food bank profile details.
- Recent donation records.
- Verification status.

### Donation Records

Food bank users can view donations assigned to their organization:

- Tracking number.
- Donor name and email.
- Item type.
- Quantity.
- Status.
- Donation date.

The food bank donation list currently focuses on received donation records.

### Donor List

Food bank users can view donor-related records for their organization.

### Food Bank Messaging

Food bank accounts share the messaging system:

- Conversations are stored in `MESSAGES`.
- Messages track sender, receiver, body, read status, and creation time.
- Message APIs support conversations, message retrieval, contact search, and sending messages.

### Food Bank Account Settings

Food bank users can manage:

- Organization name.
- Public email.
- Public phone.
- Operating days.
- Opening and closing time.
- Manager first and last name.
- Login email.
- Manager phone.
- Food bank address.
- Manager address.
- Profile avatar.
- Password.

## Main Database Tables

- `USERS`: Personal user profile data.
- `ACCOUNTS`: Login accounts, roles, credentials, status, OTP, reset tokens, and custom IDs.
- `FOOD_BANKS`: Food bank organization profile, public contact details, verification status, operating hours, and manager details.
- `DONATIONS`: Donation reports, donor account, food bank, item details, pickup details, proof of delivery, status, and tracking number.
- `NOTIFICATIONS`: Per-account notifications.
- `MESSAGES`: Direct messages between accounts.
- `ACCOUNT_DELETION_REQUESTS`: Admin-reviewed deletion requests.
- `PA_FOOD_BANK_FAVORITES`: Donor favorite food banks.

## Important Backend Areas

- `backend/controllers/auth/`: Login, signup, OTP, password reset, and logout.
- `backend/controllers/admin/users/`: Admin user actions.
- `backend/controllers/admin/foodbanks/`: Admin food bank and manager actions.
- `backend/controllers/admin/donations/`: Admin donation report actions.
- `backend/controllers/individual/`: Donor profile, settings, favorites, and account actions.
- `backend/controllers/foodbank/`: Food bank account settings and profile actions.
- `backend/api/messages/`: Messaging API endpoints.
- `backend/api/notifications/`: Notification API endpoints.

## Important Frontend Areas

- `signup.php`: Public account registration.
- `login.php`: Public login page.
- `frontend/views/admin/admin_index.php`: Admin shell.
- `frontend/views/admin/user_management.php`: Admin users overview.
- `frontend/views/admin/donations.php`: Admin donation management.
- `frontend/views/admin/foodbanks.php`: Admin food bank management.
- `frontend/views/admin/reports.php`: Admin analytics and reports.
- `frontend/views/individual/pa_index.php`: Donor dashboard shell.
- `frontend/views/individual/pa_foodbanks.php`: Donor food bank discovery.
- `frontend/views/individual/pa_donations.php`: Donor donation history.
- `frontend/views/individual/pa_messages.php`: Donor messaging UI.
- `frontend/views/foodbank/index.php`: Food bank dashboard shell.
- `frontend/views/foodbank/fb_home.php`: Food bank dashboard overview.
- `frontend/views/foodbank/fb_donations.php`: Food bank donation records.
- `frontend/views/foodbank/fb_account.php`: Food bank account settings.

## Recent Implemented Fixes

- Duplicate signup emails now show a notice on the signup page instead of exposing a raw SQL error.
- Signup birthdate now validates that the user is at least 18 years old.
- Admin Total Donors count now counts only `PA` donor accounts.
- Admin Total Users card now shows the total user count plus the number of admin accounts.
- Admin donation pagination and filters were adjusted to work correctly inside the admin shell.
- Notification dropdown logic was guarded against missing optional DOM nodes.

## Notes for Future Development

- Keep role checks on every protected page and controller.
- Keep user-facing errors generic; log technical database errors server-side.
- For paginated admin pages loaded inside the admin shell, reload components through the shell JavaScript rather than plain browser navigation.
- Apply filters in SQL before pagination so filtered results include all matching records.
- Use `C:\xampp\php\php.exe -l` for PHP syntax validation in this XAMPP environment when `php` is not available on PATH.
