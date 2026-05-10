<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    exit('Unauthorized');
}

$stmt = $pdo->prepare("
    SELECT u.*, a.Email, a.Phone_Number, a.Custom_App_ID
    FROM USERS u
    JOIN ACCOUNTS a ON a.User_ID = u.User_ID
    WHERE a.Account_ID = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['Account_ID']]);
$user = $stmt->fetch();

$fullName = trim(($user['First_Name'] ?? '') . ' ' . ($user['Last_Name'] ?? ''));
$avatar = !empty($user['Profile_Picture_URL'])
    ? $user['Profile_Picture_URL']
    : '/foodbank/frontend/assets/images/default-avatar.png';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ACCOUNT_LOGIN_ACTIVITY (
            Activity_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            IP_Address VARCHAR(45) DEFAULT NULL,
            User_Agent TEXT DEFAULT NULL,
            Login_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_login_activity_account (Account_ID),
            CONSTRAINT fk_login_activity_account
                FOREIGN KEY (Account_ID) REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        )
    ");

    $stmtActivity = $pdo->prepare("
        SELECT IP_Address, User_Agent, Login_At
        FROM ACCOUNT_LOGIN_ACTIVITY
        WHERE Account_ID = ?
        ORDER BY Login_At DESC
        LIMIT 10
    ");
    $stmtActivity->execute([$_SESSION['Account_ID']]);
    $loginActivities = $stmtActivity->fetchAll();
} catch (PDOException $e) {
    error_log('PA login activity load error: ' . $e->getMessage());
    $loginActivities = [];
}

function paLoginDevice(?string $userAgent): string {
    $agent = strtolower((string) $userAgent);
    if ($agent === '') return 'Unknown device';

    $browser = 'Browser';
    if (str_contains($agent, 'edg/')) $browser = 'Microsoft Edge';
    elseif (str_contains($agent, 'chrome/')) $browser = 'Chrome';
    elseif (str_contains($agent, 'firefox/')) $browser = 'Firefox';
    elseif (str_contains($agent, 'safari/')) $browser = 'Safari';

    $platform = 'Desktop';
    if (str_contains($agent, 'android')) $platform = 'Android';
    elseif (str_contains($agent, 'iphone') || str_contains($agent, 'ipad')) $platform = 'iOS';
    elseif (str_contains($agent, 'windows')) $platform = 'Windows';
    elseif (str_contains($agent, 'mac os')) $platform = 'macOS';

    return $browser . ' on ' . $platform;
}
?>

<section class="pa-settings" data-settings-root>
    <button type="button" class="settings-back-btn" data-open-view="home" hidden>
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
    </button>

    <div class="settings-view is-active" data-view="home">
        <div class="settings-account-panel">
            <h2>My Account</h2>
            <p>Manage your account and preferences</p>

            <div class="settings-profile-summary">
                <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($fullName) ?>" id="settings-summary-avatar">
                <div>
                    <h3><?= htmlspecialchars($fullName) ?></h3>
                    <span><?= htmlspecialchars($user['Email'] ?? '') ?></span>
                </div>
            </div>

            <div class="settings-menu">
                <button type="button" data-open-view="edit">
                    <span>Edit Profile</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" data-open-view="notifications">
                    <span>Notification Setting</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" data-open-view="security">
                    <span>Privacy &amp; Security</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" data-open-view="help">
                    <span>Help &amp; Support</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="settings-view" data-view="edit">
        <div class="settings-edit-grid">
            <div class="settings-card settings-avatar-card">
                <form id="pa-avatar-form" enctype="multipart/form-data">
                    <label class="avatar-picker">
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($fullName) ?>" id="settings-avatar-preview">
                        <span><i class="fas fa-camera"></i></span>
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" hidden>
                    </label>
                    <p>Tap to change profile picture</p>
                </form>
            </div>

            <form class="settings-card settings-profile-form" id="pa-profile-form">
                <h2>Personal Information</h2>

                <label>
                    <span>First Name</span>
                    <i class="far fa-user"></i>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['First_Name'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Middle Name (Optional)</span>
                    <i class="far fa-user"></i>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($user['Middle_Name'] ?? '') ?>">
                </label>
                <label>
                    <span>Last Name</span>
                    <i class="far fa-user"></i>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['Last_Name'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Email</span>
                    <i class="far fa-envelope"></i>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['Email'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Phone</span>
                    <i class="fas fa-phone"></i>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['Phone_Number'] ?? '') ?>">
                </label>
                <label>
                    <span>Address</span>
                    <i class="fas fa-location-dot"></i>
                    <input type="text" name="address" value="<?= htmlspecialchars($user['Address'] ?? '') ?>">
                </label>

                <button type="submit">Save Changes</button>
                <p class="settings-form-status" id="profile-status"></p>
            </form>
        </div>
    </div>

    <div class="settings-view" data-view="notifications">
        <div class="settings-stack">
            <div class="settings-card settings-option-card">
                <div class="option-heading">
                    <span class="option-icon"><i class="far fa-bell"></i></span>
                    <div>
                        <h2>General Notification</h2>
                        <p>Manage how you receive updates</p>
                    </div>
                </div>
                <div class="settings-toggle-row">
                    <div><strong>Email Notification</strong><span>Receive updates via email</span></div>
                    <label class="settings-switch"><input type="checkbox" data-pref="email" checked><span></span></label>
                </div>
                <div class="settings-toggle-row">
                    <div><strong>SMS Notification</strong><span>Receive text messages</span></div>
                    <label class="settings-switch"><input type="checkbox" data-pref="sms"><span></span></label>
                </div>
            </div>

            <div class="settings-card settings-option-card">
                <div class="option-heading">
                    <span class="option-icon option-icon--orange"><i class="far fa-clipboard"></i></span>
                    <div>
                        <h2>Activity Updates</h2>
                        <p>Manage how you receive updates</p>
                    </div>
                </div>
                <div class="settings-toggle-row">
                    <div><strong>Volunteer Updates</strong><span>New volunteer opportunities</span></div>
                    <label class="settings-switch"><input type="checkbox" data-pref="volunteer" checked><span></span></label>
                </div>
                <div class="settings-toggle-row">
                    <div><strong>Food Shortage Updates</strong><span>Receive alerts when food supplies are low</span></div>
                    <label class="settings-switch"><input type="checkbox" data-pref="shortage"><span></span></label>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-view" data-view="security">
        <div class="settings-security-grid">
            <form class="settings-card settings-password-form" id="pa-password-form">
                <div class="option-heading">
                    <span class="option-icon"><i class="fas fa-lock"></i></span>
                    <div>
                        <h2>Password</h2>
                        <p>Update your password</p>
                    </div>
                </div>

                <label><span>Current Password</span><input type="password" name="current_password" placeholder="Enter current password" required></label>
                <label><span>New Password</span><input type="password" name="new_password" placeholder="Enter new password" required></label>
                <label><span>Confirm New Password</span><input type="password" name="confirm_password" placeholder="Confirm new password" required></label>
                <button type="submit">Save Changes</button>
                <p class="settings-form-status" id="password-status"></p>
            </form>

            <div class="settings-security-side">
                <div class="settings-card">
                    <div class="option-heading">
                        <span class="option-icon option-icon--red"><i class="fas fa-shield-heart"></i></span>
                        <div>
                            <h2>Security Options</h2>
                            <p>Manage your account security</p>
                        </div>
                    </div>
                    <button class="settings-link-row" type="button" data-open-view="login_activity"><span>Login Activity</span><i class="fas fa-chevron-right"></i></button>
                </div>

                <div class="settings-card danger-card">
                    <h2>Danger Zone</h2>
                    <button type="button" data-danger-action="deactivate">Deactivate Account</button>
                    <button type="button" data-danger-action="delete">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-view" data-view="login_activity">
        <div class="settings-stack login-activity-stack">
            <div class="settings-card settings-option-card">
                <div class="option-heading">
                    <span class="option-icon option-icon--red"><i class="fas fa-shield-heart"></i></span>
                    <div>
                        <h2>Login Activity</h2>
                        <p>Recent successful sign-ins to your account</p>
                    </div>
                </div>

                <?php if (empty($loginActivities)): ?>
                    <div class="login-activity-empty">
                        <i class="fas fa-clock-rotate-left"></i>
                        <p>No login activity has been recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="login-activity-list">
                        <?php foreach ($loginActivities as $index => $activity): ?>
                            <div class="login-activity-row">
                                <span class="login-activity-icon">
                                    <i class="fas fa-<?= $index === 0 ? 'location-dot' : 'clock-rotate-left' ?>"></i>
                                </span>
                                <div>
                                    <strong><?= htmlspecialchars(paLoginDevice($activity['User_Agent'] ?? '')) ?></strong>
                                    <span><?= htmlspecialchars($activity['IP_Address'] ?: 'Unknown IP') ?></span>
                                </div>
                                <time datetime="<?= htmlspecialchars($activity['Login_At']) ?>">
                                    <?= htmlspecialchars(date('M j, Y g:i A', strtotime($activity['Login_At']))) ?>
                                </time>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="settings-view" data-view="help">
        <div class="settings-help-grid">
            <div class="settings-card">
                <div class="option-heading">
                    <span class="option-icon"><i class="far fa-circle-question"></i></span>
                    <div><h2>Frequently Asked Questions</h2><p>Quick answers to common questions</p></div>
                </div>
                <div class="faq-item">
                    <button class="settings-link-row faq-toggle" type="button" aria-expanded="false">
                        <span>How do I make a donation?</span><i class="fas fa-chevron-right"></i>
                    </button>
                    <p class="faq-answer">Go to Food Banks, choose a food bank, then coordinate your donation details through the available contact or message options.</p>
                </div>
                <div class="faq-item">
                    <button class="settings-link-row faq-toggle" type="button" aria-expanded="false">
                        <span>How do I find nearby food banks?</span><i class="fas fa-chevron-right"></i>
                    </button>
                    <p class="faq-answer">Open the Food Banks section to view approved active food banks and use View All to search by name, address, or phone number.</p>
                </div>
                <div class="faq-item">
                    <button class="settings-link-row faq-toggle" type="button" aria-expanded="false">
                        <span>Can I volunteer at food banks?</span><i class="fas fa-chevron-right"></i>
                    </button>
                    <p class="faq-answer">Yes. Contact the food bank directly from its details page to ask about volunteer schedules and requirements.</p>
                </div>
                <div class="faq-item">
                    <button class="settings-link-row faq-toggle" type="button" aria-expanded="false">
                        <span>How do I track my donations?</span><i class="fas fa-chevron-right"></i>
                    </button>
                    <p class="faq-answer">Open the Donation section to view your donation records, status, dates, and report details.</p>
                </div>
                <div class="faq-item">
                    <button class="settings-link-row faq-toggle" type="button" aria-expanded="false">
                        <span>What items can I donate?</span><i class="fas fa-chevron-right"></i>
                    </button>
                    <p class="faq-answer">Common accepted items include food items, clothing, medicine, relief goods, and cash assistance, depending on the food bank needs.</p>
                </div>
            </div>

            <div class="settings-help-side">
                <div class="settings-card">
                    <div class="option-heading">
                        <span class="option-icon option-icon--red"><i class="far fa-file-lines"></i></span>
                        <div><h2>Resources</h2><p>Learn more about our app</p></div>
                    </div>
                    <a class="settings-link-row" href="/foodbank/frontend/views/public/how-to-donate.html"><span>User Guide</span><i class="fas fa-chevron-right"></i></a>
                    <a class="settings-link-row" href="/foodbank/frontend/views/public/terms-agreement.html"><span>Terms &amp; Conditions</span><i class="fas fa-chevron-right"></i></a>
                    <a class="settings-link-row" href="/foodbank/frontend/views/public/privacy-security.html"><span>Privacy &amp; Policy</span><i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="settings-card support-card">
                    <div class="option-heading">
                        <span class="option-icon option-icon--orange"><i class="far fa-message"></i></span>
                        <div><h2>Contact Support</h2><p>We are here to help you</p></div>
                    </div>
                    <a href="mailto:support@foodbankapp.com"><i class="far fa-envelope"></i><span>Email Support<small>support@foodbankapp.com</small></span></a>
                    <a href="mailto:support@foodbankapp.com"><i class="fas fa-phone"></i><span>Phone Support<small>0123456789</small></span></a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="settings-warning-modal" id="settings-warning-modal" hidden>
    <div class="settings-warning-dialog" role="dialog" aria-modal="true" aria-labelledby="settings-warning-title">
        <button type="button" class="settings-warning-close" aria-label="Close warning">
            <i class="fas fa-xmark"></i>
        </button>
        <span class="settings-warning-icon"><i class="fas fa-triangle-exclamation"></i></span>
        <h2 id="settings-warning-title">Warning</h2>
        <p id="settings-warning-message"></p>
        <div class="settings-warning-actions">
            <button type="button" class="settings-warning-cancel">Cancel</button>
            <button type="button" class="settings-warning-confirm" id="settings-warning-confirm">Continue</button>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('[data-settings-root]');
    const title = root.querySelector('[data-settings-title]');
    const backButton = root.querySelector('.settings-back-btn');
    const views = root.querySelectorAll('.settings-view');
    const titles = {
        home: 'My Account',
        edit: 'Edit Profile',
        notifications: 'Notifications',
        security: 'Privacy & Security',
        login_activity: 'Login Activity',
        help: 'Help & Support'
    };
    const headerWrapper = document.querySelector('.header-wrapper');

    function openView(viewName) {
        views.forEach(view => view.classList.toggle('is-active', view.dataset.view === viewName));
        root.dataset.currentView = viewName;
        if (title) {
            title.textContent = titles[viewName] || 'My Account';
        }
        if (headerWrapper) {
            headerWrapper.style.display = viewName === 'home' ? '' : 'none';
        }
        if (backButton) {
            backButton.hidden = viewName === 'home';
        }
    }

    openView('home');

    root.querySelectorAll('[data-open-view]').forEach(button => {
        button.addEventListener('click', () => openView(button.dataset.openView));
    });

    root.querySelectorAll('[data-pref]').forEach(input => {
        const key = 'pa_setting_' + input.dataset.pref;
        const stored = localStorage.getItem(key);
        if (stored !== null) input.checked = stored === '1';
        input.addEventListener('change', () => localStorage.setItem(key, input.checked ? '1' : '0'));
    });

    root.querySelectorAll('.faq-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const item = button.closest('.faq-item');
            const expanded = item.classList.toggle('is-open');
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    const warningModal = document.getElementById('settings-warning-modal');
    const warningTitle = document.getElementById('settings-warning-title');
    const warningMessage = document.getElementById('settings-warning-message');
    const warningConfirm = document.getElementById('settings-warning-confirm');
    const warningClose = warningModal.querySelector('.settings-warning-close');
    const warningCancel = warningModal.querySelector('.settings-warning-cancel');
    const dangerMessages = {
        deactivate: {
            title: 'Deactivate Account?',
            message: 'Your account will be disabled and you will not be able to access donor features until an administrator restores it.',
            confirm: 'Deactivate'
        },
        delete: {
            title: 'Delete Account?',
            message: 'This action can permanently remove your account data. This cannot be undone once deletion is processed.',
            confirm: 'Delete Account'
        }
    };

    function closeWarning() {
        warningModal.hidden = true;
        document.body.classList.remove('settings-warning-open');
    }

    function openWarning(action) {
        const config = dangerMessages[action];
        if (!config) return;
        warningTitle.textContent = config.title;
        warningMessage.textContent = config.message;
        warningConfirm.textContent = config.confirm;
        warningConfirm.dataset.action = action;
        warningConfirm.classList.toggle('is-delete', action === 'delete');
        warningModal.hidden = false;
        document.body.classList.add('settings-warning-open');
    }

    root.querySelectorAll('[data-danger-action]').forEach(button => {
        button.addEventListener('click', () => openWarning(button.dataset.dangerAction));
    });

    warningClose.addEventListener('click', closeWarning);
    warningCancel.addEventListener('click', closeWarning);
    warningModal.addEventListener('click', event => {
        if (event.target === warningModal) closeWarning();
    });
    warningConfirm.addEventListener('click', () => {
        const action = warningConfirm.dataset.action;
        const endpoint = action === 'delete'
            ? '/foodbank/backend/controllers/individual/settings/request_account_deletion.php'
            : '/foodbank/backend/controllers/individual/settings/deactivate_account.php';

        warningConfirm.disabled = true;
        warningConfirm.textContent = 'Processing...';

        fetch(endpoint, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to process request.');
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                closeWarning();
            })
            .catch(error => {
                alert(error.message);
                warningConfirm.disabled = false;
                warningConfirm.textContent = dangerMessages[action]?.confirm || 'Continue';
            });
    });

    const avatarForm = document.getElementById('pa-avatar-form');
    const avatarInput = avatarForm.querySelector('input[type="file"]');
    const avatarPreview = document.getElementById('settings-avatar-preview');
    const summaryAvatar = document.getElementById('settings-summary-avatar');
    const sidebarAvatar = document.querySelector('.sidebar-profile .profile-avatar');

    avatarInput.addEventListener('change', () => {
        if (!avatarInput.files.length) return;

        const formData = new FormData(avatarForm);
        fetch('/foodbank/backend/controllers/individual/settings/update_avatar.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Upload failed');
                avatarPreview.src = data.avatar_url;
                summaryAvatar.src = data.avatar_url;
                if (sidebarAvatar) {
                    sidebarAvatar.src = data.avatar_url;
                }
            })
            .catch(error => alert(error.message));
    });

    document.getElementById('pa-profile-form').addEventListener('submit', event => {
        event.preventDefault();
        const status = document.getElementById('profile-status');
        fetch('/foodbank/backend/controllers/individual/settings/update_profile.php', {
            method: 'POST',
            body: new FormData(event.currentTarget)
        })
            .then(response => response.json())
            .then(data => {
                status.textContent = data.message || (data.success ? 'Profile updated.' : 'Unable to update profile.');
                status.classList.toggle('is-error', !data.success);
            })
            .catch(() => {
                status.textContent = 'Unable to update profile.';
                status.classList.add('is-error');
            });
    });

    document.getElementById('pa-password-form').addEventListener('submit', event => {
        event.preventDefault();
        const status = document.getElementById('password-status');
        fetch('/foodbank/backend/controllers/individual/settings/change_password.php', {
            method: 'POST',
            body: new FormData(event.currentTarget)
        })
            .then(response => response.json())
            .then(data => {
                status.textContent = data.message || (data.success ? 'Password updated.' : 'Unable to update password.');
                status.classList.toggle('is-error', !data.success);
                if (data.success) event.currentTarget.reset();
            })
            .catch(() => {
                status.textContent = 'Unable to update password.';
                status.classList.add('is-error');
            });
    });
})();
</script>
