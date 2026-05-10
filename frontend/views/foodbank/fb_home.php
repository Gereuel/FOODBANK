<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
    http_response_code(401);
    exit('Unauthorized');
}

require_once __DIR__ . '/../../../backend/config/database.php';

function fb_home_date(?string $date): string
{
    return $date ? date('M j, Y', strtotime($date)) : '-';
}

try {
    $stmtBank = $pdo->prepare("SELECT * FROM FOOD_BANKS WHERE Account_ID = ? LIMIT 1");
    $stmtBank->execute([$_SESSION['Account_ID']]);
    $bank = $stmtBank->fetch(PDO::FETCH_ASSOC);
    $foodBankId = (int) ($bank['FoodBank_ID'] ?? 0);

    $stats = ['received' => 0, 'pending' => 0, 'donors' => 0, 'messages' => 0];
    $recentDonations = [];

    if ($foodBankId > 0) {
        $stmtStats = $pdo->prepare("
            SELECT
                SUM(CASE WHEN Status = 'Received' THEN 1 ELSE 0 END) AS received,
                SUM(CASE WHEN Status IN ('Pending', 'In Transit') THEN 1 ELSE 0 END) AS pending,
                COUNT(DISTINCT Donor_Account_ID) AS donors
            FROM DONATIONS
            WHERE FoodBank_ID = ?
        ");
        $stmtStats->execute([$foodBankId]);
        $stats = array_merge($stats, $stmtStats->fetch(PDO::FETCH_ASSOC) ?: []);

        $stmtRecent = $pdo->prepare("
            SELECT d.*, u.First_Name, u.Last_Name
            FROM DONATIONS d
            JOIN ACCOUNTS a ON a.Account_ID = d.Donor_Account_ID
            JOIN USERS u ON u.User_ID = a.User_ID
            WHERE d.FoodBank_ID = ?
            ORDER BY d.Date_Donated DESC, d.Donation_ID DESC
            LIMIT 5
        ");
        $stmtRecent->execute([$foodBankId]);
        $recentDonations = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtMessages = $pdo->prepare("SELECT COUNT(*) FROM MESSAGES WHERE Receiver_Account_ID = ? AND Is_Read = 0");
    $stmtMessages->execute([$_SESSION['Account_ID']]);
    $stats['messages'] = (int) $stmtMessages->fetchColumn();
} catch (PDOException $e) {
    error_log('Foodbank dashboard load error: ' . $e->getMessage());
    $bank = null;
    $stats = ['received' => 0, 'pending' => 0, 'donors' => 0, 'messages' => 0];
    $recentDonations = [];
}
?>

<section class="fb-page fb-home" aria-labelledby="fb-home-title">
    <div class="fb-page-heading">
        <div>
            <h2 id="fb-home-title">Dashboard</h2>
            <p>Overview of your food bank activity</p>
        </div>
        <span class="fb-status-pill"><?= htmlspecialchars($bank['Verification_Status'] ?? 'Pending') ?></span>
    </div>

    <div class="fb-stat-grid">
        <article class="fb-stat-card"><i class="fas fa-box-open"></i><strong><?= (int) $stats['received'] ?></strong><span>Received Donations</span></article>
        <article class="fb-stat-card"><i class="fas fa-truck"></i><strong><?= (int) $stats['pending'] ?></strong><span>Pending / In Transit</span></article>
        <article class="fb-stat-card"><i class="fas fa-user-group"></i><strong><?= (int) $stats['donors'] ?></strong><span>Donors</span></article>
        <article class="fb-stat-card"><i class="far fa-comment-dots"></i><strong><?= (int) $stats['messages'] ?></strong><span>Unread Messages</span></article>
    </div>

    <div class="fb-home-grid">
        <section class="fb-panel">
            <div class="fb-panel-heading">
                <h3>Food Bank Profile</h3>
            </div>
            <dl class="fb-info-list">
                <div><dt>Name</dt><dd><?= htmlspecialchars($bank['Organization_Name'] ?? 'Not set') ?></dd></div>
                <div><dt>Food Bank ID</dt><dd><?= htmlspecialchars($bank['Custom_FoodBank_ID'] ?? '-') ?></dd></div>
                <div><dt>Public Email</dt><dd><?= htmlspecialchars($bank['Public_Email'] ?? '-') ?></dd></div>
                <div><dt>Public Phone</dt><dd><?= htmlspecialchars($bank['Public_Phone'] ?? '-') ?></dd></div>
                <div><dt>Hours</dt><dd><?= htmlspecialchars(($bank['Time_Open'] ?? '-') . ' - ' . ($bank['Time_Close'] ?? '-')) ?></dd></div>
                <div><dt>Operating Days</dt><dd><?= htmlspecialchars($bank['Operating_Days'] ?? '-') ?></dd></div>
                <div class="is-full"><dt>Address</dt><dd><?= htmlspecialchars($bank['Physical_Address'] ?? '-') ?></dd></div>
            </dl>
        </section>

        <section class="fb-panel">
            <div class="fb-panel-heading">
                <h3>Recent Donations</h3>
            </div>
            <?php if (empty($recentDonations)): ?>
                <div class="fb-empty-state"><i class="fas fa-hand-holding-heart"></i><p>No donations received yet.</p></div>
            <?php else: ?>
                <div class="fb-mini-list">
                    <?php foreach ($recentDonations as $donation): ?>
                        <article>
                            <strong><?= htmlspecialchars($donation['Item_Type']) ?></strong>
                            <span><?= htmlspecialchars(trim(($donation['First_Name'] ?? '') . ' ' . ($donation['Last_Name'] ?? ''))) ?></span>
                            <small><?= htmlspecialchars($donation['Status']) ?> - <?= htmlspecialchars(fb_home_date($donation['Date_Donated'])) ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
