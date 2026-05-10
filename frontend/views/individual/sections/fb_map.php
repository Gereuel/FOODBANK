<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    exit('Unauthorized');
}

if (!isset($pdo)) {
    require_once __DIR__ . '/../../../../backend/config/database.php';
}

$selectedId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS PA_FOOD_BANK_FAVORITES (
            Favorite_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            FoodBank_ID INT NOT NULL,
            Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_pa_foodbank_favorite (Account_ID, FoodBank_ID),
            INDEX idx_pa_favorite_account (Account_ID),
            INDEX idx_pa_favorite_foodbank (FoodBank_ID),
            CONSTRAINT fk_pa_favorite_account
                FOREIGN KEY (Account_ID) REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_pa_favorite_foodbank
                FOREIGN KEY (FoodBank_ID) REFERENCES FOOD_BANKS(FoodBank_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        )
    ");

    try {
        $pdo->exec("ALTER TABLE FOOD_BANKS ADD COLUMN Map_Image_URL VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) !== 1060) {
            throw $e;
        }
    }

    $stmt = $pdo->prepare("
        SELECT
            fb.FoodBank_ID,
            fb.Custom_FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Map_Image_URL,
            fb.Public_Email,
            fb.Public_Phone,
            fb.Time_Open,
            fb.Time_Close,
            fb.Operating_Days,
            fb.Verification_Status,
            fb.Org_Status,
            fb.Date_Registered,
            COALESCE(fb.Manager_First_Name, u.First_Name) AS Manager_First,
            COALESCE(fb.Manager_Last_Name, u.Last_Name) AS Manager_Last,
            COALESCE(fb.Manager_Email, a.Email) AS Manager_Email,
            COALESCE(fb.Manager_Phone, a.Phone_Number) AS Manager_Phone,
            CASE WHEN fav.Favorite_ID IS NULL THEN 0 ELSE 1 END AS is_favourite,
            COUNT(d.Donation_ID) AS Donation_Count
        FROM FOOD_BANKS fb
        LEFT JOIN ACCOUNTS a ON a.Account_ID = fb.Account_ID
        LEFT JOIN USERS u ON u.User_ID = a.User_ID
        LEFT JOIN PA_FOOD_BANK_FAVORITES fav
          ON fav.FoodBank_ID = fb.FoodBank_ID
         AND fav.Account_ID = ?
        LEFT JOIN DONATIONS d ON d.FoodBank_ID = fb.FoodBank_ID AND d.Status != 'Cancelled'
        WHERE fb.Verification_Status = 'Approved'
          AND fb.Org_Status = 'Active'
        GROUP BY
            fb.FoodBank_ID,
            fb.Custom_FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Map_Image_URL,
            fb.Public_Email,
            fb.Public_Phone,
            fb.Time_Open,
            fb.Time_Close,
            fb.Operating_Days,
            fb.Verification_Status,
            fb.Org_Status,
            fb.Date_Registered,
            Manager_First,
            Manager_Last,
            Manager_Email,
            Manager_Phone,
            fav.Favorite_ID
        ORDER BY fb.Date_Registered DESC
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $banks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Food bank map load error: ' . $e->getMessage());
    $banks = [];
}

$selectedBank = $banks[0] ?? null;
foreach ($banks as $bank) {
    if ((int) $bank['FoodBank_ID'] === $selectedId) {
        $selectedBank = $bank;
        break;
    }
}

function fb_time(?string $time): string
{
    return $time ? date('g:i A', strtotime($time)) : '-';
}

function fb_day_index(string $day): ?int
{
    $key = substr(strtolower(trim($day)), 0, 3);
    $map = [
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
        'sun' => 7,
    ];

    return $map[$key] ?? null;
}

function fb_day_is_in_range(int $today, int $start, int $end): bool
{
    if ($start <= $end) {
        return $today >= $start && $today <= $end;
    }

    return $today >= $start || $today <= $end;
}

function fb_operates_today(?string $operatingDays): bool
{
    $days = strtolower(trim((string) $operatingDays));

    if ($days === '') {
        return false;
    }

    if (preg_match('/\b(daily|everyday|every day)\b/', $days)) {
        return true;
    }

    $today = (int) date('N');
    $dayPattern = '(mon(?:day)?|tue(?:sday)?|tues(?:day)?|wed(?:nesday)?|thu(?:rsday)?|thur(?:sday)?|thurs(?:day)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)';

    preg_match_all('/' . $dayPattern . '(?:\s*(?:-|to)\s*' . $dayPattern . ')?/i', $days, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $start = fb_day_index($match[1]);
        $end = isset($match[2]) && $match[2] !== '' ? fb_day_index($match[2]) : $start;

        if ($start !== null && $end !== null && fb_day_is_in_range($today, $start, $end)) {
            return true;
        }
    }

    return false;
}

function fb_status(?string $open, ?string $close, ?string $operatingDays = null): string
{
    if (!$open || !$close || !fb_operates_today($operatingDays)) {
        return 'Closed';
    }

    $now = strtotime(date('H:i:s'));
    return ($now >= strtotime($open) && $now <= strtotime($close)) ? 'Open Now' : 'Closed';
}

function fb_details_payload(?array $bank): string
{
    if (!$bank) {
        return '{}';
    }

    $payload = [
        'id' => (int) $bank['FoodBank_ID'],
        'custom_id' => $bank['Custom_FoodBank_ID'] ?: ('FB-' . $bank['FoodBank_ID']),
        'name' => $bank['Organization_Name'],
        'address' => $bank['Physical_Address'],
        'map_image' => $bank['Map_Image_URL'] ?: '',
        'email' => $bank['Public_Email'] ?: '-',
        'phone' => $bank['Public_Phone'] ?: '-',
        'hours' => fb_time($bank['Time_Open']) . ' - ' . fb_time($bank['Time_Close']),
        'days' => $bank['Operating_Days'] ?: '-',
        'status' => fb_status($bank['Time_Open'], $bank['Time_Close'], $bank['Operating_Days']),
        'manager' => trim(($bank['Manager_First'] ?? '') . ' ' . ($bank['Manager_Last'] ?? '')) ?: '-',
        'manager_email' => $bank['Manager_Email'] ?: '-',
        'manager_phone' => $bank['Manager_Phone'] ?: '-',
        'donations' => (int) $bank['Donation_Count'],
        'registered' => $bank['Date_Registered'] ? date('M j, Y', strtotime($bank['Date_Registered'])) : '-',
    ];

    return htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');
}

$status = $selectedBank ? fb_status($selectedBank['Time_Open'], $selectedBank['Time_Close'], $selectedBank['Operating_Days']) : 'Closed';
$mapImage = $selectedBank['Map_Image_URL'] ?? '';
$isFavorite = $selectedBank && !empty($selectedBank['is_favourite']);
$directionsUrl = $selectedBank
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($selectedBank['Physical_Address'])
    : '#';
?>

<section class="fb-map-view" data-bank='<?= fb_details_payload($selectedBank) ?>'>
    <button type="button" class="fb-map-back nav-link" data-target="/foodbank/frontend/views/individual/pa_foodbanks.php" aria-label="Back to food banks">
        <i class="fas fa-arrow-left"></i>
    </button>

    <button
        type="button"
        class="fb-map-favorite fav-btn <?= $isFavorite ? 'fav-btn--active' : '' ?>"
        aria-label="<?= $isFavorite ? 'Remove saved food bank' : 'Save food bank' ?>"
        data-id="<?= $selectedBank ? (int) $selectedBank['FoodBank_ID'] : 0 ?>">
        <i class="<?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
    </button>

    <div
        class="fb-detail-map fb-detail-map--image <?= $mapImage ? 'has-map-image' : '' ?>"
        <?= $mapImage ? 'style="background-image: url(' . htmlspecialchars($mapImage, ENT_QUOTES, 'UTF-8') . ');"' : '' ?>>
        <?php if (!$mapImage): ?>
            <div class="fb-map-placeholder">
                <i class="fas fa-map-location-dot"></i>
                <span>No map screenshot uploaded</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$selectedBank): ?>
        <div class="fb-map-empty">
            <i class="fas fa-store-slash"></i>
            <p>No food bank details available.</p>
        </div>
    <?php else: ?>
        <div class="fb-map-pin" aria-hidden="true">
            <i class="fas fa-location-dot"></i>
        </div>

        <article class="fb-map-card">
            <h2><?= htmlspecialchars($selectedBank['Organization_Name']) ?></h2>
            <div class="fb-map-meta">
                <span class="status-badge <?= $status === 'Open Now' ? 'status-badge--open' : 'status-badge--closed' ?>">
                    <?= htmlspecialchars($status) ?>
                </span>
                <span><i class="fas fa-star"></i> 4.8</span>
            </div>
            <p>
                <?= htmlspecialchars($selectedBank['Physical_Address']) ?>
            </p>
            <div class="fb-map-actions">
                <button type="button" class="fb-full-details-btn" id="fb-full-details-btn">
                    View Full Details
                </button>
                <a class="fb-directions-btn" href="<?= htmlspecialchars($directionsUrl) ?>" target="_blank" rel="noopener">
                    Get Direction
                </a>
            </div>
        </article>
    <?php endif; ?>
</section>

<div class="fb-details-modal" id="fb-details-modal" hidden>
    <section class="fb-details-dialog" role="dialog" aria-modal="true" aria-labelledby="fb-details-title">
        <button type="button" class="fb-details-close" id="fb-details-close" aria-label="Close details">
            <i class="fas fa-xmark"></i>
        </button>

        <header class="fb-details-header">
            <div>
                <span class="fb-details-kicker">Food Bank Information</span>
                <h2 id="fb-details-title">Food Bank</h2>
                <p id="fb-details-address">-</p>
            </div>
            <span class="fb-details-status" id="fb-details-status">-</span>
        </header>

        <div class="fb-details-grid">
            <div class="fb-details-section">
                <h3>Organization Details</h3>
                <dl>
                    <div><dt>Food Bank ID</dt><dd id="fb-details-id">-</dd></div>
                    <div><dt>Operating Days</dt><dd id="fb-details-days">-</dd></div>
                    <div><dt>Hours</dt><dd id="fb-details-hours">-</dd></div>
                    <div><dt>Date Registered</dt><dd id="fb-details-registered">-</dd></div>
                </dl>
            </div>

            <div class="fb-details-section">
                <h3>Public Contact</h3>
                <dl>
                    <div><dt>Email</dt><dd id="fb-details-email">-</dd></div>
                    <div><dt>Phone</dt><dd id="fb-details-phone">-</dd></div>
                    <div><dt>Total Donations</dt><dd id="fb-details-donations">-</dd></div>
                </dl>
            </div>

            <div class="fb-details-section fb-details-section--full">
                <h3>Manager Information</h3>
                <dl>
                    <div><dt>Name</dt><dd id="fb-details-manager">-</dd></div>
                    <div><dt>Email</dt><dd id="fb-details-manager-email">-</dd></div>
                    <div><dt>Phone</dt><dd id="fb-details-manager-phone">-</dd></div>
                </dl>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    const headerWrapper = document.querySelector('.header-wrapper');
    if (headerWrapper) {
        headerWrapper.style.display = 'none';
    }

    const root = document.querySelector('.fb-map-view');
    const bank = JSON.parse(root.dataset.bank || '{}');
    const modal = document.getElementById('fb-details-modal');
    const detailsBtn = document.getElementById('fb-full-details-btn');
    const closeBtn = document.getElementById('fb-details-close');

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }

    function openDetails() {
        setText('fb-details-title', bank.name);
        setText('fb-details-address', bank.address);
        setText('fb-details-status', bank.status);
        setText('fb-details-id', bank.custom_id);
        setText('fb-details-days', bank.days);
        setText('fb-details-hours', bank.hours);
        setText('fb-details-registered', bank.registered);
        setText('fb-details-email', bank.email);
        setText('fb-details-phone', bank.phone);
        setText('fb-details-donations', String(bank.donations || 0));
        setText('fb-details-manager', bank.manager);
        setText('fb-details-manager-email', bank.manager_email);
        setText('fb-details-manager-phone', bank.manager_phone);
        modal.hidden = false;
        document.body.classList.add('fb-details-open');
    }

    function closeDetails() {
        modal.hidden = true;
        document.body.classList.remove('fb-details-open');
    }

    if (detailsBtn) detailsBtn.addEventListener('click', openDetails);
    closeBtn.addEventListener('click', closeDetails);
    modal.addEventListener('click', event => {
        if (event.target === modal) closeDetails();
    });
})();

function initMap() {}
</script>
