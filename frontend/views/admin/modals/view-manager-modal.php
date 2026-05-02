<!-- View Manager Modal -->
<div id="viewManagerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Manager Details</h2>
                <p>Viewing manager contact information.</p>
            </div>
            <button class="modal-close" onclick="closeViewManagerModal()">&times;</button>
        </div>
        <div class="modal-body">

            <h3>Manager Information</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">First Name</span>
                    <span class="view-value" id="vm-first-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Last Name</span>
                    <span class="view-value" id="vm-last-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Email</span>
                    <span class="view-value" id="vm-email">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Phone</span>
                    <span class="view-value" id="vm-phone">—</span>
                </div>
                <div class="view-field view-field--full">
                    <span class="view-label">Address</span>
                    <span class="view-value" id="vm-address">—</span>
                </div>
            </div>

            <h3>Assigned Food Bank</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">Organization</span>
                    <span class="view-value" id="vm-org-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Food Bank ID</span>
                    <span class="view-value" id="vm-fb-id">—</span>
                </div>
                <div class="view-field view-field--full">
                    <span class="view-label">Food Bank Address</span>
                    <span class="view-value" id="vm-fb-address">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Verification Status</span>
                    <span class="view-value" id="vm-verification">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Org Status</span>
                    <span class="view-value" id="vm-org-status">—</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeViewManagerModal()">Close</button>
            </div>
        </div>
    </div>
</div>