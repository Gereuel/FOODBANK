<!-- View Food Bank Modal -->
<div id="viewFoodBankModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Food Bank Details</h2>
                <p>Viewing full food bank information.</p>
            </div>
            <button class="modal-close" onclick="closeViewFoodBankModal()">&times;</button>
        </div>
        <div class="modal-body">

            <h3>Organization</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">Organization Name</span>
                    <span class="view-value" id="vfb-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Food Bank ID</span>
                    <span class="view-value" id="vfb-id">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Org Email</span>
                    <span class="view-value" id="vfb-org-email">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Verification Status</span>
                    <span class="view-value" id="vfb-verification">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Org Status</span>
                    <span class="view-value" id="vfb-org-status">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Date Registered</span>
                    <span class="view-value" id="vfb-date">—</span>
                </div>
                <div class="view-field view-field--full">
                    <span class="view-label">Physical Address</span>
                    <span class="view-value" id="vfb-address">—</span>
                </div>
            </div>

            <h3>Operating Hours</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">Office Hours</span>
                    <span class="view-value" id="vfb-hours">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Operating Days</span>
                    <span class="view-value" id="vfb-days">—</span>
                </div>
            </div>

            <h3>Map Screenshot</h3>
            <div class="map-image-preview" id="vfb-map-preview" hidden>
                <img src="" alt="Food bank map screenshot" id="vfb-map-preview-img">
                <a href="#" target="_blank" rel="noopener" id="vfb-map-preview-link">Open map screenshot</a>
            </div>
            <p class="field-hint" id="vfb-map-empty">No map screenshot uploaded.</p>

            <h3>Public Contact</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">Public Email</span>
                    <span class="view-value" id="vfb-public-email">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Public Phone</span>
                    <span class="view-value" id="vfb-public-phone">—</span>
                </div>
            </div>

            <h3>Manager Information</h3>
            <div class="view-grid">
                <div class="view-field">
                    <span class="view-label">Manager Name</span>
                    <span class="view-value" id="vfb-manager-name">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Manager Email</span>
                    <span class="view-value" id="vfb-manager-email">—</span>
                </div>
                <div class="view-field">
                    <span class="view-label">Manager Phone</span>
                    <span class="view-value" id="vfb-manager-phone">—</span>
                </div>
                <div class="view-field view-field--full">
                    <span class="view-label">Manager Address</span>
                    <span class="view-value" id="vfb-manager-address">—</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeViewFoodBankModal()">Close</button>
            </div>
        </div>
    </div>
</div>
