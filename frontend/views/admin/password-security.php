<?php
session_start();
require_once __DIR__ . '/../../../backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access: Only administrators can access this page.");
}

$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset   = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');
$selectedRoles = array_values(array_filter((array)($_GET['role'] ?? []), 'strlen'));

$where = [];
$filterParams = [];
$displayNameSql = "CASE
                WHEN a.Account_Type = 'FA' THEN fb.Organization_Name
                ELSE CONCAT(u.First_Name, ' ', COALESCE(u.Middle_Name, ''), ' ', u.Last_Name)
            END";

if ($search !== '') {
    $where[] = "(
        {$displayNameSql} LIKE ?
        OR a.Email LIKE ?
        OR a.Phone_Number LIKE ?
        OR a.Custom_App_ID LIKE ?
    )";
    $searchLike = '%' . $search . '%';
    $filterParams = array_merge($filterParams, array_fill(0, 4, $searchLike));
}

if (!empty($selectedRoles)) {
    $placeholders = implode(',', array_fill(0, count($selectedRoles), '?'));
    $where[] = "a.Account_Type IN ($placeholders)";
    $filterParams = array_merge($filterParams, $selectedRoles);
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$queryParams = [];
if ($search !== '') {
    $queryParams['search'] = $search;
}
if (!empty($selectedRoles)) {
    $queryParams['role'] = $selectedRoles;
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
    // FIX: Count from ACCOUNTS not USERS so FA accounts are included
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON a.User_ID = u.User_ID
        LEFT JOIN FOOD_BANKS fb ON a.Account_ID = fb.Account_ID
        " . $whereSql);
    $stmt_count->execute($filterParams);
    $total_users = $stmt_count->fetch()['count'];
    $total_pages = ceil($total_users / $per_page);

    // FIX: LEFT JOIN so FA accounts (User_ID = NULL) are included
    $stmt = $pdo->prepare("
        SELECT
            a.Account_ID,
            a.Account_Type,
            a.Custom_App_ID,
            a.Email,
            a.Phone_Number,
            a.Date_Created,
            a.Status,
            a.Reset_Token,
            u.User_ID,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.Suffix,
            u.Address,
            u.Birthdate,
            -- FIX: Show org name for FA, personal name for others
            CASE
                WHEN a.Account_Type = 'FA' THEN fb.Organization_Name
                ELSE CONCAT(u.First_Name, ' ', COALESCE(u.Middle_Name, ''), ' ', u.Last_Name)
            END AS Display_Name
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON a.User_ID = u.User_ID
        LEFT JOIN FOOD_BANKS fb ON a.Account_ID = fb.Account_ID
        {$whereSql}
        ORDER BY a.Date_Created DESC
        LIMIT ? OFFSET ?
    ");
    $paramIndex = 1;
    foreach ($filterParams as $filterParam) {
        $stmt->bindValue($paramIndex++, $filterParam);
    }
    $stmt->bindValue($paramIndex++, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex,   $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!-- Password & Security -->
<section class="content-area">
    <header class="page-header">
        <h2>Password And Security</h2>
        <p>Manage user security and authentication</p>
    </header>

    <div class="table-card">

        <!-- Toolbar -->
        <div class="table-toolbar" data-server-filter-url="/frontend/views/admin/password-security.php">
            <div class="toolbar-search">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="search-input" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
            </div>

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
                        <label class="filter-label">Role</label>
                        <div class="filter-options">
                            <label class="filter-option"><input type="checkbox" name="role" value="PA" <?= in_array('PA', $selectedRoles, true) ? 'checked' : '' ?>> Donor</label>
                            <label class="filter-option"><input type="checkbox" name="role" value="FA" <?= in_array('FA', $selectedRoles, true) ? 'checked' : '' ?>> Food Bank Account</label>
                            <label class="filter-option"><input type="checkbox" name="role" value="AA" <?= in_array('AA', $selectedRoles, true) ? 'checked' : '' ?>> Admin</label>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="filter-apply-btn" id="filter-apply-btn">Apply</button>
                        <button class="filter-reset-btn" id="filter-reset-btn">Reset</button>
                    </div>
                </div>
            </div>

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
                    <th>Role</th>
                    <th>ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $role_map     = ['PA' => 'Donor', 'FA' => 'Food Bank Account', 'AA' => 'Admin'];
                    $role_display = $role_map[$user['Account_Type']] ?? $user['Account_Type'];
                ?>
                <tr>
                    <!-- FIX: Use Display_Name instead of manually built full name -->
                    <td><?= htmlspecialchars($user['Display_Name']) ?></td>
                    <td><?= htmlspecialchars($user['Email']) ?></td>
                    <td><?= htmlspecialchars($role_display) ?></td>
                    <td><?= htmlspecialchars($user['Custom_App_ID']) ?></td>
                    <td>
                        <div class="action-group">
                            <button class="action-btn" title="Security"
                                onclick="openSecurityModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <div class="table-footer">
            <div class="pagination-info">
                Showing <strong><?= ($offset + 1) ?>–<?= min($offset + count($users), $total_users) ?></strong>
                of <strong><?= $total_users ?></strong> Users
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
<?php require_once 'modals/security-user-modal.php'; ?>

<script src="/foodbank/frontend/assets/js/users-overview.js"></script>
<script src="/foodbank/frontend/assets/js/modals/toolbar.js"></script>
<script src="/foodbank/frontend/assets/js/modals/security-user-modal.js"></script>
