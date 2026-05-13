<?php
session_start();
require_once __DIR__ . '/../../../backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access.");
}

$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset   = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');
$selectedStatuses = array_values(array_filter((array)($_GET['status'] ?? []), 'strlen'));
$selectedItemTypes = array_values(array_filter((array)($_GET['item_type'] ?? []), 'strlen'));

$where = [];
$filterParams = [];

if (!empty($selectedStatuses)) {
    $placeholders = implode(',', array_fill(0, count($selectedStatuses), '?'));
    $where[] = "d.Status IN ($placeholders)";
    $filterParams = array_merge($filterParams, $selectedStatuses);
}

if (!empty($selectedItemTypes)) {
    $placeholders = implode(',', array_fill(0, count($selectedItemTypes), '?'));
    $where[] = "d.Item_Type IN ($placeholders)";
    $filterParams = array_merge($filterParams, $selectedItemTypes);
}

if ($search !== '') {
    $where[] = "(
        d.Tracking_Number LIKE ?
        OR d.Item_Type LIKE ?
        OR d.Item_Description LIKE ?
        OR d.Quantity_Description LIKE ?
        OR d.Pickup_Address LIKE ?
        OR d.Status LIKE ?
        OR CONCAT(u.First_Name, ' ', u.Last_Name) LIKE ?
        OR a.Email LIKE ?
        OR a.Custom_App_ID LIKE ?
        OR fb.Organization_Name LIKE ?
    )";
    $searchLike = '%' . $search . '%';
    $filterParams = array_merge($filterParams, array_fill(0, 10, $searchLike));
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$queryParams = [];
if ($search !== '') {
    $queryParams['search'] = $search;
}
if (!empty($selectedStatuses)) {
    $queryParams['status'] = $selectedStatuses;
}
if (!empty($selectedItemTypes)) {
    $queryParams['item_type'] = $selectedItemTypes;
}
$filterQueryString = http_build_query($queryParams);
$pageHref = function (int $targetPage) use ($filterQueryString): string {
    $query = 'page=' . $targetPage;
    if ($filterQueryString !== '') {
        $query .= '&' . $filterQueryString;
    }
    return '?' . $query;
};

try {
    // Total count
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*)
        FROM DONATIONS d
        JOIN ACCOUNTS a  ON d.Donor_Account_ID = a.Account_ID
        JOIN USERS u     ON a.User_ID = u.User_ID
        JOIN FOOD_BANKS fb ON d.FoodBank_ID = fb.FoodBank_ID
        " . $whereSql);
    $stmt_count->execute($filterParams);
    $total      = $stmt_count->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // Fetch donations with donor and food bank info
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
            -- Donor info
            u.First_Name, u.Middle_Name, u.Last_Name,
            u.Address AS Donor_Address,
            u.Birthdate,
            a.Email, a.Phone_Number, a.Custom_App_ID,
            -- Food bank info
            fb.Organization_Name,
            fb.Physical_Address AS FoodBank_Address,
            fb.FoodBank_ID,
            fb.Public_Phone AS FoodBank_Phone,
            COALESCE(fb.Manager_First_Name, mu.First_Name) AS Manager_First,
            COALESCE(fb.Manager_Last_Name, mu.Last_Name) AS Manager_Last,
            COALESCE(fb.Manager_Phone, mfa.Phone_Number) AS Manager_Phone,
            mfa.Custom_App_ID AS FoodBank_App_ID
        FROM DONATIONS d
        JOIN ACCOUNTS a  ON d.Donor_Account_ID = a.Account_ID
        JOIN USERS u     ON a.User_ID = u.User_ID
        JOIN FOOD_BANKS fb ON d.FoodBank_ID = fb.FoodBank_ID
        LEFT JOIN ACCOUNTS mfa ON fb.Account_ID = mfa.Account_ID
        LEFT JOIN USERS mu     ON mfa.User_ID = mu.User_ID
        {$whereSql}
        ORDER BY d.Date_Donated DESC
        LIMIT ? OFFSET ?
    ");
    $paramIndex = 1;
    foreach ($filterParams as $filterParam) {
        $stmt->bindValue($paramIndex++, $filterParam);
    }
    $stmt->bindValue($paramIndex++, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex,   $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $donations = $stmt->fetchAll();

    // Fetch donors for Add modal dropdown
    $stmt_donors = $pdo->query("
        SELECT a.Account_ID, a.Custom_App_ID, u.First_Name, u.Last_Name
        FROM ACCOUNTS a JOIN USERS u ON a.User_ID = u.User_ID
        WHERE a.Account_Type = 'PA'
        ORDER BY u.First_Name
    ");
    $donors = $stmt_donors->fetchAll();

    // Fetch food banks for Add modal dropdown
    $stmt_banks = $pdo->query("
        SELECT fb.FoodBank_ID, fb.Organization_Name
        FROM FOOD_BANKS fb
        WHERE fb.Verification_Status = 'Approved'
        ORDER BY fb.Organization_Name
    ");
    $foodbanks = $stmt_banks->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$status_classes = [
    'Pending'    => 'badge-pending',
    'In Transit' => 'badge-transit',
    'Received'   => 'badge-active',
    'Cancelled'  => 'badge-inactive',
];
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    <?php $msgs = ['donation_added' => 'Donation report added successfully.'];
    echo $msgs[$_GET['success']] ?? 'Action completed.'; ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error">
    <?php $msgs = [
        'donation_added'   => 'Donation report added successfully.',
        'donation_updated' => 'Donation report updated successfully.',
        'donation_deleted' => 'Donation report deleted successfully.',
        'missing_fields'   => 'Please fill in all required fields.',
        'invalid_data'     => 'Invalid data submitted.',
        'invalid_file'     => 'Invalid file type. Use JPG, PNG, or PDF.',
        'upload_failed'    => 'File upload failed. Please try again.',
        'db_error'         => 'A database error occurred. Please try again.',
    ];
    echo $msgs[$_GET['error']] ?? 'An unexpected error occurred.'; ?>
</div>
<?php endif; ?>

<section class="content-area">
    <header class="page-header">
        <h2>Donations Made by Donors</h2>
        <p>Track all donations made by donors to food banks.</p>
        <button class="btn-add" onclick="openAddDonationModal()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Donation Report
        </button>
    </header>

    <!-- Stat Cards -->
    <div class="stat-row">
        <div class="stat-card">
            <div class="label">Total Donations</div>
            <div class="value"><?= $total ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Received</div>
            <div class="value green">
                <?php
                $stmt_r = $pdo->query("SELECT COUNT(*) FROM DONATIONS WHERE Status='Received'");
                echo $stmt_r->fetchColumn();
                ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">In Transit</div>
            <div class="value">
                <?php
                $stmt_t = $pdo->query("SELECT COUNT(*) FROM DONATIONS WHERE Status='In Transit'");
                echo $stmt_t->fetchColumn();
                ?>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">

        <!-- Toolbar -->
        <div class="table-toolbar" data-server-filter-url="/frontend/views/admin/donations.php">
            <div class="toolbar-search">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="search-input" placeholder="Search donations..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Status Filter -->
            <div class="toolbar-filter-wrap">
                <button class="toolbar-btn" id="filter-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Status
                    <span class="filter-badge" id="filter-badge" style="display:none;"></span>
                </button>
                <div class="filter-dropdown" id="filter-dropdown">
                    <div class="filter-section">
                        <label class="filter-label">Status</label>
                        <div class="filter-options">
                            <label class="filter-option"><input type="checkbox" name="status" value="Pending" <?= in_array('Pending', $selectedStatuses, true) ? 'checked' : '' ?>> Pending</label>
                            <label class="filter-option"><input type="checkbox" name="status" value="In Transit" <?= in_array('In Transit', $selectedStatuses, true) ? 'checked' : '' ?>> In Transit</label>
                            <label class="filter-option"><input type="checkbox" name="status" value="Received" <?= in_array('Received', $selectedStatuses, true) ? 'checked' : '' ?>> Received</label>
                            <label class="filter-option"><input type="checkbox" name="status" value="Cancelled" <?= in_array('Cancelled', $selectedStatuses, true) ? 'checked' : '' ?>> Cancelled</label>
                        </div>
                    </div>
                    <div class="filter-section">
                        <label class="filter-label">Item Type</label>
                        <div class="filter-options">
                            <label class="filter-option"><input type="checkbox" name="item_type" value="Food Items" <?= in_array('Food Items', $selectedItemTypes, true) ? 'checked' : '' ?>> Food Items</label>
                            <label class="filter-option"><input type="checkbox" name="item_type" value="Clothing" <?= in_array('Clothing', $selectedItemTypes, true) ? 'checked' : '' ?>> Clothing</label>
                            <label class="filter-option"><input type="checkbox" name="item_type" value="Cash Donation" <?= in_array('Cash Donation', $selectedItemTypes, true) ? 'checked' : '' ?>> Cash Donation</label>
                            <label class="filter-option"><input type="checkbox" name="item_type" value="Medicine" <?= in_array('Medicine', $selectedItemTypes, true) ? 'checked' : '' ?>> Medicine</label>
                            <label class="filter-option"><input type="checkbox" name="item_type" value="Perishable Goods" <?= in_array('Perishable Goods', $selectedItemTypes, true) ? 'checked' : '' ?>> Perishable Goods</label>
                            <label class="filter-option"><input type="checkbox" name="item_type" value="Other" <?= in_array('Other', $selectedItemTypes, true) ? 'checked' : '' ?>> Other</label>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="filter-apply-btn" id="filter-apply-btn">Apply</button>
                        <button class="filter-reset-btn" id="filter-reset-btn">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Export -->
            <div class="toolbar-filter-wrap">
                <button class="toolbar-btn" id="export-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export
                </button>
                <div class="filter-dropdown" id="export-dropdown">
                    <div class="export-options">
                        <button class="export-option-btn" id="export-csv-btn">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            Export as CSV
                        </button>
                        <button class="export-option-btn" id="export-pdf-btn">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            Export as PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Show All -->
            <button class="toolbar-btn" id="show-all-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Show All
            </button>
        </div>

        <!-- Table -->
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Donor</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Food Bank</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donations as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['First_Name'] . ' ' . $d['Last_Name']) ?></td>
                    <td><?= htmlspecialchars($d['Item_Type']) ?></td>
                    <td><?= htmlspecialchars($d['Quantity_Description']) ?></td>
                    <td><?= htmlspecialchars(substr($d['Pickup_Address'], 0, 30)) . (strlen($d['Pickup_Address']) > 30 ? '...' : '') ?></td>
                    <td><?= date('M j, Y', strtotime($d['Date_Donated'])) ?></td>
                    <td><?= htmlspecialchars($d['Organization_Name']) ?></td>
                    <td>
                        <span class="badge <?= $status_classes[$d['Status']] ?? 'badge-pending' ?>">
                            <?= htmlspecialchars($d['Status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <!-- View Report -->
                            <button class="action-btn" title="View Report"
                                onclick="openDonationReport(<?= htmlspecialchars(json_encode($d)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <!-- Edit Status -->
                            <button class="action-btn" title="Edit"
                                onclick="openEditDonationModal(<?= htmlspecialchars(json_encode($d)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <!-- Delete -->
                            <button class="action-btn delete" title="Delete"
                                onclick="openDeleteDonationModal(<?= htmlspecialchars(json_encode($d)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    <path d="M10 11v6M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($donations)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding: var(--spacing-3xl); color: var(--gray-400);">
                        No donations found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <div class="table-footer">
            <div class="pagination-info">
                Showing <strong><?= $total > 0 ? ($offset + 1) : 0 ?>–<?= min($offset + count($donations), $total) ?></strong>
                of <strong><?= $total ?></strong> donations
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars($pageHref($page - 1)) ?>" class="page-btn">Previous</a>
                <?php else: ?>
                    <button class="page-btn" disabled>Previous</button>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 1); $p <= min($total_pages, $page + 1); $p++): ?>
                    <?php if ($p === $page): ?>
                        <button class="page-btn active" disabled><?= $p ?></button>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($pageHref($p)) ?>" class="page-btn"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= htmlspecialchars($pageHref($page + 1)) ?>" class="page-btn">Next</a>
                <?php else: ?>
                    <button class="page-btn" disabled>Next</button>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /table-card -->
</section>
