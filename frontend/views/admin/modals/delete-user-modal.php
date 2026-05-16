<!-- Delete User Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content modal-content--sm">

        <div class="modal-header modal-header--danger">
            <div class="modal-header-text">
                <h2 id="delete-modal-title">Delete User</h2>
                <p id="delete-modal-subtitle">This action cannot be undone.</p>
            </div>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>

        <div class="modal-body">
            <div class="delete-warning">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p id="delete-warning-text">Are you sure you want to delete this user?</p>
            </div>

            <!-- User Summary -->
            <div class="delete-user-summary">
                <div class="view-field">
                    <span class="view-label">Name</span>
                    <span class="view-value" id="delete-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Email</span>
                    <span class="view-value" id="delete-email">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Role</span>
                    <span class="view-value" id="delete-role">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">ID</span>
                    <span class="view-value" id="delete-app-id">—</span>
                </div>
            </div>

            <form action="/foodbank/backend/controllers/admin/users/process_delete_user.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="user_id" id="delete-user-id">
                <input type="hidden" name="account_id" id="delete-account-id">
                <input type="hidden" name="deletion_request_id" id="delete-request-id">

                <div class="modal-footer">
                    <button type="button" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit"
                            id="delete-reject-btn"
                            class="btn-secondary"
                            formaction="/foodbank/backend/controllers/admin/users/process_reject_deletion_request.php"
                            hidden>
                        Reject Request
                    </button>
                    <button type="submit" class="btn-danger" id="delete-submit-btn">Delete User</button>
                </div>
            </form>

        </div>
    </div>
</div>
