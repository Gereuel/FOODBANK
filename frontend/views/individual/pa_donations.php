<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    exit('Unauthorized');
}

try {
    $stmt = $pdo->prepare("
        SELECT
            d.Donation_ID,
            d.Tracking_Number,
            d.Item_Type,
            d.Item_Description,
            d.Quantity_Description,
            d.Pickup_Address,
            d.Status,
            d.Donation_Time,
            d.Date_Donated,
            d.Generated_On,
            d.Proof_Of_Delivery_URL,
            d.Notes,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.Address AS Donor_Address,
            a.Email,
            a.Phone_Number,
            a.Custom_App_ID,
            fb.Organization_Name,
            fb.Physical_Address AS FoodBank_Address,
            fb.FoodBank_ID,
            fb.Custom_FoodBank_ID,
            fb.Public_Phone AS FoodBank_Phone,
            COALESCE(fb.Manager_First_Name, mu.First_Name) AS Manager_First,
            COALESCE(fb.Manager_Last_Name, mu.Last_Name) AS Manager_Last,
            COALESCE(fb.Manager_Phone, mfa.Phone_Number) AS Manager_Phone
        FROM DONATIONS d
        JOIN ACCOUNTS a ON d.Donor_Account_ID = a.Account_ID
        JOIN USERS u ON a.User_ID = u.User_ID
        JOIN FOOD_BANKS fb ON d.FoodBank_ID = fb.FoodBank_ID
        LEFT JOIN ACCOUNTS mfa ON fb.Account_ID = mfa.Account_ID
        LEFT JOIN USERS mu ON mfa.User_ID = mu.User_ID
        WHERE d.Donor_Account_ID = ?
        ORDER BY d.Date_Donated DESC, d.Donation_ID DESC
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $donations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('PA donations load error: ' . $e->getMessage());
    $donations = [];
}

function donation_date_label(?string $date): string
{
    if (!$date) {
        return '-';
    }

    return date('M j, Y', strtotime($date));
}

function donation_time_label(?string $time): string
{
    if (!$time) {
        return '-';
    }

    return date('g:i A', strtotime($time));
}

function donation_json(array $donation): string
{
    return htmlspecialchars(json_encode($donation), ENT_QUOTES, 'UTF-8');
}
?>

<section class="pa-donations" aria-labelledby="donations-title">
    <div class="donations-header">
        <h2 id="donations-title">My Donations</h2>
        <p>Track your generous contributions</p>
    </div>

    <?php if (empty($donations)): ?>
        <div class="donations-empty">
            <i class="fas fa-hand-holding-heart"></i>
            <p>No donations have been recorded for your account yet.</p>
        </div>
    <?php else: ?>
        <div class="donations-list">
            <?php foreach ($donations as $donation): ?>
                <button class="donation-row" type="button" data-donation="<?= donation_json($donation) ?>">
                    <span class="donation-main">
                        <strong><?= htmlspecialchars($donation['Item_Type']) ?></strong>
                        <span><?= htmlspecialchars($donation['Organization_Name'] ?: 'Unassigned Food Bank') ?></span>
                        <small><i class="far fa-calendar"></i><?= htmlspecialchars(donation_date_label($donation['Date_Donated'])) ?></small>
                    </span>
                    <span class="donation-quantity"><?= htmlspecialchars($donation['Quantity_Description']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="pa-report-modal" id="pa-donation-report-modal" hidden>
    <div class="pa-report-content">
        <header class="pa-report-header">
            <div>
                <h2>Donation Report</h2>
                <p id="pa-report-tracking">Tracking Number: -</p>
            </div>
            <button type="button" class="pa-report-close" id="pa-report-close" aria-label="Close report">&times;</button>
        </header>

        <div class="pa-report-body">
            <div class="pa-report-status-bar">
                <div class="pa-report-status-left">
                    <div class="pa-report-status-icon" id="pa-report-status-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <div class="pa-report-status-label">Status</div>
                        <div class="pa-report-status-value" id="pa-report-status">-</div>
                    </div>
                </div>
                <div class="pa-report-generated">
                    Generated on:<br>
                    <strong id="pa-report-generated-on">-</strong>
                </div>
            </div>

            <section class="pa-report-section">
                <h3>Donor Information</h3>
                <div class="pa-report-grid">
                    <div><span>Full Name</span><strong id="pa-report-donor-name">-</strong></div>
                    <div><span>Email Address</span><strong id="pa-report-donor-email">-</strong></div>
                    <div><span>Phone Number</span><strong id="pa-report-donor-phone">-</strong></div>
                    <div><span>Unique ID</span><strong id="pa-report-donor-id">-</strong></div>
                    <div class="is-full"><span>Address</span><strong id="pa-report-donor-address">-</strong></div>
                </div>
            </section>

            <section class="pa-report-section">
                <h3>Donation Details</h3>
                <div class="pa-report-grid">
                    <div><span>Amount/Items</span><strong id="pa-report-quantity">-</strong></div>
                    <div><span>Type</span><strong id="pa-report-item-type">-</strong></div>
                    <div><span>Date</span><strong id="pa-report-date">-</strong></div>
                    <div><span>Time</span><strong id="pa-report-time">-</strong></div>
                    <div class="is-full"><span>Tracking Number</span><strong id="pa-report-tracking-detail">-</strong></div>
                </div>
            </section>

            <section class="pa-report-section">
                <h3>Designated Food Bank</h3>
                <div class="pa-report-grid">
                    <div><span>Food Bank Name</span><strong id="pa-report-fb-name">-</strong></div>
                    <div><span>Food Bank ID</span><strong id="pa-report-fb-id">-</strong></div>
                    <div class="is-full"><span>Location</span><strong id="pa-report-fb-location">-</strong></div>
                    <div class="is-full"><span>Contact Information</span><strong id="pa-report-fb-contact">-</strong></div>
                </div>
            </section>

            <section class="pa-report-section">
                <h3>Proof of Delivery</h3>
                <div id="pa-report-proof-wrap">
                    <p class="pa-report-muted">No proof of delivery uploaded.</p>
                </div>
                <p class="pa-report-verified" id="pa-report-proof-verified" hidden>Delivery verified with photographic evidence</p>
            </section>

            <section class="pa-report-section">
                <h3>Additional Notes</h3>
                <p class="pa-report-notes" id="pa-report-notes">-</p>
            </section>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('pa-donation-report-modal');
    const closeBtn = document.getElementById('pa-report-close');

    function value(data, key, fallback = '-') {
        return data[key] || fallback;
    }

    function formatDate(dateValue, long = false) {
        if (!dateValue) return '-';
        return new Date(dateValue).toLocaleDateString('en-PH', long
            ? { year: 'numeric', month: 'long', day: 'numeric' }
            : { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatTime(timeValue) {
        if (!timeValue) return '-';
        const parts = String(timeValue).split(':');
        const hour = parseInt(parts[0], 10);
        const minute = parts[1] || '00';
        const suffix = hour >= 12 ? 'PM' : 'AM';
        return `${hour % 12 || 12}:${minute} ${suffix}`;
    }

    function setText(id, text) {
        document.getElementById(id).textContent = text || '-';
    }

    function openReport(data) {
        const fullName = [data.First_Name, data.Middle_Name, data.Last_Name].filter(Boolean).join(' ');
        const status = value(data, 'Status');
        const statusIcon = document.getElementById('pa-report-status-icon');
        const proofWrap = document.getElementById('pa-report-proof-wrap');
        const proofVerified = document.getElementById('pa-report-proof-verified');

        setText('pa-report-tracking', 'Tracking Number: ' + value(data, 'Tracking_Number'));
        setText('pa-report-tracking-detail', value(data, 'Tracking_Number'));
        setText('pa-report-status', status);
        setText('pa-report-generated-on', formatDate(data.Generated_On, true));
        statusIcon.className = 'pa-report-status-icon pa-report-status-icon--' + status.toLowerCase().replace(/\s+/g, '-');

        setText('pa-report-donor-name', fullName);
        setText('pa-report-donor-email', value(data, 'Email'));
        setText('pa-report-donor-phone', value(data, 'Phone_Number'));
        setText('pa-report-donor-id', value(data, 'Custom_App_ID'));
        setText('pa-report-donor-address', value(data, 'Donor_Address'));

        setText('pa-report-quantity', value(data, 'Quantity_Description'));
        setText('pa-report-item-type', value(data, 'Item_Type'));
        setText('pa-report-date', formatDate(data.Date_Donated, true));
        setText('pa-report-time', formatTime(data.Donation_Time));

        setText('pa-report-fb-name', value(data, 'Organization_Name'));
        setText('pa-report-fb-id', data.Custom_FoodBank_ID || ('FB-' + value(data, 'FoodBank_ID')));
        setText('pa-report-fb-location', value(data, 'FoodBank_Address'));
        setText('pa-report-fb-contact', `Manager: ${value(data, 'Manager_First', '')} ${value(data, 'Manager_Last', '')}, Phone: ${value(data, 'Manager_Phone')}`);

        if (data.Proof_Of_Delivery_URL) {
            proofWrap.innerHTML = `<img src="${data.Proof_Of_Delivery_URL}" alt="Proof of Delivery" class="pa-report-proof-img">`;
            proofVerified.hidden = false;
        } else {
            proofWrap.innerHTML = '<p class="pa-report-muted">No proof of delivery uploaded.</p>';
            proofVerified.hidden = true;
        }

        setText('pa-report-notes', data.Notes || 'No additional notes.');
        modal.hidden = false;
        document.body.classList.add('pa-report-open');
    }

    function closeReport() {
        modal.hidden = true;
        document.body.classList.remove('pa-report-open');
    }

    document.querySelectorAll('.donation-row').forEach(row => {
        row.addEventListener('click', () => openReport(JSON.parse(row.dataset.donation)));
    });

    closeBtn.addEventListener('click', closeReport);
    modal.addEventListener('click', event => {
        if (event.target === modal) closeReport();
    });
})();
</script>
