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
$selectedVerification = array_values(array_filter((array)($_GET['verification'] ?? []), 'strlen'));

$where = ["fb.Manager_First_Name IS NOT NULL"];
$filterParams = [];

if ($search !== '') {
    $where[] = "(
        fb.Manager_First_Name LIKE ?
        OR fb.Manager_Last_Name LIKE ?
        OR fb.Manager_Email LIKE ?
        OR fb.Manager_Phone LIKE ?
        OR fb.Manager_Address LIKE ?
        OR fb.Organization_Name LIKE ?
        OR fb.Custom_FoodBank_ID LIKE ?
        OR fb.Physical_Address LIKE ?
    )";
    $searchLike = '%' . $search . '%';
    $filterParams = array_merge($filterParams, array_fill(0, 8, $searchLike));
}

if (!empty($selectedVerification)) {
    $placeholders = implode(',', array_fill(0, count($selectedVerification), '?'));
    $where[] = "fb.Verification_Status IN ($placeholders)";
    $filterParams = array_merge($filterParams, $selectedVerification);
}

$whereSql = ' WHERE ' . implode(' AND ', $where);
$queryParams = [];
if ($search !== '') {
    $queryParams['search'] = $search;
}
if (!empty($selectedVerification)) {
    $queryParams['verification'] = $selectedVerification;
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
    // Total count — only food banks that have a manager assigned
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM FOOD_BANKS fb" . $whereSql);
    $stmt_count->execute($filterParams);
    $total      = $stmt_count->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // Fetch managers
    $stmt = $pdo->prepare("
        SELECT
            fb.FoodBank_ID,
            fb.Custom_FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Manager_First_Name,
            fb.Manager_Last_Name,
            fb.Manager_Email,
            fb.Manager_Phone,
            fb.Manager_Address,
            fb.Verification_Status,
            fb.Org_Status
        FROM FOOD_BANKS fb
        {$whereSql}
        ORDER BY fb.Date_Registered DESC
        LIMIT ? OFFSET ?
    ");
    $paramIndex = 1;
    foreach ($filterParams as $filterParam) {
        $stmt->bindValue($paramIndex++, $filterParam);
    }
    $stmt->bindValue($paramIndex++, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex,   $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $managers = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    <?php $msgs = [
        'manager_updated' => 'Manager information updated successfully.',
        'manager_removed' => 'Manager has been removed from the food bank.',
    ];
    echo $msgs[$_GET['success']] ?? 'Action completed.'; ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error">
    <?php $msgs = [
        'missing_fields' => 'Please fill in all required fields.',
        'db_error'       => 'A database error occurred. Please try again.',
    ];
    echo $msgs[$_GET['error']] ?? 'An unexpected error occurred.'; ?>
</div>
<?php endif; ?>

<section class="content-area">
    <header class="page-header">
        <h2>Manager Name and Contact</h2>
        <p>Contact information for food bank managers.</p>
    </header>

    <div class="table-card">

        <!-- Toolbar -->
        <div class="table-toolbar" data-server-filter-url="/frontend/views/admin/foodbank-managers.php">
            <div class="toolbar-search">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="search-input" placeholder="Search managers..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Filter -->
            <div class="toolbar-filter-wrap">
                <button class="toolbar-btn" id="filter-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filter
                    <span class="filter-badge" id="filter-badge" style="display:none;"></span>
                </button>
                <div class="filter-dropdown" id="filter-dropdown">
                    <div class="filter-section">
                        <label class="filter-label">Verification Status</label>
                        <div class="filter-options">
                            <label class="filter-option"><input type="checkbox" name="verification" value="Pending" <?= in_array('Pending', $selectedVerification, true) ? 'checked' : '' ?>> Pending</label>
                            <label class="filter-option"><input type="checkbox" name="verification" value="Approved" <?= in_array('Approved', $selectedVerification, true) ? 'checked' : '' ?>> Approved</label>
                            <label class="filter-option"><input type="checkbox" name="verification" value="Suspended" <?= in_array('Suspended', $selectedVerification, true) ? 'checked' : '' ?>> Suspended</label>
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
                    <th>Name</th>
                    <th>Assigned FB</th>
                    <th>ID</th>
                    <th>Location</th>
                    <th>Contact #</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($managers as $mgr): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($mgr['Manager_First_Name'] . ' ' . $mgr['Manager_Last_Name']) ?></strong></td>
                    <td><?= htmlspecialchars($mgr['Organization_Name']) ?></td>
                    <td><?= htmlspecialchars($mgr['Custom_FoodBank_ID'] ?? '—') ?></td>
                    <td><?= htmlspecialchars(substr($mgr['Manager_Address'] ?? '—', 0, 30)) . (strlen($mgr['Manager_Address'] ?? '') > 30 ? '...' : '') ?></td>
                    <td><?= htmlspecialchars($mgr['Manager_Phone'] ?? '—') ?></td>
                    <td>
                        <div class="action-group">
                            <!-- View -->
                            <button class="action-btn" title="View"
                                onclick="openViewManagerModal(<?= htmlspecialchars(json_encode($mgr)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <!-- Edit -->
                            <button class="action-btn" title="Edit"
                                onclick="openEditManagerModal(<?= htmlspecialchars(json_encode($mgr)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <!-- Delete -->
                            <button class="action-btn delete" title="Delete"
                                onclick="openDeleteManagerModal(<?= htmlspecialchars(json_encode($mgr)) ?>)">
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
                <?php if (empty($managers)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: var(--spacing-3xl); color: var(--gray-400);">
                        No managers found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <div class="table-footer">
            <div class="pagination-info">
                Showing <strong><?= $total > 0 ? ($offset + 1) : 0 ?>–<?= min($offset + count($managers), $total) ?></strong>
                of <strong><?= $total ?></strong> Food Bank Managers
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

    </div>
</section>

<!-- Modals -->
<?php require_once 'modals/view-manager-modal.php'; ?>
<?php require_once 'modals/edit-manager-modal.php'; ?>
<?php require_once 'modals/delete-manager-modal.php'; ?>
