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
            a.Account_ID,
            a.Custom_App_ID,
            a.Email,
            a.Phone_Number,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.Suffix,
            u.Address,
            u.Birthdate,
            u.Profile_Picture,
            u.Profile_Picture_URL,
            COUNT(d.Donation_ID) AS Donation_Count,
            MAX(d.Date_Donated) AS Last_Donation_Date
        FROM ACCOUNTS a
        JOIN USERS u ON u.User_ID = a.User_ID
        LEFT JOIN DONATIONS d
            ON d.Donor_Account_ID = a.Account_ID
           AND d.Status != 'Cancelled'
        WHERE a.Account_Type = 'PA'
          AND a.Status = 'Active'
          AND a.Account_ID != ?
        GROUP BY
            a.Account_ID,
            a.Custom_App_ID,
            a.Email,
            a.Phone_Number,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.Suffix,
            u.Address,
            u.Birthdate,
            u.Profile_Picture,
            u.Profile_Picture_URL
        ORDER BY Donation_Count DESC, u.First_Name ASC, u.Last_Name ASC
        LIMIT 12
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $donors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('PA donors load error: ' . $e->getMessage());
    $donors = [];
}

function donor_avatar_url(array $donor): string
{
    if (!empty($donor['Profile_Picture_URL'])) {
        return $donor['Profile_Picture_URL'];
    }

    if (!empty($donor['Profile_Picture']) && is_string($donor['Profile_Picture'])) {
        return 'data:image/jpeg;base64,' . base64_encode($donor['Profile_Picture']);
    }

    return '/foodbank/frontend/assets/images/default-avatar.png';
}

function donor_count_label(int $count): string
{
    if ($count >= 1000000) {
        return round($count / 1000000, 1) . 'M Donations';
    }
    if ($count >= 1000) {
        return round($count / 1000, 1) . 'k Donations';
    }

    return $count . ' ' . ($count === 1 ? 'Donation' : 'Donations');
}

function donor_date_label(?string $date): string
{
    if (!$date) {
        return 'No donations yet';
    }

    return date('M j, Y', strtotime($date));
}

function donor_profile_payload(array $donor, string $name, string $avatar, string $countLabel): string
{
    $payload = [
        'name' => $name,
        'avatar' => $avatar,
        'account_id' => (int) $donor['Account_ID'],
        'custom_id' => $donor['Custom_App_ID'] ?? '',
        'email' => $donor['Email'] ?? '',
        'phone' => $donor['Phone_Number'] ?? '',
        'address' => $donor['Address'] ?? '',
        'birthdate' => donor_date_label($donor['Birthdate'] ?? null),
        'donations' => $countLabel,
        'last_donation' => donor_date_label($donor['Last_Donation_Date'] ?? null),
    ];

    return htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');
}
?>

<section class="pa-donors" aria-labelledby="donors-title">
    <div class="donors-header">
        <div>
            <h2 id="donors-title">Active Donors</h2>
            <p>Connect with our generous community</p>
        </div>
        <div class="donors-search">
            <i class="fas fa-search"></i>
            <input type="search" id="donor-search" placeholder="Search donors" autocomplete="off">
        </div>
    </div>

    <?php if (empty($donors)): ?>
        <div class="donors-empty">
            <i class="fas fa-user-group"></i>
            <p>No active donors found yet.</p>
        </div>
    <?php else: ?>
        <div class="donors-grid">
            <?php foreach ($donors as $donor):
                $name = trim(implode(' ', array_filter([
                    $donor['First_Name'] ?? '',
                    $donor['Middle_Name'] ?? '',
                    $donor['Last_Name'] ?? '',
                    $donor['Suffix'] ?? '',
                ])));
                $donationCount = (int) $donor['Donation_Count'];
                $countLabel = donor_count_label($donationCount);
                $avatarUrl = donor_avatar_url($donor);
            ?>
                <article
                    class="donor-card"
                    data-search="<?= htmlspecialchars(strtolower($name . ' ' . ($donor['Email'] ?? '') . ' ' . ($donor['Custom_App_ID'] ?? ''))) ?>"
                >
                    <img
                        src="<?= htmlspecialchars($avatarUrl) ?>"
                        alt="<?= htmlspecialchars($name) ?>"
                        class="donor-card__avatar"
                        onerror="this.src='/foodbank/frontend/assets/images/default-avatar.png'"
                    >
                    <h3><?= htmlspecialchars($name) ?></h3>
                    <p><?= htmlspecialchars($countLabel) ?></p>
                    <span class="donor-card__meta">Last donation: <?= htmlspecialchars(donor_date_label($donor['Last_Donation_Date'] ?? null)) ?></span>
                    <button
                        type="button"
                        class="donor-profile-btn"
                        data-profile="<?= donor_profile_payload($donor, $name, $avatarUrl, $countLabel) ?>"
                    >
                        View Profile
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="donors-empty donors-empty--search" id="donors-search-empty" hidden>
            <i class="fas fa-magnifying-glass"></i>
            <p>No donors match your search.</p>
        </div>
    <?php endif; ?>
</section>

<div class="donor-profile-modal" id="donor-profile-modal" hidden>
    <div class="donor-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="donor-modal-name">
        <button type="button" class="donor-profile-close" id="donor-profile-close" aria-label="Close profile">
            <i class="fas fa-xmark"></i>
        </button>
        <div class="donor-profile-hero">
            <img src="/foodbank/frontend/assets/images/default-avatar.png" alt="" id="donor-modal-avatar">
            <div>
                <h2 id="donor-modal-name">Donor</h2>
                <p id="donor-modal-email">-</p>
            </div>
        </div>
        <dl class="donor-profile-details">
            <div>
                <dt>Donor ID</dt>
                <dd id="donor-modal-code">-</dd>
            </div>
            <div>
                <dt>Donation Count</dt>
                <dd id="donor-modal-donations">-</dd>
            </div>
            <div>
                <dt>Last Donation</dt>
                <dd id="donor-modal-last-donation">-</dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd id="donor-modal-phone">-</dd>
            </div>
            <div>
                <dt>Birthdate</dt>
                <dd id="donor-modal-birthdate">-</dd>
            </div>
            <div class="is-full">
                <dt>Address</dt>
                <dd id="donor-modal-address">-</dd>
            </div>
        </dl>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('donor-profile-modal');
    const closeBtn = document.getElementById('donor-profile-close');
    const searchInput = document.getElementById('donor-search');
    const searchEmpty = document.getElementById('donors-search-empty');
    const donorCards = Array.from(document.querySelectorAll('.donor-card'));
    const avatarEl = document.getElementById('donor-modal-avatar');
    const nameEl = document.getElementById('donor-modal-name');
    const emailEl = document.getElementById('donor-modal-email');
    const codeEl = document.getElementById('donor-modal-code');
    const donationsEl = document.getElementById('donor-modal-donations');
    const lastDonationEl = document.getElementById('donor-modal-last-donation');
    const phoneEl = document.getElementById('donor-modal-phone');
    const birthdateEl = document.getElementById('donor-modal-birthdate');
    const addressEl = document.getElementById('donor-modal-address');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            donorCards.forEach(card => {
                const isVisible = !query || card.dataset.search.includes(query);
                card.hidden = !isVisible;
                if (isVisible) visibleCount++;
            });

            if (searchEmpty) {
                searchEmpty.hidden = visibleCount !== 0;
            }
        });
    }

    document.querySelectorAll('.donor-profile-btn').forEach(button => {
        button.addEventListener('click', () => {
            const profile = JSON.parse(button.dataset.profile);
            avatarEl.src = profile.avatar || '/foodbank/frontend/assets/images/default-avatar.png';
            nameEl.textContent = profile.name || 'Donor';
            emailEl.textContent = profile.email || '-';
            codeEl.textContent = profile.custom_id || '-';
            donationsEl.textContent = profile.donations || '-';
            lastDonationEl.textContent = profile.last_donation || '-';
            phoneEl.textContent = profile.phone || '-';
            birthdateEl.textContent = profile.birthdate || '-';
            addressEl.textContent = profile.address || '-';
            modal.hidden = false;
            document.body.classList.add('donor-profile-open');
        });
    });

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('donor-profile-open');
    }

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', event => {
        if (event.target === modal) closeModal();
    });
})();
</script>
