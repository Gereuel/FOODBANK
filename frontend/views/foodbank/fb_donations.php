<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'FA') {
    http_response_code(401);
    exit('Unauthorized');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

function fb_donation_date(?string $date): string
{
    return $date ? date('M j, Y', strtotime($date)) : '-';
}

try {
    $stmt = $pdo->prepare("
        SELECT d.*, u.First_Name, u.Last_Name, a.Email, a.Phone_Number
        FROM FOOD_BANKS fb
        JOIN DONATIONS d ON d.FoodBank_ID = fb.FoodBank_ID
        JOIN ACCOUNTS a ON a.Account_ID = d.Donor_Account_ID
        JOIN USERS u ON u.User_ID = a.User_ID
        WHERE fb.Account_ID = ?
        ORDER BY d.Date_Donated DESC, d.Donation_ID DESC
    ");
    $stmt->execute([$_SESSION['Account_ID']]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Foodbank donations load error: ' . $e->getMessage());
    $donations = [];
}
?>

<section class="fb-page" aria-labelledby="fb-donations-title">
    <div class="fb-page-heading">
        <div>
            <h2 id="fb-donations-title">Donations Received</h2>
            <p>Donation records assigned to your food bank</p>
        </div>
    </div>

    <?php if (empty($donations)): ?>
        <div class="fb-empty-state"><i class="fas fa-hand-holding-heart"></i><p>No received donation records yet.</p></div>
    <?php else: ?>
        <div class="fb-table-wrap">
            <table class="fb-data-table">
                <thead>
                    <tr>
                        <th>Tracking</th>
                        <th>Donor</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td><?= htmlspecialchars($donation['Tracking_Number'] ?? ('DN-' . $donation['Donation_ID'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars(trim(($donation['First_Name'] ?? '') . ' ' . ($donation['Last_Name'] ?? ''))) ?></strong>
                                <span><?= htmlspecialchars($donation['Email'] ?? '') ?></span>
                            </td>
                            <td><?= htmlspecialchars($donation['Item_Type']) ?></td>
                            <td><?= htmlspecialchars($donation['Quantity_Description']) ?></td>
                            <td><span class="fb-status-pill fb-status-pill--<?= htmlspecialchars(strtolower(str_replace(' ', '-', $donation['Status']))) ?>"><?= htmlspecialchars($donation['Status']) ?></span></td>
                            <td><?= htmlspecialchars(fb_donation_date($donation['Date_Donated'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
