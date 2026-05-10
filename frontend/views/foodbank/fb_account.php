<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
    http_response_code(401);
    exit('Unauthorized');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

try {
    $stmt = $pdo->prepare("
        SELECT
            u.*,
            a.Email,
            a.Phone_Number,
            a.Custom_App_ID,
            fb.*
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON u.User_ID = a.User_ID
        LEFT JOIN FOOD_BANKS fb ON fb.Account_ID = a.Account_ID
        WHERE a.Account_ID = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Foodbank account load error: ' . $e->getMessage());
    $account = [];
}

$managerName = trim(
    (($account['First_Name'] ?? ($account['Manager_First_Name'] ?? '')) . ' ' .
    ($account['Last_Name'] ?? ($account['Manager_Last_Name'] ?? '')))
);
$avatar = !empty($account['Profile_Picture_URL'])
    ? $account['Profile_Picture_URL']
    : (!empty($account['Manager_Profile_Picture_URL'])
        ? $account['Manager_Profile_Picture_URL']
        : '/foodbank/frontend/assets/images/default-avatar.png');

$dayOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$operatingDaysValue = (string) ($account['Operating_Days'] ?? '');
?>

<section class="fb-page fb-account" aria-labelledby="fb-account-title">
    <div class="fb-page-heading">
        <div>
            <h2 id="fb-account-title">Account</h2>
            <p>Manage manager and public food bank information</p>
        </div>
    </div>

    <div class="fb-account-grid">
        <aside class="fb-account-summary fb-panel">
            <form class="fb-avatar-form" id="fb-avatar-form" enctype="multipart/form-data">
                <label class="fb-avatar-picker">
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($managerName) ?>" id="fb-avatar-preview" onerror="this.src='/foodbank/frontend/assets/images/default-avatar.png'">
                    <span><i class="fas fa-camera"></i></span>
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" hidden>
                </label>
                <p class="fb-avatar-hint" id="fb-avatar-status">Change profile photo</p>
            </form>
            <h3><?= htmlspecialchars(($account['Organization_Name'] ?? '') ?: 'Food Bank') ?></h3>
            <p><?= htmlspecialchars($account['Email'] ?? '') ?></p>
            <dl>
                <div><dt>Manager</dt><dd><?= htmlspecialchars($managerName ?: '-') ?></dd></div>
                <div><dt>Food Bank ID</dt><dd><?= htmlspecialchars($account['Custom_FoodBank_ID'] ?? '-') ?></dd></div>
                <div><dt>Status</dt><dd><?= htmlspecialchars($account['Org_Status'] ?? '-') ?></dd></div>
            </dl>
        </aside>

        <div class="fb-account-forms">
            <form class="fb-panel fb-form" id="fb-profile-form">
                <h3>Profile Information</h3>

                <div class="fb-form-grid">
                    <label><span>Organization Name</span><input type="text" name="organization_name" value="<?= htmlspecialchars($account['Organization_Name'] ?? '') ?>" required></label>
                    <label><span>Public Email</span><input type="email" name="public_email" value="<?= htmlspecialchars($account['Public_Email'] ?? '') ?>"></label>
                    <label><span>Public Phone</span><input type="text" name="public_phone" value="<?= htmlspecialchars($account['Public_Phone'] ?? '') ?>"></label>
                    <div class="fb-field is-full"><span>Operating Days</span>
                        <input type="hidden" name="operating_days" id="fb-operating-days" value="<?= htmlspecialchars($operatingDaysValue) ?>" required>
                        <div class="fb-operating-days-picker operating-days-picker" data-hidden-target="fb-operating-days">
                            <?php foreach ($dayOrder as $day): ?>
                                <label class="day-option"><input type="checkbox" name="operating_day_values[]" value="<?= $day ?>"> <?= $day ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <label><span>Time Open</span><input type="time" name="time_open" value="<?= htmlspecialchars(substr((string) ($account['Time_Open'] ?? ''), 0, 5)) ?>" required></label>
                    <label><span>Time Close</span><input type="time" name="time_close" value="<?= htmlspecialchars(substr((string) ($account['Time_Close'] ?? ''), 0, 5)) ?>" required></label>
                    <label><span>Manager First Name</span><input type="text" name="first_name" value="<?= htmlspecialchars($account['First_Name'] ?? ($account['Manager_First_Name'] ?? '')) ?>" required></label>
                    <label><span>Manager Last Name</span><input type="text" name="last_name" value="<?= htmlspecialchars($account['Last_Name'] ?? ($account['Manager_Last_Name'] ?? '')) ?>" required></label>
                    <label><span>Login Email</span><input type="email" name="email" value="<?= htmlspecialchars($account['Email'] ?? '') ?>" required></label>
                    <label><span>Manager Phone</span><input type="text" name="phone" value="<?= htmlspecialchars($account['Phone_Number'] ?? '') ?>"></label>
                    <label class="is-full"><span>Food Bank Address</span><input type="text" name="physical_address" value="<?= htmlspecialchars($account['Physical_Address'] ?? '') ?>" required></label>
                    <label class="is-full"><span>Manager Address</span><input type="text" name="manager_address" value="<?= htmlspecialchars($account['Address'] ?? ($account['Manager_Address'] ?? '')) ?>"></label>
                </div>

                <button type="submit">Save Changes</button>
                <p class="fb-form-status" id="fb-profile-status"></p>
            </form>

            <form class="fb-panel fb-form" id="fb-password-form">
                <h3>Password</h3>
                <div class="fb-form-grid">
                    <label><span>Current Password</span><input type="password" name="current_password" required></label>
                    <label><span>New Password</span><input type="password" name="new_password" required></label>
                    <label><span>Confirm New Password</span><input type="password" name="confirm_password" required></label>
                </div>
                <button type="submit">Update Password</button>
                <p class="fb-form-status" id="fb-password-status"></p>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    function handleForm(formId, endpoint, statusId) {
        const form = document.getElementById(formId);
        const status = document.getElementById(statusId);
        if (!form) return;

        form.addEventListener('submit', event => {
            event.preventDefault();
            if (formId === 'fb-profile-form') syncDaysPicker();
            status.textContent = 'Saving...';
            status.classList.remove('is-error');

            fetch(endpoint, { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    status.textContent = data.message || (data.success ? 'Saved.' : 'Unable to save.');
                    status.classList.toggle('is-error', !data.success);
                    if (data.success && formId === 'fb-password-form') form.reset();
                })
                .catch(() => {
                    status.textContent = 'Unable to save changes.';
                    status.classList.add('is-error');
                });
        });
    }

    handleForm('fb-profile-form', '/foodbank/backend/controllers/foodbank/settings/update_profile.php', 'fb-profile-status');
    handleForm('fb-password-form', '/foodbank/backend/controllers/foodbank/settings/change_password.php', 'fb-password-status');

    const dayOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const daysHidden = document.getElementById('fb-operating-days');
    const daysPicker = document.querySelector('.fb-operating-days-picker');

    function compressOperatingDays(days) {
        const selected = dayOrder.filter(day => days.includes(day));
        if (selected.length === 7) return 'Daily';
        if (!selected.length) return '';
        const indexes = selected.map(day => dayOrder.indexOf(day));
        const isContiguous = indexes.every((index, position) => position === 0 || index === indexes[position - 1] + 1);
        return isContiguous && selected.length > 1 ? `${selected[0]}-${selected[selected.length - 1]}` : selected.join(', ');
    }

    function dayIndexFromText(day) {
        return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'].indexOf(String(day || '').slice(0, 3).toLowerCase());
    }

    function parseOperatingDays(value) {
        const text = String(value || '').trim().toLowerCase();
        if (!text) return [];
        if (['daily', 'everyday', 'every day'].includes(text)) return [...dayOrder];

        const selected = new Set();
        const dayPattern = /(mon(?:day)?|tue(?:sday)?|tues(?:day)?|wed(?:nesday)?|thu(?:rsday)?|thur(?:sday)?|thurs(?:day)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)(?:\s*(?:-|to)\s*(mon(?:day)?|tue(?:sday)?|tues(?:day)?|wed(?:nesday)?|thu(?:rsday)?|thur(?:sday)?|thurs(?:day)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?))?/gi;
        let match;
        while ((match = dayPattern.exec(text)) !== null) {
            const start = dayIndexFromText(match[1]);
            const end = match[2] ? dayIndexFromText(match[2]) : start;
            if (start === -1 || end === -1) continue;
            if (start <= end) {
                for (let i = start; i <= end; i++) selected.add(dayOrder[i]);
            } else {
                for (let i = start; i < dayOrder.length; i++) selected.add(dayOrder[i]);
                for (let i = 0; i <= end; i++) selected.add(dayOrder[i]);
            }
        }
        return dayOrder.filter(day => selected.has(day));
    }

    function syncDaysPicker() {
        if (!daysHidden || !daysPicker) return;
        const selected = Array.from(daysPicker.querySelectorAll('input[type="checkbox"]:checked')).map(input => input.value);
        daysHidden.value = compressOperatingDays(selected);
    }

    if (daysPicker && daysHidden) {
        const selectedDays = parseOperatingDays(daysHidden.value);
        daysPicker.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.checked = selectedDays.includes(input.value);
            input.addEventListener('change', syncDaysPicker);
        });
        syncDaysPicker();
    }

    const avatarForm = document.getElementById('fb-avatar-form');
    const avatarInput = avatarForm?.querySelector('input[type="file"]');
    const avatarPreview = document.getElementById('fb-avatar-preview');
    const avatarStatus = document.getElementById('fb-avatar-status');
    const sidebarAvatar = document.querySelector('.sidebar-profile .profile-avatar');

    avatarInput?.addEventListener('change', () => {
        if (!avatarInput.files.length) return;
        avatarStatus.textContent = 'Uploading...';
        avatarStatus.classList.remove('is-error');

        fetch('/foodbank/backend/controllers/foodbank/settings/update_avatar.php', {
            method: 'POST',
            body: new FormData(avatarForm)
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Upload failed.');
                avatarPreview.src = data.avatar_url;
                if (sidebarAvatar) sidebarAvatar.src = data.avatar_url;
                avatarStatus.textContent = 'Profile photo updated.';
            })
            .catch(error => {
                avatarStatus.textContent = error.message;
                avatarStatus.classList.add('is-error');
            });
    });
})();
</script>
