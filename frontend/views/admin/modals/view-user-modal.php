// <!-- View User Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <div class="modal-header-text">
                <h2>View User</h2>
                <p>Viewing full account details.</p>
            </div>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>

        <div class="modal-body">

            <h3>Account Details</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">Custom App ID</span>
                    <span class="view-value" id="view-app-id">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Account Type</span>
                    <span class="view-value" id="view-role">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Email</span>
                    <span class="view-value" id="view-email">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Phone Number</span>
                    <span class="view-value" id="view-phone">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Date Created</span>
                    <span class="view-value" id="view-date-created">—</span>
                </div>
            </div>

            <h3>Personal Information</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">First Name</span>
                    <span class="view-value" id="view-first-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Middle Name</span>
                    <span class="view-value" id="view-middle-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Last Name</span>
                    <span class="view-value" id="view-last-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Suffix</span>
                    <span class="view-value" id="view-suffix">—</span>
                </div>
                <div class="view-field view-field--full">
                    <span class="view-label">Address</span>
                    <span class="view-value" id="view-address">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Birthdate</span>
                    <span class="view-value" id="view-birthdate">—</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeViewModal()">Close</button>
            </div>

        </div>
    </div>
</div>