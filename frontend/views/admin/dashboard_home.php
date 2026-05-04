<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access.");
}

try {
    // ── Fetch KPIs ───────────────────────────────────────────
    $total_donations = $pdo->query("SELECT COUNT(*) FROM DONATIONS")->fetchColumn();
    $active_fb       = $pdo->query("SELECT COUNT(*) FROM FOOD_BANKS WHERE Org_Status = 'Active'")->fetchColumn();
    $total_users     = $pdo->query("SELECT COUNT(*) FROM ACCOUNTS")->fetchColumn();
    $pending_fb      = $pdo->query("SELECT COUNT(*) FROM FOOD_BANKS WHERE Verification_Status = 'Pending'")->fetchColumn();
    $new_users_7     = $pdo->query("SELECT COUNT(*) FROM ACCOUNTS WHERE Date_Created >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

    // ── Fetch Donation Trend (Last 7 Days) ───────────────────
    $stmt = $pdo->query("
        SELECT DATE(Date_Donated) as date_val, COUNT(*) as count 
        FROM DONATIONS 
        WHERE Date_Donated >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(Date_Donated)
        ORDER BY date_val ASC
    ");
    $trend_data = $stmt->fetchAll();
    
    $trend_labels = array_map(fn($r) => date('M j', strtotime($r['date_val'])), $trend_data);
    $trend_counts = array_column($trend_data, 'count');

    // ── Fetch Donation Type Distribution ─────────────────────
    $stmt_dist = $pdo->query("
        SELECT Item_Type, COUNT(*) as count 
        FROM DONATIONS 
        GROUP BY Item_Type
    ");
    $dist_data = $stmt_dist->fetchAll();
    $dist_labels = array_column($dist_data, 'Item_Type');
    $dist_counts = array_column($dist_data, 'count');

    // ── Fetch Recent Transactions ────────────────────────────
    $stmt_recent = $pdo->query("
        SELECT 
            d.Tracking_Number, 
            CONCAT(u.First_Name, ' ', u.Last_Name) as donor_name,
            d.Item_Type,
            d.Date_Donated,
            d.Status
        FROM DONATIONS d
        JOIN ACCOUNTS a ON d.Donor_Account_ID = a.Account_ID
        JOIN USERS u ON a.User_ID = u.User_ID
        ORDER BY d.Date_Donated DESC, d.Donation_Time DESC
        LIMIT 5
    ");
    $recent_donations = $stmt_recent->fetchAll();

    $status_classes = [
        'Pending' => 'badge-pending', 'In Transit' => 'badge-transit', 'Received' => 'badge-active', 'Cancelled' => 'badge-inactive'
    ];

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Reusing Reports CSS for consistent styling -->
<link rel="stylesheet" href="/foodbank/frontend/assets/css/pages/admin/reports.css">

<section class="content-area">
    <header class="page-header">
        <h2>Dashboard Overview</h2>
        <p>Monitor key metrics and system performance at a glance</p>
    </header>

    <!-- Stat Cards -->
    <div class="stat-row">
        <div class="stat-card" onclick="loadComponent('main-display', '/foodbank/frontend/views/admin/donations.php')" style="cursor:pointer;">
            <div class="label">Total Donations</div>
            <div class="value"><?= number_format($total_donations) ?></div>
        </div>
        <div class="stat-card" onclick="loadComponent('main-display', '/foodbank/frontend/views/admin/foodbanks.php')" style="cursor:pointer;">
            <div class="label">Active Food Banks</div>
            <div class="value green"><?= number_format($active_fb) ?></div>
        </div>
        <div class="stat-card" onclick="loadComponent('main-display', '/foodbank/frontend/views/admin/foodbanks.php')" style="cursor:pointer; <?= $pending_fb > 0 ? 'border-color: var(--red-main); background: #fff5f5;' : '' ?>">
            <div class="label">Pending Approval</div>
            <div class="value <?= $pending_fb > 0 ? 'rpt-value-red' : '' ?>"><?= number_format($pending_fb) ?></div>
            <?php if($pending_fb > 0): ?>
                <div style="font-size: 11px; color: var(--red-dark); margin-top: 4px; font-weight: 600;">Action Required</div>
            <?php endif; ?>
        </div>
        <div class="stat-card" onclick="loadComponent('main-display', '/foodbank/frontend/views/admin/user_management.php')" style="cursor:pointer;">
            <div class="label">System Users</div>
            <div class="value"><?= number_format($total_users) ?></div>
            <div style="font-size: 12px; color: var(--green-main); margin-top: 4px;">+<?= $new_users_7 ?> this week</div>
        </div>
    </div>

    <!-- Dashboard Charts -->
    <div class="rpt-chart-grid" style="margin-top: var(--spacing-xl);">
        <!-- Trend Chart -->
        <div class="table-card rpt-chart-card">
            <div class="rpt-chart-card-header">Recent Donation Activity (Last 7 Days)</div>
            <div class="rpt-chart-wrap" style="height: 350px;">
                <canvas id="dashDonationChart"></canvas>
            </div>
        </div>

        <!-- Distribution Chart -->
        <div class="table-card rpt-chart-card">
            <div class="rpt-chart-card-header">Donation Distribution</div>
            <div class="rpt-chart-wrap" style="height: 350px;">
                <canvas id="dashDistChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="table-card" style="margin-top: var(--spacing-xl);">
        <div class="table-toolbar" style="border:none;">
            <span class="rpt-table-title" style="font-size: var(--font-size-xl); font-weight: bold;">Recent Donation Reports</span>
            <button class="toolbar-btn" onclick="loadComponent('main-display', '/foodbank/frontend/views/admin/donations.php')">View All</button>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tracking #</th>
                        <th>Donor</th>
                        <th>Item Type</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_donations)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--gray-400);">No recent activity.</td></tr>
                    <?php else: ?>
                    <?php foreach ($recent_donations as $row): ?>
                    <tr>
                        <td style="font-family: var(--font-family-mono); font-size: 13px;"><?= $row['Tracking_Number'] ?></td>
                        <td><?= htmlspecialchars($row['donor_name']) ?></td>
                        <td><?= htmlspecialchars($row['Item_Type']) ?></td>
                        <td><?= date('M j, Y', strtotime($row['Date_Donated'])) ?></td>
                        <td>
                            <span class="badge <?= $status_classes[$row['Status']] ?? 'badge-pending' ?>">
                                <?= $row['Status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
window.initDashboard = function() {
    var DASH_DATA = {
        labels: <?= json_encode($trend_labels) ?>,
        counts: <?= json_encode($trend_counts) ?>,
        distLabels: <?= json_encode($dist_labels) ?>,
        distCounts: <?= json_encode($dist_counts) ?>
    };

    function renderDashboardCharts() {
        const ctxTrend = document.getElementById('dashDonationChart');
        const ctxDist  = document.getElementById('dashDistChart');

        // Destroy stale instance to prevent memory leaks in SPA
        const exTrend = Chart.getChart('dashDonationChart');
        if (exTrend) exTrend.destroy();
        const exDist = Chart.getChart('dashDistChart');
        if (exDist) exDist.destroy();

        // 1. Line Chart
        if (ctxTrend) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: DASH_DATA.labels,
                    datasets: [{
                        label: 'Donations',
                        data: DASH_DATA.counts,
                        borderColor: '#40916c',
                        backgroundColor: 'rgba(64, 145, 108, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#40916c',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e0e7e4' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 2. Doughnut Chart
        if (ctxDist) {
            new Chart(ctxDist, {
                type: 'doughnut',
                data: {
                    labels: DASH_DATA.distLabels,
                    datasets: [{
                        data: DASH_DATA.distCounts,
                        backgroundColor: ['#40916c', '#2d6a4f', '#52b788', '#74c69d', '#95d5b2', '#b7e4c7'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 12 }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    }

    // Load Chart.js if needed
    if (typeof Chart !== 'undefined') {
        renderDashboardCharts();
    } else {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.onload = renderDashboardCharts;
        document.head.appendChild(script);
    }
};
</script>