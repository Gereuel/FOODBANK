# FoodBank Project Directory

This file documents the current structure of the FoodBank PHP/XAMPP project.

## Root

```text
foodbank/
|-- backend/
|-- database/
|-- frontend/
|-- notes/
|-- uploads/
|-- .htaccess
|-- favicon.ico
|-- index.php
|-- login.php
|-- signup.php
`-- SYSTEM_OVERVIEW.md
```

## Root Files

- `index.php`: Public landing page.
- `login.php`: Unified login page for admin, donor, and food bank accounts.
- `signup.php`: Public signup page with duplicate-email notice and 18+ birthdate validation.
- `.htaccess`: Apache routing/configuration file.
- `favicon.ico`: Browser favicon.
- `SYSTEM_OVERVIEW.md`: System feature and module overview.

## Backend

```text
backend/
|-- api/
|   |-- auth/
|   |   `-- session_status.php
|   |-- messages/
|   |   |-- get_conversations.php
|   |   |-- get_messages.php
|   |   |-- search_contacts.php
|   |   `-- send_message.php
|   |-- notifications/
|   |   |-- get_notifications.php
|   |   `-- mark_as_read.php
|   `-- get_landing_stats.php
|-- config/
|   |-- app.php
|   `-- database.php
|-- controllers/
|   |-- admin/
|   |-- auth/
|   |-- foodbank/
|   `-- individual/
`-- helpers/
```

### Backend API

- `backend/api/get_landing_stats.php`: Public landing page statistics.
- `backend/api/auth/session_status.php`: Session status endpoint.
- `backend/api/messages/`: Conversation, message retrieval, contact search, and send-message endpoints.
- `backend/api/notifications/`: Notification fetch and mark-as-read endpoints.

### Backend Config

- `backend/config/app.php`: Application configuration and URL helpers.
- `backend/config/database.php`: PDO database connection.

### Auth Controllers

```text
backend/controllers/auth/
|-- logout.php
|-- password_reset_token.php
|-- process_forgot_password.php
|-- process_login.php
|-- process_resend_otp.php
|-- process_reset_password.php
|-- process_send_otp.php
|-- process_signup.php
`-- process_verify_otp.php
```

Handles login, signup, logout, OTP, and password reset flows.

### Admin Controllers

```text
backend/controllers/admin/
|-- donations/
|   |-- process_add_donations.php
|   |-- process_delete_donation.php
|   `-- process_edit_donation.php
|-- foodbanks/
|   |-- process_add_foodbank.php
|   |-- process_delete_foodbank.php
|   |-- process_delete_manager.php
|   |-- process_edit_foodbank.php
|   `-- process_edit_manager.php
|-- settings/
|   |-- update_avatar.php
|   `-- update_profile.php
`-- users/
    |-- process_add_user.php
    |-- process_delete_user.php
    |-- process_edit_user.php
    |-- process_reject_deletion_request.php
    |-- process_toggle_2fa.php
    `-- process_toggle_status.php
```

Handles admin CRUD and account management actions.

### Donor Controllers

```text
backend/controllers/individual/
|-- foodbanks/
|   `-- toggle_favorite.php
`-- settings/
    |-- change_password.php
    |-- deactivate_account.php
    |-- request_account_deletion.php
    |-- update_avatar.php
    `-- update_profile.php
```

Handles donor settings, favorites, deactivation, and deletion requests.

### Food Bank Controllers

```text
backend/controllers/foodbank/
`-- settings/
    |-- change_password.php
    |-- update_avatar.php
    `-- update_profile.php
```

Handles food bank account settings, profile updates, avatar uploads, and password changes.

### Backend Helpers

```text
backend/helpers/
|-- auth_redirect.php
|-- messages_contacts.php
|-- messages_schema.php
`-- schema_columns.php
```

Shared helper files for authentication redirects, messaging, and schema compatibility checks.

## Frontend

```text
frontend/
|-- assets/
|-- components/
`-- views/
```

## Frontend Assets

```text
frontend/assets/
|-- css/
|   |-- components/
|   |-- global/
|   `-- pages/
|-- images/
`-- js/
```

### CSS

- `frontend/assets/css/global/`: Shared variables, resets, layout, and typography.
- `frontend/assets/css/components/admin/`: Admin sidebar and topbar styles.
- `frontend/assets/css/components/foodbank/`: Food bank navigation and header styles.
- `frontend/assets/css/components/individual/`: Donor navigation and header styles.
- `frontend/assets/css/pages/admin/`: Admin dashboard, reports, animation, and user management styles.
- `frontend/assets/css/pages/foodbank/`: Food bank dashboard styles.
- `frontend/assets/css/pages/individual/`: Donor donation, donor, food bank, messaging, and settings styles.
- `frontend/assets/css/pages/`: Public page styles for landing, login, signup, verification, and public information pages.

### Images

```text
frontend/assets/images/
|-- default-avatar.png
|-- header-banner.png
|-- index_background.png
|-- leaf-bottom.png
|-- leaf-top.png
|-- logo.png
`-- Nature.png
```

### JavaScript

```text
frontend/assets/js/
|-- app.js
|-- fb-app.js
|-- public-auth.js
|-- users-overview.js
|-- Individual/
|   `-- pa-app.js
`-- modals/
    |-- add-user-modal.js
    |-- delete-user-modal.js
    |-- donation-modals.js
    |-- edit-user-modal.js
    |-- foodbank-modals.js
    |-- manager-modals.js
    |-- security-user-modal.js
    |-- toolbar.js
    `-- view-user-modal.js
```

- `app.js`: Admin shell/component loader and shared admin behaviors.
- `fb-app.js`: Food bank dashboard component loader.
- `Individual/pa-app.js`: Donor dashboard component loader.
- `modals/`: Modal handlers for admin users, food banks, donations, managers, and toolbar filtering/export controls.

## Frontend Components

```text
frontend/components/
|-- bottom_strip.html
|-- admin/
|   |-- admin_sideBar.php
|   `-- admin_topBar.php
|-- foodbank/
|   |-- fb_header.php
|   `-- fb_navigation.php
`-- individual/
    |-- indi_header.php
    `-- indi_navigation.php
```

Reusable UI pieces grouped by role.

## Frontend Views

```text
frontend/views/
|-- admin/
|-- auth/
|-- foodbank/
|-- individual/
`-- public/
```

### Admin Views

```text
frontend/views/admin/
|-- admin_index.php
|-- dashboard_home.php
|-- donations.php
|-- foodbank-managers.php
|-- foodbanks.php
|-- notifications.php
|-- password-security.php
|-- reports.php
|-- settings.php
|-- user_management.php
`-- modals/
```

- `admin_index.php`: Admin shell page.
- `dashboard_home.php`: Admin KPI cards, charts, and recent donations.
- `user_management.php`: Users overview, account stats, filters, and user actions.
- `donations.php`: Donation report listing, filters, pagination, add/edit/delete/view flows.
- `foodbanks.php`: Food bank organization management.
- `foodbank-managers.php`: Food bank manager management.
- `reports.php`: Analytics and export controls.
- `notifications.php`: Notification view.
- `password-security.php`: Password and security management.
- `settings.php`: Admin settings.

### Admin Modals

```text
frontend/views/admin/modals/
|-- add-donation-modal.php
|-- add-foodbank-modal.php
|-- add-user-modal.php
|-- delete-donation-modal.php
|-- delete-foodbank-modal.php
|-- delete-manager-modal.php
|-- delete-user-modal.php
|-- donation-report-modal.php
|-- edit-donation-modal.php
|-- edit-foodbank-modal.php
|-- edit-manager-modal.php
|-- edit-user-modal.php
|-- security-user-modal.php
|-- view-foodbank-modal.php
|-- view-manager-modal.php
`-- view-user-modal.php
```

### Auth Views

```text
frontend/views/auth/
|-- forgot-password.php
|-- otp.php
|-- reset-password.php
`-- verification.php
```

Auth-related pages used by password reset and verification flows.

### Donor Views

```text
frontend/views/individual/
|-- pa_all_foodbanks.php
|-- pa_donations.php
|-- pa_donors.php
|-- pa_foodbanks.php
|-- pa_index.php
|-- pa_messages.php
|-- pa_settings.php
`-- sections/
    `-- fb_map.php
```

- `pa_index.php`: Donor shell page.
- `pa_foodbanks.php`: Food bank discovery and favorite food banks.
- `pa_all_foodbanks.php`: Expanded food bank list.
- `pa_donations.php`: Donor donation history and report modal.
- `pa_messages.php`: Donor messaging UI.
- `pa_settings.php`: Donor profile and account settings.
- `sections/fb_map.php`: Map-based food bank section.

### Food Bank Views

```text
frontend/views/foodbank/
|-- fb_account.php
|-- fb_donations.php
|-- fb_donors.php
|-- fb_home.php
|-- fb_messages.php
`-- index.php
```

- `index.php`: Food bank shell page.
- `fb_home.php`: Food bank dashboard overview.
- `fb_donations.php`: Donation records assigned to the food bank.
- `fb_donors.php`: Donor-related food bank view.
- `fb_messages.php`: Food bank messaging UI.
- `fb_account.php`: Food bank profile, manager, avatar, and password settings.

### Public Views

```text
frontend/views/public/
|-- how-to-donate.html
|-- privacy-security.html
`-- terms-agreement.html
```

Static public information pages.

## Database

```text
database/
|-- database.sql
`-- v1_database.sql
```

- `database.sql`: Database schema/backup.
- `v1_database.sql`: Versioned schema containing core tables such as `USERS`, `ACCOUNTS`, `FOOD_BANKS`, `DONATIONS`, `NOTIFICATIONS`, and `MESSAGES`.

## Notes

```text
notes/
|-- DIRECTORY.md
`-- SYSTEM_OVERVIEW.md
```

- `DIRECTORY.md`: This current directory map.
- `SYSTEM_OVERVIEW.md`: Feature overview copy kept under notes.

## Uploads

```text
uploads/
```

Stores uploaded files such as avatars, proofs, or documents depending on the active upload controllers.

## Important Path Examples

- Public signup posts to `backend/controllers/auth/process_signup.php`.
- Public login posts to `backend/controllers/auth/process_login.php`.
- Admin shell loads components into `frontend/views/admin/admin_index.php`.
- Admin donations page is `frontend/views/admin/donations.php`.
- Admin donation add controller is `backend/controllers/admin/donations/process_add_donations.php`.
- Admin user management page is `frontend/views/admin/user_management.php`.
- Donor shell page is `frontend/views/individual/pa_index.php`.
- Food bank shell page is `frontend/views/foodbank/index.php`.

## Validation Note

In this XAMPP setup, PHP may not be available as `php` on PATH. Use:

```powershell
C:\xampp\php\php.exe -l path\to\file.php
```
