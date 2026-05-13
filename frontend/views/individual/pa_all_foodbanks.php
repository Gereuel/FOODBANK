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

    $stmt = $pdo->prepare("
        SELECT
            fb.FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Public_Phone,
            fb.Time_Open,
            fb.Time_Close,
            fb.Operating_Days,
            CASE WHEN fav.Favorite_ID IS NULL THEN 0 ELSE 1 END AS is_favourite
        FROM FOOD_BANKS fb
        LEFT JOIN PA_FOOD_BANK_FAVORITES fav
          ON fav.FoodBank_ID = fb.FoodBank_ID
         AND fav.Account_ID = ?
        WHERE fb.Verification_Status = 'Approved'
          AND fb.Org_Status = 'Active'
        ORDER BY is_favourite DESC, fb.Organization_Name ASC
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $foodBanks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('All food banks load error: ' . $e->getMessage());
    $foodBanks = [];
}

function allFbDayIndex(string $day): ?int {
    $key = substr(strtolower(trim($day)), 0, 3);
    $map = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
    return $map[$key] ?? null;
}

function allFbDayInRange(int $today, int $start, int $end): bool {
    return $start <= $end ? ($today >= $start && $today <= $end) : ($today >= $start || $today <= $end);
}

function allFbOperatesToday(?string $operatingDays): bool {
    $days = strtolower(trim((string) $operatingDays));
    if ($days === '') return true;
    if (preg_match('/\b(daily|everyday|every day)\b/', $days)) return true;

    $today = (int) date('N');
    $dayPattern = '(mon(?:day)?|tue(?:sday)?|tues(?:day)?|wed(?:nesday)?|thu(?:rsday)?|thur(?:sday)?|thurs(?:day)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)';
    preg_match_all('/' . $dayPattern . '(?:\s*(?:-|to)\s*' . $dayPattern . ')?/i', $days, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $start = allFbDayIndex($match[1]);
        $end = isset($match[2]) && $match[2] !== '' ? allFbDayIndex($match[2]) : $start;
        if ($start !== null && $end !== null && allFbDayInRange($today, $start, $end)) return true;
    }

    return false;
}

function allFbTimeInRange(?string $timeOpen, ?string $timeClose): bool {
    if (!$timeOpen || !$timeClose) return false;

    $now = strtotime(date('H:i:s'));
    $open = strtotime($timeOpen);
    $close = strtotime($timeClose);

    if ($open === false || $close === false) return false;

    return $open <= $close
        ? ($now >= $open && $now <= $close)
        : ($now >= $open || $now <= $close);
}

function allFbStatus(?string $timeOpen, ?string $timeClose, ?string $operatingDays): string {
    if (!$timeOpen || !$timeClose || !allFbOperatesToday($operatingDays)) return 'Closed';
    return allFbTimeInRange($timeOpen, $timeClose) ? 'Open Now' : 'Closed';
}

function allFbShortAddress(?string $address, int $limit = 78): string {
    $address = trim((string) $address);
    return strlen($address) <= $limit ? $address : rtrim(substr($address, 0, $limit - 3), " ,.") . '...';
}
?>

<section class="all-foodbanks-page">
    <div class="all-foodbanks-header">
        <div>
            <h2>All Food Banks</h2>
            <p>Search and save food banks in your community</p>
        </div>
        <button type="button" class="settings-back-btn nav-link" data-target="/foodbank/frontend/views/individual/pa_foodbanks.php">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </button>
    </div>

    <div class="foodbank-search">
        <i class="fas fa-search"></i>
        <input type="search" id="all-foodbanks-search" placeholder="Search food banks, addresses, or phone numbers">
    </div>

    <div class="foodbanks-grid all-foodbanks-grid" id="all-foodbanks-grid">
        <?php if (empty($foodBanks)): ?>
            <div class="empty-state">
                <i class="fas fa-store-slash"></i>
                <p>No food banks available yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($foodBanks as $bank):
                $status = allFbStatus($bank['Time_Open'], $bank['Time_Close'], $bank['Operating_Days']);
                $isOpen = $status === 'Open Now';
                $isFav = (bool) $bank['is_favourite'];
                $hours = date('g:i A', strtotime($bank['Time_Open'])) . ' - ' . date('g:i A', strtotime($bank['Time_Close']));
                $phone = !empty($bank['Public_Phone']) ? $bank['Public_Phone'] : 'N/A';
                $searchText = strtolower($bank['Organization_Name'] . ' ' . $bank['Physical_Address'] . ' ' . $phone);
            ?>
                <div class="foodbank-card" data-search="<?= htmlspecialchars($searchText) ?>">
                    <div class="foodbank-card__header">
                        <h4 class="foodbank-name"><?= htmlspecialchars($bank['Organization_Name']) ?></h4>
                        <button
                            class="fav-btn <?= $isFav ? 'fav-btn--active' : '' ?>"
                            aria-label="Save to favorites"
                            data-id="<?= (int) $bank['FoodBank_ID'] ?>">
                            <i class="<?= $isFav ? 'fas' : 'far' ?> fa-heart"></i>
                        </button>
                    </div>

                    <div class="foodbank-card__meta">
                        <span class="status-badge <?= $isOpen ? 'status-badge--open' : 'status-badge--closed' ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                        <span class="distance" title="<?= htmlspecialchars($bank['Physical_Address']) ?>">
                            <i class="fas fa-location-dot"></i>
                            <span><?= htmlspecialchars(allFbShortAddress($bank['Physical_Address'])) ?></span>
                        </span>
                    </div>

                    <div class="foodbank-card__details">
                        <div class="detail-row"><i class="fas fa-clock"></i><span><?= htmlspecialchars($hours) ?></span></div>
                        <div class="detail-row"><i class="fas fa-phone"></i><span><?= htmlspecialchars($phone) ?></span></div>
                    </div>

                    <button
                        class="view-details-btn nav-link"
                        data-target="/foodbank/frontend/views/individual/sections/fb_map.php?id=<?= (int) $bank['FoodBank_ID'] ?>">
                        View Details
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="empty-state all-foodbanks-empty" id="all-foodbanks-empty" hidden>
        <i class="fas fa-search"></i>
        <p>No food banks match your search.</p>
    </div>
</section>

<script>
(function () {
    const input = document.getElementById('all-foodbanks-search');
    const cards = Array.from(document.querySelectorAll('#all-foodbanks-grid .foodbank-card'));
    const empty = document.getElementById('all-foodbanks-empty');

    if (!input) return;

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        let visible = 0;

        cards.forEach(card => {
            const match = !query || (card.dataset.search || '').includes(query);
            card.hidden = !match;
            if (match) visible++;
        });

        if (empty) empty.hidden = visible > 0;
    });
})();
</script>
