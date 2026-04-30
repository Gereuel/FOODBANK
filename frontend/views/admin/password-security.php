<?php
session_start();
require_once '../../../backend/config/database.php';

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
    // Get total count of users
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM USERS");
    $stmt_count->execute();
    $total_users = $stmt_count->fetch()['count'];
    $total_pages = ceil($total_users / $per_page);
    
    // Fetch users with their account info
    $stmt = $pdo->prepare("
        SELECT 
            u.User_ID,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.Suffix,
            u.Address,
            u.Birthdate,
            a.Account_ID,
            a.Account_Type,
            a.Custom_App_ID,
            a.Email,
            a.Phone_Number,
            a.Date_Created,
            a.Status,
            a.Two_FA_Enabled,
            a.Reset_Token
        FROM USERS u
        JOIN ACCOUNTS a ON u.User_ID = a.User_ID
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
            'user_deleted'         => 'User has been deleted successfully.',
            'cannot_delete_self'   => 'You cannot delete your own account.',
        ];
        echo $messages[$_GET['error']] ?? 'An unexpected error occurred.';
        ?>
    </div>
<?php endif; ?>

<!-- Password & Security -->
<section class="content-area">
    <header class="page-header">
        <h2>Password And Security</h2>
        <p>Manage user security and authentication</p>
    </header>

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
                    <div class="filter-actions">
                        <button class="filter-apply-btn" id="filter-apply-btn">Apply</button>
                        <button class="filter-reset-btn" id="filter-reset-btn">Reset</button>
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
                    $full_name = $user['First_Name'] . ' ' . ($user['Middle_Name'] ? $user['Middle_Name'] . ' ' : '') . $user['Last_Name'];
                    $role_map = ['PA' => 'Donor', 'FA' => 'Food Bank Manager', 'AA' => 'Admin'];
                    $role_display = $role_map[$user['Account_Type']] ?? $user['Account_Type'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($full_name) ?></td>
                    <td><?= htmlspecialchars($user['Email']) ?></td>
                    <td><?= htmlspecialchars($role_display) ?></td>
                    <td><?= htmlspecialchars($user['Custom_App_ID']) ?></td>
                    <td>
                        <div class="action-group">
                            <!-- Security -->
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
<?php require_once 'modals/security-user-modal.php'; ?>

<script src="/foodbank/frontend/assets/js/users-overview.js"></script>

<!-- Modal Scripts -->
<script src="/foodbank/frontend/assets/js/modals/toolbar.js"></script>
<script src="/foodbank/frontend/assets/js/modals/security-user-modal.js"></script>