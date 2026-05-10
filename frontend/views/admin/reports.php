<?php
session_start();
require_once __DIR__ . '/../../../backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access.");
}

// ── Date range filter ─────────────────────────────────────────
$range  = $_GET['range'] ?? '30';
$days   = in_array($range, ['7','30','90','365']) ? (int)$range : 30;
$cutoff = date('Y-m-d', strtotime("-{$days} days"));

$range_label = match($days) {
    7   => 'Last 7 Days',
    30  => 'Last 30 Days',
    90  => 'Last 3 Months',
    365 => 'Last Year',
    default => 'Last 30 Days',
};

try {
    // ── Donation summary KPIs ─────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*)                         AS total_donations,
            COUNT(DISTINCT Donor_Account_ID) AS unique_donors,
            COUNT(DISTINCT FoodBank_ID)      AS active_foodbanks
        FROM DONATIONS
        WHERE Date_Donated >= ?
    ");
    $stmt->execute([$cutoff]);
    $donation_summary = $stmt->fetch();

    // ── Donations by type ─────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT Item_Type AS Donation_Type, COUNT(*) AS count
        FROM DONATIONS
        WHERE Date_Donated >= ?
        GROUP BY Item_Type
        ORDER BY count DESC
    ");
    $stmt->execute([$cutoff]);
    $donations_by_type = $stmt->fetchAll();

    // ── Donation trend (weekly) ───────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(Date_Donated, '%Y-%u') AS week_key,
            MIN(DATE(Date_Donated))            AS week_start,
            COUNT(*)                           AS count
        FROM DONATIONS
        WHERE Date_Donated >= ?
        GROUP BY week_key
        ORDER BY week_key ASC
    ");
    $stmt->execute([$cutoff]);
    $donation_trend = $stmt->fetchAll();

    // ── User activity ─────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*)                                                       AS total_users,
            SUM(CASE WHEN Date_Created >= ? THEN 1 ELSE 0 END)            AS new_users,
            SUM(CASE WHEN Status = 'Active'           THEN 1 ELSE 0 END)  AS active_users,
            SUM(CASE WHEN Status = 'Inactive'         THEN 1 ELSE 0 END)  AS disabled_users,
            SUM(CASE WHEN Account_Type = 'PA'         THEN 1 ELSE 0 END)  AS donors,
            SUM(CASE WHEN Account_Type IN ('AA','FA')  THEN 1 ELSE 0 END) AS admins
        FROM ACCOUNTS
    ");
    $stmt->execute([$cutoff]);
    $user_stats = $stmt->fetch();

    // ── Registrations per week ────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(Date_Created, '%Y-%u') AS week_key,
            MIN(DATE(Date_Created))            AS week_start,
            COUNT(*)                           AS count
        FROM ACCOUNTS
        WHERE Date_Created >= ?
        GROUP BY week_key
        ORDER BY week_key ASC
    ");
    $stmt->execute([$cutoff]);
    $reg_trend = $stmt->fetchAll();

    // ── Food bank activity ────────────────────────────────────
    $stmt = $pdo->query("
        SELECT
            fb.FoodBank_ID,
            fb.Organization_Name AS Name,
            fb.Physical_Address  AS Address,
            COUNT(d.Donation_ID) AS donations_received
        FROM FOOD_BANKS fb
        LEFT JOIN DONATIONS d ON d.FoodBank_ID = fb.FoodBank_ID
        GROUP BY fb.FoodBank_ID, fb.Organization_Name, fb.Physical_Address
        ORDER BY donations_received DESC
    ");
    $foodbank_inventory = $stmt->fetchAll();

    // ── Top donors ────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            CONCAT(u.First_Name, ' ', u.Last_Name) AS donor_name,
            a.Email,
            COUNT(d.Donation_ID) AS donation_count
        FROM DONATIONS d
        JOIN ACCOUNTS a ON a.Account_ID = d.Donor_Account_ID
        JOIN USERS    u ON u.User_ID    = a.User_ID
        WHERE d.Date_Donated >= ?
        GROUP BY d.Donor_Account_ID, donor_name, a.Email
        ORDER BY donation_count DESC
        LIMIT 5
    ");
    $stmt->execute([$cutoff]);
    $top_donors = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// ── Chart data ────────────────────────────────────────────────
$trend_labels = array_map(fn($r) => date('M j', strtotime($r['week_start'])), $donation_trend);
$trend_counts = array_column($donation_trend, 'count');
$reg_labels   = array_map(fn($r) => date('M j', strtotime($r['week_start'])), $reg_trend);
$reg_counts   = array_column($reg_trend, 'count');
$type_labels  = array_column($donations_by_type, 'Donation_Type');
$type_counts  = array_column($donations_by_type, 'count');

$max_donations = max(1, ...array_column($foodbank_inventory, 'donations_received'));
?>

<link rel="stylesheet" href="/foodbank/frontend/assets/css/pages/admin/reports.css">

<!-- Reports -->
<section class="content-area">
    <header class="page-header">
        <div class="rpt-header-title">
            <h2>Food Bank App Reports</h2>
            <p>Detailed performance analytics and system logs</p>
        </div>

        <div class="rpt-header-controls">
            <div class="filter-bar">
                <span class="filter-bar-label">Range:</span>
                <a href="#" class="range-btn <?= $range == '7' ? 'active' : '' ?>" data-range="7">7D</a>
                <a href="#" class="range-btn <?= $range == '30' ? 'active' : '' ?>" data-range="30">30D</a>
                <a href="#" class="range-btn <?= $range == '90' ? 'active' : '' ?>" data-range="90">90D</a>
                <a href="#" class="range-btn <?= $range == '365' ? 'active' : '' ?>" data-range="365">1Y</a>
            </div>

            <div class="rpt-export-row">
                <button class="toolbar-btn rpt-btn-csv" id="export-csv-btn">
                    <i class="fas fa-file-csv"></i> CSV Export
                </button>
                <button class="toolbar-btn rpt-btn-pdf" id="export-pdf-btn">
                    <i class="fas fa-file-pdf"></i> PDF Report
                </button>
            </div>
        </div>
    </header>

    <!-- Stat Cards -->
    <div class="stat-row">
        <div class="stat-card">
            <div class="label">Total Donations</div>
            <div class="value"><?= number_format($donation_summary['total_donations']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Unique Donors</div>
            <div class="value green"><?= number_format($donation_summary['unique_donors']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Active Food Banks</div>
            <div class="value"><?= number_format($donation_summary['active_foodbanks']) ?></div>
        </div>
    </div>

    <div class="rpt-section-divider"></div>

    <div class="rpt-chart-grid">
        <div class="table-card rpt-chart-card">
            <div class="rpt-chart-card-header">Donation Trend (Weekly)</div>
            <div class="rpt-chart-wrap"><canvas id="rptDonationTrendChart"></canvas></div>
        </div>
        <div class="table-card rpt-chart-card">
            <div class="rpt-chart-card-header">Donations by Type</div>
            <div class="rpt-chart-wrap"><canvas id="rptDonationTypeChart"></canvas></div>
        </div>
    </div>

    <div class="table-card rpt-table-spacing">
        <div class="table-toolbar rpt-table-toolbar-inner">
            <span class="rpt-table-title">Top Donors</span>
            <span class="badge badge-active"><?= htmlspecialchars($range_label) ?></span>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Donor</th>
                        <th>Email</th>
                        <th>Donations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_donors)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:var(--spacing-3xl);color:var(--gray-400);">
                            No donation data for this period.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($top_donors as $i => $d): ?>
                    <tr>
                        <td class="rpt-rank"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($d['donor_name']) ?></td>
                        <td style="color:var(--text-sub);"><?= htmlspecialchars($d['Email']) ?></td>
                        <td><span class="badge badge-active"><?= $d['donation_count'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-section-divider"></div>

    <div class="rpt-section-label">
        <span class="rpt-section-title">User Activity</span>
        <span class="badge badge-active">All Time + <?= htmlspecialchars($range_label) ?></span>
    </div>

    <div class="stat-row">
        <div class="stat-card">
            <div class="label">Total Accounts</div>
            <div class="value"><?= number_format($user_stats['total_users']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">New Registrations</div>
            <div class="value green"><?= number_format($user_stats['new_users']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Active Users</div>
            <div class="value"><?= number_format($user_stats['active_users']) ?></div>
        </div>
    </div>
    <div class="stat-row rpt-stat-row-second">
        <div class="stat-card">
            <div class="label">Inactive Accounts</div>
            <div class="value rpt-value-red"><?= number_format($user_stats['disabled_users']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Donors (PA)</div>
            <div class="value"><?= number_format($user_stats['donors']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Administrators</div>
            <div class="value"><?= number_format($user_stats['admins']) ?></div>
        </div>
    </div>

    <div class="rpt-chart-grid">
        <div class="table-card rpt-chart-card">
            <div class="rpt-chart-card-header">New Registrations (Weekly)</div>
            <div class="rpt-chart-wrap"><canvas id="rptRegTrendChart"></canvas></div>
        </div>
        <div class="table-card rpt-chart-card">
            <div class="rpt-chart-card-header">Account Status Breakdown</div>
            <div class="rpt-chart-wrap"><canvas id="rptUserStatusChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-section-divider"></div>

    <div class="rpt-section-label">
        <span class="rpt-section-title">Food Bank Activity</span>
        <span class="badge badge-active">All Time</span>
    </div>

    <div class="table-card">
        <div class="table-toolbar rpt-table-toolbar-inner">
            <span class="rpt-table-title">Donations Received per Food Bank</span>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Food Bank</th>
                        <th>Address</th>
                        <th>Donations</th>
                        <th style="min-width:200px;">Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($foodbank_inventory)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:var(--spacing-3xl);color:var(--gray-400);">
                            No food banks found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($foodbank_inventory as $i => $fb):
                        $pct = $max_donations > 0
                            ? round($fb['donations_received'] / $max_donations * 100)
                            : 0;
                    ?>
                    <tr>
                        <td class="rpt-rank"><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($fb['Name']) ?></strong></td>
                        <td style="color:var(--text-sub);font-size:var(--font-size-sm);">
                            <?= htmlspecialchars($fb['Address']) ?>
                        </td>
                        <td><span class="badge badge-active"><?= $fb['donations_received'] ?></span></td>
                        <td>
                            <div class="rpt-progress-wrap">
                                <div class="rpt-progress-bar-bg">
                                    <div class="rpt-progress-bar-fill" style="width:<?= $pct ?>%"></div>
                                </div>
                                <div class="rpt-progress-label">
                                    <span><?= $fb['donations_received'] ?> donations</span>
                                    <span><?= $pct ?>%</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════════
     SCRIPTS — single block, guaranteed execution order
════════════════════════════════════════════════════════════ -->
<script>
window.initReports = function () {

    // ── Chart data from PHP ───────────────────────────────────
    var RPT_DATA = {
        trendLabels : <?= json_encode($trend_labels) ?>,
        trendCounts : <?= json_encode($trend_counts) ?>,
        typeLabels  : <?= json_encode($type_labels)  ?>,
        typeCounts  : <?= json_encode($type_counts)  ?>,
        regLabels   : <?= json_encode($reg_labels)   ?>,
        regCounts   : <?= json_encode($reg_counts)   ?>,
        activeUsers : <?= (int)$user_stats['active_users']   ?>,
        inactiveUsers:<?= (int)$user_stats['disabled_users'] ?>,
    };

    // ── Range Selection ───────────────────────────────────────
    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const r = this.getAttribute('data-range');
            loadComponent('main-display', `/foodbank/frontend/views/admin/reports.php?range=${r}`);
        });
    });

    // ── CSV Export ────────────────────────────────────────────
    document.getElementById('export-csv-btn').addEventListener('click', function () {
        const rows = [
            ['Food Bank App — Reports Export', <?= json_encode($range_label) ?>, <?= json_encode(date('Y-m-d')) ?>],
            [],
            ['DONATION SUMMARY'],
            ['Total Donations',   <?= (int)$donation_summary['total_donations'] ?>],
            ['Unique Donors',     <?= (int)$donation_summary['unique_donors'] ?>],
            ['Active Food Banks', <?= (int)$donation_summary['active_foodbanks'] ?>],
            [],
            ['TOP DONORS', 'Email', 'Donations'],
            <?php foreach ($top_donors as $d): ?>
            [<?= json_encode($d['donor_name']) ?>, <?= json_encode($d['Email']) ?>, <?= (int)$d['donation_count'] ?>],
            <?php endforeach; ?>
            [],
            ['USER ACTIVITY'],
            ['Total Accounts',    <?= (int)$user_stats['total_users'] ?>],
            ['New Registrations', <?= (int)$user_stats['new_users'] ?>],
            ['Active Users',      <?= (int)$user_stats['active_users'] ?>],
            ['Inactive Accounts', <?= (int)$user_stats['disabled_users'] ?>],
            ['Donors (PA)',       <?= (int)$user_stats['donors'] ?>],
            ['Administrators',    <?= (int)$user_stats['admins'] ?>],
            [],
            ['FOOD BANK ACTIVITY', 'Address', 'Donations Received'],
            <?php foreach ($foodbank_inventory as $fb): ?>
            [<?= json_encode($fb['Name']) ?>, <?= json_encode($fb['Address']) ?>, <?= (int)$fb['donations_received'] ?>],
            <?php endforeach; ?>
        ];
        var csv  = rows.map(r => r.map(v => {
            return '"' + String(v).replace(/"/g, '""') + '"';
        }).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const a    = Object.assign(document.createElement('a'), {
            href: URL.createObjectURL(blob),
            download: `foodbank-report-<?= date('Y-m-d') ?>.csv`
        });
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });

    // ── PDF Export ────────────────────────────────────────────
    document.getElementById('export-pdf-btn').addEventListener('click', function () {
        window.print();
    });

    // ── Chart rendering ───────────────────────────────────────
    function renderCharts() {
        const C_GREEN      = '#40916c';
        const C_GREEN_PALE = 'rgba(64,145,108,.12)';
        const C_ORANGE     = '#f57c00';
        const C_TEAL       = '#00897b';
        const C_TEAL_PALE  = 'rgba(0,137,123,.12)';
        const C_RED        = '#dc2626';
        const C_MUTED      = '#6b7280';
        const C_BORDER     = '#e0e7e4';

        Chart.defaults.font.family = '"DM Sans", sans-serif';
        Chart.defaults.color       = C_MUTED;

        const grid = { color: C_BORDER, drawBorder: false };

        // Destroy any stale instances from previous AJAX load
        ['rptDonationTrendChart','rptDonationTypeChart','rptRegTrendChart','rptUserStatusChart']
            .forEach(id => { const c = Chart.getChart(id); if (c) c.destroy(); });

        // Donation Trend
        new Chart(document.getElementById('rptDonationTrendChart'), {
            type: 'line',
            data: {
                labels: RPT_DATA.trendLabels,
                datasets: [{
                    label: 'Donations',
                    data: RPT_DATA.trendCounts,
                    tension: 0.42, fill: true,
                    backgroundColor: C_GREEN_PALE,
                    borderColor: C_GREEN, borderWidth: 2.5,
                    pointBackgroundColor: C_GREEN, pointRadius: 4, pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid, ticks: { maxTicksLimit: 7 } },
                    y: { grid, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Donations by Type
        new Chart(document.getElementById('rptDonationTypeChart'), {
            type: 'doughnut',
            data: {
                labels: RPT_DATA.typeLabels,
                datasets: [{
                    data: RPT_DATA.typeCounts,
                    backgroundColor: [C_GREEN, C_ORANGE, C_TEAL, C_RED, '#8e24aa', '#0277bd', '#558b2f'],
                    borderWidth: 2, borderColor: '#fff', hoverOffset: 8,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } } },
                cutout: '62%',
            }
        });

        // Registrations
        new Chart(document.getElementById('rptRegTrendChart'), {
            type: 'bar',
            data: {
                labels: RPT_DATA.regLabels,
                datasets: [{
                    label: 'Registrations',
                    data: RPT_DATA.regCounts,
                    backgroundColor: C_TEAL_PALE,
                    borderColor: C_TEAL, borderWidth: 2, borderRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // User Status
        new Chart(document.getElementById('rptUserStatusChart'), {
            type: 'pie',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: [RPT_DATA.activeUsers, RPT_DATA.inactiveUsers],
                    backgroundColor: [C_GREEN, C_RED],
                    borderWidth: 3, borderColor: '#fff', hoverOffset: 8,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } } },
            }
        });
    }

    // ── Load Chart.js then render ─────────────────────────────
    // If Chart.js is already loaded (e.g. another page used it), render immediately.
    // Otherwise inject the script tag and render in its onload callback.
    if (typeof Chart !== 'undefined') {
        renderCharts();
    } else {
        var script    = document.createElement('script');
        script.src    = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.onload = renderCharts;
        script.onerror = function () {
            console.error('reports.php: Failed to load Chart.js from CDN.');
        };
        document.head.appendChild(script);
    }
};
</script>