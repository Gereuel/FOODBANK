<!-- Delete Manager Modal -->
<div id="deleteManagerModal" class="modal">
    <div class="modal-content modal-content--sm">
        <div class="modal-header modal-header--danger">
            <div class="modal-header-text">
                <h2>Remove Manager</h2>
                <p>This will clear the manager info from this food bank.</p>
            </div>
            <button class="modal-close" onclick="closeDeleteManagerModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="delete-warning">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p>Remove manager <strong id="dm-name">—</strong> from <strong id="dm-org">—</strong>?</p>
            </div>
            <form action="/foodbank/backend/controllers/admin/foodbanks/process_delete_manager.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="foodbank_id" id="dm-foodbank-id">
                <div class="modal-footer">
                    <button type="button" onclick="closeDeleteManagerModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Remove Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>
