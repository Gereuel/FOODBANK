<?php
session_start();
require_once __DIR__ . '/../../../backend/config/database.php';

if (!isset($_SESSION['Account_Type']) || $_SESSION['Account_Type'] !== 'AA') {
    die("Unauthorized Access.");
}

$admin_id = $_SESSION['Account_ID'];

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

try {
    // Get total count for pagination
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM NOTIFICATIONS WHERE Account_ID = ?");
    $stmt_count->execute([$admin_id]);
    $total = $stmt_count->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // Fetch notifications with most recent first
    $stmt = $pdo->prepare("
        SELECT * FROM NOTIFICATIONS 
        WHERE Account_ID = ? 
        ORDER BY Created_At DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $admin_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<section class="content-area">
    <header class="page-header">
        <h2>Notifications & Activity</h2>
        <p>A complete history of system alerts and account activities.</p>
        <button class="btn-add" id="mark-all-read-page" style="background: var(--text-main); border: none;">
            <i class="fas fa-check-double"></i> Mark All as Read
        </button>
    </header>

    <div class="table-card">
        <div class="table-toolbar" style="border:none; padding-bottom: 0;">
            <span class="rpt-table-title" style="font-size: var(--font-size-xl); font-weight: bold;">Notification History</span>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">Status</th>
                        <th>Message</th>
                        <th>Date Received</th>
                        <th style="width: 120px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 60px; color: var(--gray-400);">
                            <i class="fas fa-bell-slash" style="font-size: 32px; display: block; margin-bottom: 12px; opacity: 0.5;"></i>
                            No notifications found.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <tr style="<?= !$notif['Is_Read'] ? 'background-color: var(--green-light-extra);' : '' ?>">
                                <td style="text-align: center;">
                                    <?php if (!$notif['Is_Read']): ?>
                                        <span class="badge badge-active" style="width: 10px; height: 10px; padding: 0; border-radius: 50%;" title="Unread"></span>
                                    <?php else: ?>
                                        <i class="fas fa-check" style="color: var(--gray-300); font-size: 12px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="color: var(--text-main); font-weight: <?= !$notif['Is_Read'] ? '600' : '400' ?>;">
                                        <?= htmlspecialchars($notif['Message']) ?>
                                    </div>
                                    <?php if ($notif['Link']): ?>
                                        <a href="#" onclick="loadComponent('main-display', '<?= htmlspecialchars($notif['Link'], ENT_QUOTES) ?>')"
                                           style="font-size: 12px; color: var(--green-main); text-decoration: none; margin-top: 4px; display: inline-block;">
                                           View related page →
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-sub); font-size: 14px;">
                                    <?= date('M j, Y — g:i A', strtotime($notif['Created_At'])) ?>
                                </td>
                                <td>
                                    <div class="action-group" style="justify-content: center;">
                                        <?php if (!$notif['Is_Read']): ?>
                                            <button class="action-btn" title="Mark as Read" onclick="markReadPage(<?= $notif['Notification_ID'] ?>)">
                                                <i class="fas fa-envelope-open"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="action-btn delete" title="Remove" onclick="deleteNotification(<?= $notif['Notification_ID'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="pagination-info">
                Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?: 1 ?></strong> (Total: <?= $total ?> notifications)
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <button class="page-btn" onclick="loadComponent('main-display', appUrl('/frontend/views/admin/notifications.php?page=<?= $page-1 ?>'))">Previous</button>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <button class="page-btn" onclick="loadComponent('main-display', appUrl('/frontend/views/admin/notifications.php?page=<?= $page+1 ?>'))">Next</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
window.initNotificationsPage = function() {
    window.markReadPage = async function(id) {
        await fetch(appUrl(`/backend/api/notifications/mark_as_read.php?id=${id}`), { method: 'POST' });
        loadComponent('main-display', appUrl('/frontend/views/admin/notifications.php?page=<?= $page ?>'));
    };

    window.deleteNotification = async function(id) {
        // Placeholder for delete API if implemented
        loadComponent('main-display', appUrl('/frontend/views/admin/notifications.php?page=<?= $page ?>'));
    };

    const markAllBtn = document.getElementById('mark-all-read-page');
    if (markAllBtn) {
        markAllBtn.onclick = async function() {
            await fetch(appUrl('/backend/api/notifications/mark_all_read.php'), { method: 'POST' });
            loadComponent('main-display', appUrl('/frontend/views/admin/notifications.php'));
        };
    }
};
if (typeof initNotificationsPage === 'function') initNotificationsPage();
</script>
