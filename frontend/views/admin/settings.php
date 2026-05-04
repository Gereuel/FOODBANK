<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access.");
}

try {
    // Fetch current admin data
    $stmt = $pdo->prepare("
        SELECT u.*, a.Email, a.Phone_Number, a.Custom_App_ID 
        FROM USERS u 
        JOIN ACCOUNTS a ON u.User_ID = a.User_ID 
        WHERE a.Account_ID = ? LIMIT 1
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $user = $stmt->fetch();

    // Fetch Notifications for the integrated section
    $stmt_notif = $pdo->prepare("SELECT * FROM NOTIFICATIONS WHERE Account_ID = ? ORDER BY Created_At DESC LIMIT 10");
    $stmt_notif->execute([$_SESSION['Account_ID']]);
    $notifications = $stmt_notif->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: var(--spacing-2xl);
        align-items: start;
    }
    .profile-card-sticky {
        position: sticky;
        top: var(--spacing-xl);
    }
    .avatar-upload-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto var(--spacing-xl);
    }
    .avatar-preview {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--white);
        box-shadow: var(--shadow-md);
    }
    .avatar-edit-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 36px;
        height: 36px;
        background: var(--green-main);
        color: var(--white);
        border-radius: 50%;
        display: grid;
        place-items: center;
        cursor: pointer;
        border: 3px solid var(--white);
        transition: transform 0.2s;
    }
    .avatar-edit-btn:hover { transform: scale(1.1); background: var(--green-dark); }
    .settings-section-card { margin-bottom: var(--spacing-xl); }
    .settings-form-group { margin-bottom: var(--spacing-lg); }
    
    .notif-settings-list { display: flex; flex-direction: column; }
    .notif-settings-item { padding: var(--spacing-md) 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .notif-settings-item:last-child { border-bottom: none; }
    .notif-settings-content { flex: 1; }
    .notif-unread-dot { width: 8px; height: 8px; background: var(--green-main); border-radius: 50%; margin-right: 10px; display: inline-block; }
    @media (max-width: 992px) { .settings-grid { grid-template-columns: 1fr; } }
</style>

<section class="content-area">
    <header class="page-header">
        <h2>Settings</h2>
        <p>Manage your profile, notifications, security, and account preferences.</p>
    </header>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            <?= $_GET['status'] === 'profile_updated' ? 'Profile updated successfully.' : 'Password changed successfully.' ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Left: Profile Summary -->
        <aside class="profile-card-sticky">
            <div class="table-card" style="padding: var(--spacing-2xl); text-align: center;">
                <form id="avatarForm" action="/foodbank/backend/controllers/admin/settings/update_avatar.php" method="POST" enctype="multipart/form-data">
                    <div class="avatar-upload-container">
                        <img src="<?= !empty($user['Profile_Picture_URL']) ? $user['Profile_Picture_URL'] : '/foodbank/frontend/assets/images/default-avatar.png' ?>" 
                             alt="Avatar" class="avatar-preview" id="settings-avatar-img">
                        <label for="avatar-input" class="avatar-edit-btn">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatar-input" name="profile_picture" hidden accept="image/*" onchange="previewAndUploadAvatar(this)">
                    </div>
                </form>
                <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?></h3>
                <p style="color: var(--text-sub); font-size: var(--font-size-sm); margin-bottom: var(--spacing-lg);">Administrator</p>
                <div class="badge badge-active"><?= $user['Custom_App_ID'] ?></div>
            </div>
        </aside>

        <!-- Right: Forms -->
        <div class="settings-main">
            <!-- Personal Info -->
            <div class="table-card settings-section-card">
                <div class="table-toolbar" style="border:none;">
                    <span class="rpt-table-title">Personal Information</span>
                </div>
                <div style="padding: 0 var(--spacing-2xl) var(--spacing-2xl);">
                    <form action="/foodbank/backend/controllers/admin/settings/update_profile.php" method="POST" class="modal-body" style="padding:0;">
                        <div class="form-row">
                            <div class="settings-form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($user['First_Name']) ?>" required>
                            </div>
                            <div class="settings-form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($user['Last_Name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="settings-form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" value="<?= htmlspecialchars($user['Middle_Name'] ?? '') ?>">
                            </div>
                            <div class="settings-form-group">
                                <label>Suffix</label>
                                <input type="text" name="suffix" value="<?= htmlspecialchars($user['Suffix'] ?? '') ?>" placeholder="e.g. Jr.">
                            </div>
                        </div>
                        <div class="settings-form-group">
                            <label>Address</label>
                            <textarea name="address" required><?= htmlspecialchars($user['Address']) ?></textarea>
                        </div>
                        <div class="settings-form-group">
                            <label>Birthdate</label>
                            <input type="date" name="birthdate" value="<?= $user['Birthdate'] ?>" required>
                        </div>
                        <div class="modal-footer" style="padding:0; border:none;">
                            <button type="submit" style="max-width: 200px;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security -->
            <div class="table-card settings-section-card">
                <div class="table-toolbar" style="border:none;">
                    <span class="rpt-table-title">Security & Password</span>
                </div>
                <div style="padding: 0 var(--spacing-2xl) var(--spacing-2xl);">
                    <form action="/foodbank/backend/controllers/admin/settings/change_password.php" method="POST" class="modal-body" style="padding:0;">
                        <div class="settings-form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-row">
                            <div class="settings-form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required minlength="8">
                            </div>
                            <div class="settings-form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                        </div>
                    <div class="modal-footer" style="padding:0; border:none;">
                        <button type="submit" class="btn-add" style="background: var(--text-main); color: white; border: none; max-width: 200px; justify-content: center;">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notification History Integrated Section -->
            <div class="table-card settings-section-card">
                <div class="table-toolbar" style="border:none;">
                    <span class="rpt-table-title">Recent Notifications & Activity</span>
                </div>
                <div style="padding: 0 var(--spacing-2xl) var(--spacing-2xl);">
                    <div class="notif-settings-list">
                        <?php if (empty($notifications)): ?>
                            <p style="color: var(--text-sub); padding: var(--spacing-md) 0;">No recent notifications.</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notif-settings-item">
                                    <div class="notif-settings-content">
                                        <?php if (!$notif['Is_Read']): ?><span class="notif-unread-dot"></span><?php endif; ?>
                                        <span style="font-weight: <?= !$notif['Is_Read'] ? '600' : '400' ?>;">
                                            <?= htmlspecialchars($notif['Message']) ?>
                                        </span>
                                        <div style="font-size: 12px; color: var(--text-sub); margin-top: 4px;">
                                            <?= date('M j, Y — g:i A', strtotime($notif['Created_At'])) ?>
                                        </div>
                                    </div>
                                    <?php if ($notif['Link']): ?>
                                        <button class="action-btn" onclick="loadComponent('main-display', '<?= $notif['Link'] ?>')" title="View Detail">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <div style="margin-top: var(--spacing-lg); text-align: center;">
                                <button class="toolbar-btn" style="width: 100%; justify-content: center; background: var(--bg-secondary);" 
                                        onclick="loadComponent('main-display', '/foodbank/frontend/views/admin/notifications.php')">
                                    View All History
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
window.initSettings = function() {
    window.previewAndUploadAvatar = function(input) {
        if (input.files && input.files[0]) {
            // 1. Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                // Update settings page preview
                const settingsPreview = document.getElementById('settings-avatar-img');
                if (settingsPreview) settingsPreview.src = e.target.result;
                
                // Update topbar preview instantly
                const topbarAvatar = document.getElementById('db-user-avatar');
                if (topbarAvatar) topbarAvatar.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
            
            // 2. Auto-submit form
            document.getElementById('avatarForm').submit();
        }
    }
};
</script>