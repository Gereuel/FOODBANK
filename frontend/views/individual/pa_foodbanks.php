<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    exit('Unauthorized');
}

if (!isset($pdo)) {
    require_once __DIR__ . '/../../../backend/config/database.php';
}

// pa_foodbanks.php
// Foodbanks Home Page — injected into #pa-main-content by pa-app.js
// Requires: $pdo (set in pa_index.php), $donor (logged-in donor row)

// ── 1. Stats ─────────────────────────────────────────────────
try {
    // Count approved food banks
    $stmtBanks = $pdo->query("
        SELECT COUNT(*) AS total
        FROM FOOD_BANKS
        WHERE Verification_Status = 'Approved'
          AND Org_Status = 'Active'
    ");
    $activeBanks = $stmtBanks->fetchColumn() ?: 0;

    // Count all donor accounts (PA)
    $stmtDonors = $pdo->query("
        SELECT COUNT(*) AS total
        FROM ACCOUNTS
        WHERE Account_Type = 'PA'
          AND Status = 'Active'
    ");
    $totalDonors = $stmtDonors->fetchColumn() ?: 0;

    // Count all donations (non-cancelled)
    $stmtDonations = $pdo->query("
        SELECT COUNT(*) AS total
        FROM DONATIONS
        WHERE Status != 'Cancelled'
    ");
    $totalDonations = $stmtDonations->fetchColumn() ?: 0;

} catch (PDOException $e) {
    $activeBanks   = 0;
    $totalDonors   = 0;
    $totalDonations = 0;
}

// ── 2. Nearby Food Banks ──────────────────────────────────────
// "Nearby" = all approved+active banks for now.
// If you add lat/lng columns later, swap in a Haversine query.
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

    $stmtNearby = $pdo->prepare("
        SELECT
            fb.FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Public_Phone,
            fb.Time_Open,
            fb.Time_Close,
            fb.Operating_Days,
            fb.Verification_Status,
            fb.Org_Status,
            CASE WHEN fav.Favorite_ID IS NULL THEN 0 ELSE 1 END AS is_favourite

        FROM FOOD_BANKS fb
        LEFT JOIN PA_FOOD_BANK_FAVORITES fav
          ON fav.FoodBank_ID = fb.FoodBank_ID
         AND fav.Account_ID = ?
        WHERE fb.Verification_Status = 'Approved'
          AND fb.Org_Status = 'Active'
        ORDER BY is_favourite DESC, fb.Date_Registered DESC
        LIMIT 3
    ");
    $stmtNearby->execute([$_SESSION['Account_ID']]);
    $nearbyBanks = $stmtNearby->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $nearbyBanks = [];
}

// ── 3. Helper: format large numbers ──────────────────────────
function formatStatNumber(int $n): string {
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M+';
    if ($n >= 1_000)     return round($n / 1_000, 1) . 'k+';
    return $n . '+';
}

// ── 4. Helper: is the bank open right now? ───────────────────
function dayIndexFromText(string $day): ?int {
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

function dayIsInRange(int $today, int $start, int $end): bool {
    if ($start <= $end) {
        return $today >= $start && $today <= $end;
    }

    return $today >= $start || $today <= $end;
}

function isOperatingToday(?string $operatingDays): bool {
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
        $start = dayIndexFromText($match[1]);
        $end = isset($match[2]) && $match[2] !== '' ? dayIndexFromText($match[2]) : $start;

        if ($start !== null && $end !== null && dayIsInRange($today, $start, $end)) {
            return true;
        }
    }

    return false;
}

function getBankStatus(?string $timeOpen, ?string $timeClose, ?string $operatingDays = null): string {
    if (!isOperatingToday($operatingDays)) {
        return 'Closed';
    }

    if (!$timeOpen || !$timeClose) {
        return 'Closed';
    }

    $now = strtotime(date('H:i:s'));
    return ($now >= strtotime($timeOpen) && $now <= strtotime($timeClose)) ? 'Open Now' : 'Closed';
}

function shortenAddress(?string $address, int $limit = 78): string {
    $address = trim((string) $address);
    if (strlen($address) <= $limit) {
        return $address;
    }

    return rtrim(substr($address, 0, $limit - 3), " ,.") . '...';
}
?>

<!-- ── Stats Row ─────────────────────────────────────────────── -->
<div class="stats-row">

    <div class="stat-card">
        <div class="stat-icon stat-icon--green">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stat-value"><?php echo formatStatNumber((int)$activeBanks); ?></div>
        <div class="stat-label">Active Banks</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon--orange">
            <i class="fas fa-arrow-trend-up"></i>
        </div>
        <div class="stat-value"><?php echo formatStatNumber((int)$totalDonors); ?></div>
        <div class="stat-label">Donors</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon--red">
            <i class="fas fa-hand-holding-heart"></i>
        </div>
        <div class="stat-value"><?php echo formatStatNumber((int)$totalDonations); ?></div>
        <div class="stat-label">Donations</div>
    </div>

</div>

<!-- ── Nearby Food Banks ──────────────────────────────────────── -->
<div class="section-header">
    <div>
        <h3 class="section-title">Nearby Food Banks</h3>
        <p class="section-subtitle">Supporting your community</p>
    </div>
    <a href="#" class="view-all-link nav-link"
       data-target="/foodbank/frontend/views/individual/pa_all_foodbanks.php">
        View All
    </a>
</div>

<div class="foodbanks-grid" id="foodbanks-grid">

    <?php if (empty($nearbyBanks)): ?>
        <div class="empty-state">
            <i class="fas fa-store-slash"></i>
            <p>No food banks available in your area yet.</p>
        </div>

    <?php else: ?>
        <?php foreach ($nearbyBanks as $bank):
            $status     = getBankStatus($bank['Time_Open'], $bank['Time_Close'], $bank['Operating_Days']);
            $isOpen     = $status === 'Open Now';
            $isFav      = (bool) $bank['is_favourite'];
            $timeOpen   = date('g:i A', strtotime($bank['Time_Open']));
            $timeClose  = date('g:i A', strtotime($bank['Time_Close']));
            $hours      = $timeOpen . ' - ' . $timeClose;
            $phone      = !empty($bank['Public_Phone']) ? $bank['Public_Phone'] : 'N/A';
        ?>
        <div class="foodbank-card">

            <div class="foodbank-card__header">
                <h4 class="foodbank-name">
                    <?php echo htmlspecialchars($bank['Organization_Name']); ?>
                </h4>
                <button
                    class="fav-btn <?php echo $isFav ? 'fav-btn--active' : ''; ?>"
                    aria-label="Save to favourites"
                    data-id="<?php echo (int)$bank['FoodBank_ID']; ?>">
                    <i class="<?php echo $isFav ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
            </div>

            <div class="foodbank-card__meta">
                <span class="status-badge <?php echo $isOpen ? 'status-badge--open' : 'status-badge--closed'; ?>">
                    <?php echo $status; ?>
                </span>
                <span class="distance" title="<?php echo htmlspecialchars($bank['Physical_Address']); ?>">
                    <i class="fas fa-location-dot"></i>
                    <span><?php echo htmlspecialchars(shortenAddress($bank['Physical_Address'])); ?></span>
                </span>
            </div>

            <div class="foodbank-card__details">
                <div class="detail-row">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($hours); ?></span>
                </div>
                <div class="detail-row">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($phone); ?></span>
                </div>
            </div>

            <button
                class="view-details-btn nav-link"
                data-target="/foodbank/frontend/views/individual/sections/fb_map.php?id=<?php echo (int)$bank['FoodBank_ID']; ?>">
                View Details
            </button>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
