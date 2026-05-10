<?php
session_start();
require_once __DIR__ . '/../../../backend/config/database.php';

// --- CRITICAL SECURITY CHECK ---
// Only allow access if the user is logged in AND is an Admin ('AA')
if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access: Only administrators can access this page.");
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ACCOUNT_DELETION_REQUESTS (
            Request_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            User_ID INT DEFAULT NULL,
            Reason TEXT DEFAULT NULL,
            Status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
            Requested_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            Reviewed_At DATETIME DEFAULT NULL,
            Reviewed_By INT DEFAULT NULL,
            INDEX idx_deletion_request_account (Account_ID),
            INDEX idx_deletion_request_status (Status),
            CONSTRAINT fk_delete_request_account
                FOREIGN KEY (Account_ID) REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_delete_request_user
                FOREIGN KEY (User_ID) REFERENCES USERS(User_ID)
                ON DELETE SET NULL ON UPDATE CASCADE
        )
    ");

    // Get total count of users
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM ACCOUNTS");
    $stmt_count->execute();
    $total_users = $stmt_count->fetch()['count'];
    $total_pages = ceil($total_users / $per_page);

    $stmt_delete_requests = $pdo->query("
        SELECT COUNT(*)
        FROM ACCOUNT_DELETION_REQUESTS
        WHERE Status = 'Pending'
    ");
    $pending_delete_requests = (int) $stmt_delete_requests->fetchColumn();
    
    // Fetch users with their account info
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
            -- Conditionally show org name for FA, personal name for others
            CASE 
                WHEN a.Account_Type = 'FA' THEN fb.Organization_Name
                ELSE CONCAT(u.First_Name, ' ', COALESCE(u.Middle_Name, ''), ' ', u.Last_Name)
            END AS Display_Name,
            -- Still fetch individual fields for modals
            u.User_ID,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.Suffix,
            u.Address,
            u.Birthdate,
            -- Food bank fields for FA accounts
            fb.Organization_Name,
            fb.FoodBank_ID,
            fb.Physical_Address AS FB_Address,
            dr.Request_ID AS Deletion_Request_ID,
            dr.Status AS Deletion_Request_Status,
            dr.Requested_At AS Deletion_Requested_At
        FROM ACCOUNTS a
        LEFT JOIN USERS u ON a.User_ID = u.User_ID
        LEFT JOIN FOOD_BANKS fb ON a.Account_ID = fb.Account_ID
        LEFT JOIN ACCOUNT_DELETION_REQUESTS dr
          ON dr.Account_ID = a.Account_ID
         AND dr.Status = 'Pending'
        ORDER BY a.Date_Created DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Get stats
    $stmt_stats = $pdo->prepare("SELECT Account_Type, COUNT(*) as count FROM ACCOUNTS GROUP BY Account_Type");
    $stmt_stats->execute();
    $stats = [];
    foreach ($stmt_stats->fetchAll() as $stat) {
        $stats[$stat['Account_Type']] = $stat['count'];
    }
    
    $total_foodbank = $stats['FA'] ?? 0;
    $total_individual = $total_users;
    $current_page = $page;
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        $messages = [
            'user_updated' => 'User has been updated successfully.',
            'user_deleted' => 'User has been deleted successfully.',
            'deletion_request_approved' => 'Deletion request has been approved and the account was deleted.',
            'deletion_request_rejected' => 'Deletion request has been rejected.',
        ];
        echo $messages[$_GET['success']] ?? 'Action completed successfully.';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <?php
        $messages = [
            'missing_fields'       => 'Please fill in all required fields.',
            'invalid_account_type' => 'Invalid account type selected.',
            'invalid_email'        => 'Please enter a valid email address.',
            'invalid_birthdate'    => 'Please enter a valid birthdate.',
            'email_taken'          => 'That email address is already in use.',
            'db_error'             => 'A database error occurred. Please try again.',
            'cannot_delete_self'   => 'You cannot delete your own account.',
        ];
        echo $messages[$_GET['error']] ?? 'An unexpected error occurred.';
        ?>
    </div>
<?php endif; ?>

<!-- User_Overview -->
<section class="content-area">
    <header class="page-header">
        <h2>Users Overview</h2>
        <p>Manage all registered users, admins, and food bank accounts.</p>
        <button class="btn-add" onclick="openAddModal()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add User
        </button>
    </header>

    <!-- Stat Cards -->
    <div class="stat-row">
        <div class="stat-card">
            <div class="label">Total Users</div>
            <div class="value"><?= $total_users ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Individual</div>
            <div class="value"><?= $total_individual ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Food Bank</div>
            <div class="value"><?= $total_foodbank ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Deletion Requests</div>
            <div class="value red"><?= $pending_delete_requests ?></div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">

        <!-- Toolbar -->
        <div class="table-toolbar">
            <!-- Search -->
            <div class="toolbar-search">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="search-input" placeholder="Search users...">
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

                <!-- Filter Dropdown -->
                <div class="filter-dropdown" id="filter-dropdown">
                    <div class="filter-section">
                        <label class="filter-label">Role</label>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="role" value="PA"> Donor
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="role" value="FA"> Food Bank Account
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="role" value="AA"> Admin
                            </label>
                        </div>
                    </div>
                    <div class="filter-section">
                        <label class="filter-label">Status</label>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="status" value="active" checked> Active
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="status" value="disabled"> Disabled
                            </label>
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

            <!-- Show All / Reset -->
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
                    <th>Location</th>
                    <th>Status</th>
                    <th>ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                    <?php foreach ($users as $user): 
                        // Use Display_Name from SQL for the name column
                        $display_name = $user['Display_Name'];
                        $role_map     = ['PA' => 'Donor', 'FA' => 'Food Bank Account', 'AA' => 'Admin'];
                        $role_display = $role_map[$user['Account_Type']] ?? $user['Account_Type'];

                        // For FA accounts, use FB address; for others use user address
                        $location = $user['Account_Type'] === 'FA'
                            ? ($user['FB_Address'] ?? '—')
                            : ($user['Address'] ?? '—');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($display_name) ?></td>
                        <td><?= htmlspecialchars($user['Email']) ?></td>
                        <td><?= htmlspecialchars($role_display) ?></td>
                        <td><?= htmlspecialchars(substr($location, 0, 40) . (strlen($location) > 40 ? '...' : '')) ?></td>
                        <td>
                            <span class="badge <?= $user['Deletion_Request_Status'] === 'Pending' ? 'badge-pending' : ($user['Status'] === 'Active' ? 'badge-active' : 'badge-inactive') ?>">
                                <?php if ($user['Deletion_Request_Status'] === 'Pending'): ?>
                                    Deletion Requested
                                <?php else: ?>
                                    <?= $user['Status'] === 'Disabled' ? 'Disabled' : htmlspecialchars($user['Status']) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                    <td><?= htmlspecialchars($user['Custom_App_ID']) ?></td>
                        <td>
                            <div class="action-group">
                                <!-- View — always visible -->
                                <button class="action-btn" title="View"
                                    onclick="openViewModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>

                                <?php if ($user['Account_Type'] !== 'FA'): ?>
                                    <!-- Edit -->
                                    <button class="action-btn" title="Edit"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        <svg width="15" height="15" fill="none" stroke="#374151" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <!-- Delete -->
                                    <button class="action-btn delete" title="Delete"
                                        onclick="openDeleteModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        <svg width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                            <path d="M10 11v6M14 11v6"/>
                                            <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Footer / Pagination -->
        <div class="table-footer">
            <div class="pagination-info">
                Showing <strong><?= ($offset + 1) ?>–<?= min($offset + count($users), $total_users) ?></strong> of <strong><?= $total_users ?></strong> Users
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
<?php require_once 'modals/add-user-modal.php'; ?>
<?php require_once 'modals/edit-user-modal.php'; ?>
<?php require_once 'modals/view-user-modal.php'; ?>
<?php require_once 'modals/delete-user-modal.php'; ?>
<?php require_once 'modals/security-user-modal.php'; ?>

<script src="/foodbank/frontend/assets/js/users-overview.js?v=<?= time() ?>"></script>

<!-- Modal Scripts -->
<script src="/foodbank/frontend/assets/js/modals/add-user-modal.js?v=<?= time() ?>"></script>
<script src="/foodbank/frontend/assets/js/modals/edit-user-modal.js?v=<?= time() ?>"></script>
<script src="/foodbank/frontend/assets/js/modals/view-user-modal.js?v=<?= time() ?>"></script>
<script src="/foodbank/frontend/assets/js/modals/delete-user-modal.js?v=<?= time() ?>"></script>
<script src="/foodbank/frontend/assets/js/modals/security-user-modal.js?v=<?= time() ?>"></script>
<script src="/foodbank/frontend/assets/js/modals/toolbar.js?v=<?= time() ?>"></script>
