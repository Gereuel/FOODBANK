<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access.");
}

$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset   = ($page - 1) * $per_page;

try {
    try {
        $pdo->exec("ALTER TABLE FOOD_BANKS ADD COLUMN Map_Image_URL VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) !== 1060) {
            throw $e;
        }
    }

    // Total count
    $stmt_count = $pdo->query("SELECT COUNT(*) FROM FOOD_BANKS");
    $total      = $stmt_count->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // Total managers (FA accounts linked to food banks)
    $stmt_mgr = $pdo->query("SELECT COUNT(*) FROM FOOD_BANKS WHERE Manager_Email IS NOT NULL");
    $total_managers = $stmt_mgr->fetchColumn();

    // Active food banks
    $stmt_active = $pdo->query("SELECT COUNT(*) FROM FOOD_BANKS WHERE Org_Status = 'Active'");
    $total_active = $stmt_active->fetchColumn();

    // Fetch food banks
    $stmt = $pdo->prepare("
        SELECT
            fb.FoodBank_ID,
            fb.Custom_FoodBank_ID,
            fb.Organization_Name,
            fb.Physical_Address,
            fb.Public_Email,
            fb.Public_Phone,
            fb.Map_Image_URL,
            fb.Time_Open,
            fb.Time_Close,
            fb.Operating_Days,
            fb.Legal_Documents_URL,
            fb.Verification_Status,
            fb.Org_Status,
            fb.Org_Email,
            fb.Date_Registered,
            fb.Manager_First_Name,
            fb.Manager_Last_Name,
            fb.Manager_Email,
            fb.Manager_Phone,
            fb.Manager_Address
        FROM FOOD_BANKS fb
        ORDER BY fb.Date_Registered DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $foodbanks = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$verification_classes = [
    'Pending'   => 'badge-pending',
    'Approved'  => 'badge-active',
    'Suspended' => 'badge-inactive',
];

$org_status_classes = [
    'Active'    => 'badge-active',
    'Pending'   => 'badge-pending',
    'Suspended' => 'badge-inactive',
];

function formatTime($time) {
    if (!$time) return '—';
    return date('g:i A', strtotime($time));
}
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    <?php $msgs = [
        'foodbank_added'   => 'Food bank registered successfully.',
        'foodbank_updated' => 'Food bank updated successfully.',
        'foodbank_deleted' => 'Food bank deleted successfully.',
    ];
    echo $msgs[$_GET['success']] ?? 'Action completed.'; ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error">
    <?php $msgs = [
        'missing_fields'  => 'Please fill in all required fields.',
        'email_taken'     => 'That email is already in use.',
        'invalid_file'    => 'Invalid file type.',
        'upload_failed'   => 'File upload failed.',
        'db_error'        => 'A database error occurred.',
    ];
    echo $msgs[$_GET['error']] ?? 'An unexpected error occurred.'; ?>
</div>
<?php endif; ?>

<section class="content-area">
    <header class="page-header">
        <h2>Food Bank Management Overview</h2>
        <p>Monitor and manage all registered food banks.</p>
        <button class="btn-add" onclick="openAddFoodBankModal()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Food Bank
        </button>
    </header>

    <!-- Stat Cards -->
    <div class="stat-row">
        <div class="stat-card">
            <div class="label">Total Food Banks</div>
            <div class="value"><?= $total ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Active</div>
            <div class="value green"><?= $total_active ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Food Bank Managers</div>
            <div class="value"><?= $total_managers ?></div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">

        <!-- Toolbar -->
        <div class="table-toolbar">
            <div class="toolbar-search">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="search-input" placeholder="Search food banks...">
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
                            <label class="filter-option"><input type="checkbox" name="verification" value="Pending"> Pending</label>
                            <label class="filter-option"><input type="checkbox" name="verification" value="Approved"> Approved</label>
                            <label class="filter-option"><input type="checkbox" name="verification" value="Suspended"> Suspended</label>
                        </div>
                    </div>
                    <div class="filter-section">
                        <label class="filter-label">Org Status</label>
                        <div class="filter-options">
                            <label class="filter-option"><input type="checkbox" name="org_status" value="Active"> Active</label>
                            <label class="filter-option"><input type="checkbox" name="org_status" value="Pending"> Pending</label>
                            <label class="filter-option"><input type="checkbox" name="org_status" value="Suspended"> Suspended</label>
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
                    <th>Email</th>
                    <th>Office #</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($foodbanks as $fb): ?>
                <tr>
                    <td><?= htmlspecialchars($fb['Organization_Name']) ?></td>
                    <td><?= htmlspecialchars($fb['Org_Email'] ?? $fb['Public_Email'] ?? '—') ?></td>
                    <td><?= formatTime($fb['Time_Open']) ?> - <?= formatTime($fb['Time_Close']) ?></td>
                    <td><?= htmlspecialchars(substr($fb['Physical_Address'], 0, 30)) . (strlen($fb['Physical_Address']) > 30 ? '...' : '') ?></td>
                    <td>
                        <span class="badge <?= $verification_classes[$fb['Verification_Status']] ?? 'badge-pending' ?>">
                            <?= htmlspecialchars($fb['Verification_Status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($fb['Custom_FoodBank_ID'] ?? '—') ?></td>
                    <td>
                        <div class="action-group">
                            <!-- View -->
                            <button class="action-btn" title="View"
                                onclick="openViewFoodBankModal(<?= htmlspecialchars(json_encode($fb)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <!-- Edit -->
                            <button class="action-btn" title="Edit"
                                onclick="openEditFoodBankModal(<?= htmlspecialchars(json_encode($fb)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <!-- Delete -->
                            <button class="action-btn delete" title="Delete"
                                onclick="openDeleteFoodBankModal(<?= htmlspecialchars(json_encode($fb)) ?>)">
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
                <?php if (empty($foodbanks)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding: var(--spacing-3xl); color: var(--gray-400);">
                        No food banks registered yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <div class="table-footer">
            <div class="pagination-info">
                Showing <strong><?= $total > 0 ? ($offset + 1) : 0 ?>–<?= min($offset + count($foodbanks), $total) ?></strong>
                of <strong><?= $total ?></strong> Food Banks
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="page-btn">Previous</a>
                <?php else: ?>
                    <button class="page-btn" disabled>Previous</button>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 1); $p <= min($total_pages, $page + 1); $p++): ?>
                    <?php if ($p === $page): ?>
                        <button class="page-btn active" disabled><?= $p ?></button>
                    <?php else: ?>
                        <a href="?page=<?= $p ?>" class="page-btn"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="page-btn">Next</a>
                <?php else: ?>
                    <button class="page-btn" disabled>Next</button>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /table-card -->
</section>

<!-- Modals -->
<?php require_once 'modals/add-foodbank-modal.php'; ?>
<?php require_once 'modals/view-foodbank-modal.php'; ?>
<?php require_once 'modals/edit-foodbank-modal.php'; ?>
<?php require_once 'modals/delete-foodbank-modal.php'; ?>
