<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
    http_response_code(401);
    exit('Unauthorized');
}

require_once __DIR__ . '/../../../backend/config/database.php';

function fb_donor_date(?string $date): string
{
    return $date ? date('M j, Y', strtotime($date)) : 'No donations yet';
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
            u.Address,
            u.Profile_Picture_URL,
            COUNT(d.Donation_ID) AS Donation_Count,
            MAX(d.Date_Donated) AS Last_Donation_Date
        FROM FOOD_BANKS fb
        JOIN DONATIONS d ON d.FoodBank_ID = fb.FoodBank_ID AND d.Status != 'Cancelled'
        JOIN ACCOUNTS a ON a.Account_ID = d.Donor_Account_ID
        JOIN USERS u ON u.User_ID = a.User_ID
        WHERE fb.Account_ID = ?
        GROUP BY a.Account_ID, a.Custom_App_ID, a.Email, a.Phone_Number, u.First_Name, u.Middle_Name, u.Last_Name, u.Address, u.Profile_Picture_URL
        ORDER BY Donation_Count DESC, Last_Donation_Date DESC
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Foodbank donors load error: ' . $e->getMessage());
    $donors = [];
}
?>

<section class="fb-page" aria-labelledby="fb-donors-title">
    <div class="fb-page-heading">
        <div>
            <h2 id="fb-donors-title">Donors</h2>
            <p>People who donated to your food bank</p>
        </div>
        <div class="fb-search">
            <i class="fas fa-search"></i>
            <input type="search" id="fb-donor-search" placeholder="Search donors">
        </div>
    </div>

    <?php if (empty($donors)): ?>
        <div class="fb-empty-state"><i class="fas fa-user-group"></i><p>No donors have donated to this food bank yet.</p></div>
    <?php else: ?>
        <div class="fb-card-grid" id="fb-donor-grid">
            <?php foreach ($donors as $donor):
                $name = trim(implode(' ', array_filter([$donor['First_Name'], $donor['Middle_Name'], $donor['Last_Name']])));
                $avatar = $donor['Profile_Picture_URL'] ?: '/foodbank/frontend/assets/images/default-avatar.png';
            ?>
                <article class="fb-person-card" data-search="<?= htmlspecialchars(strtolower($name . ' ' . $donor['Email'] . ' ' . $donor['Custom_App_ID'])) ?>">
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($name) ?>" onerror="this.src='/foodbank/frontend/assets/images/default-avatar.png'">
                    <h3><?= htmlspecialchars($name) ?></h3>
                    <p><?= htmlspecialchars($donor['Email']) ?></p>
                    <dl>
                        <div><dt>Donations</dt><dd><?= (int) $donor['Donation_Count'] ?></dd></div>
                        <div><dt>Last Donation</dt><dd><?= htmlspecialchars(fb_donor_date($donor['Last_Donation_Date'])) ?></dd></div>
                        <div><dt>Phone</dt><dd><?= htmlspecialchars($donor['Phone_Number'] ?: '-') ?></dd></div>
                        <div><dt>ID</dt><dd><?= htmlspecialchars($donor['Custom_App_ID'] ?: '-') ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="fb-empty-state" id="fb-donor-search-empty" hidden><i class="fas fa-magnifying-glass"></i><p>No donors match your search.</p></div>
    <?php endif; ?>
</section>

<script>
(function () {
    const input = document.getElementById('fb-donor-search');
    const cards = Array.from(document.querySelectorAll('.fb-person-card'));
    const empty = document.getElementById('fb-donor-search-empty');
    if (!input) return;
    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        let count = 0;
        cards.forEach(card => {
            const visible = !query || card.dataset.search.includes(query);
            card.hidden = !visible;
            if (visible) count++;
        });
        if (empty) empty.hidden = count !== 0;
    });
})();
</script>
