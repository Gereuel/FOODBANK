<!-- Donation Report Modal -->
<div id="donationReportModal" class="modal">
    <div class="modal-content modal-content--report">

        <!-- Green Header -->
        <div class="report-header">
            <div class="report-header-left">
                <h2>Donation Report</h2>
                <p id="report-tracking">Tracking Number: —</p>
                <div class="report-header-actions">
                    <button class="report-action-btn" onclick="printDonationReport()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        Print
                    </button>
                    <button class="report-action-btn" onclick="downloadDonationReport()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <polyline points="9 15 12 18 15 15"/>
                        </svg>
                        Download
                    </button>
                    <button class="report-action-btn" id="report-edit-btn">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit
                    </button>
                </div>
            </div>
            <button class="modal-close report-close" onclick="closeDonationReport()">&times;</button>
        </div>

        <!-- Report Body -->
        <div class="report-body" id="report-printable">

            <!-- Status Bar -->
            <div class="report-status-bar">
                <div class="report-status-left">
                    <div class="report-status-icon" id="report-status-icon">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="report-status-label">STATUS</div>
                        <div class="report-status-value" id="report-status">—</div>
                    </div>
                </div>
                <div class="report-generated">
                    Generated on:<br>
                    <strong id="report-generated-on">—</strong>
                </div>
            </div>

            <!-- Donor Information -->
            <div class="report-section">
                <div class="report-section-bg"></div>
                <h3 class="report-section-title">Donor Information</h3>
                <div class="report-grid">
                    <div class="report-field">
                        <span class="report-field-label">Full Name</span>
                        <span class="report-field-value" id="report-donor-name">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Email Address</span>
                        <span class="report-field-value" id="report-donor-email">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Phone Number</span>
                        <span class="report-field-value" id="report-donor-phone">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Unique ID</span>
                        <span class="report-field-value" id="report-donor-id">—</span>
                    </div>
                    <div class="report-field report-field--full">
                        <span class="report-field-label">Address</span>
                        <span class="report-field-value" id="report-donor-address">—</span>
                    </div>
                </div>
            </div>

            <!-- Donation Details -->
            <div class="report-section">
                <div class="report-section-bg"></div>
                <h3 class="report-section-title">Donation Details</h3>
                <div class="report-grid">
                    <div class="report-field">
                        <span class="report-field-label">Amount/Items</span>
                        <span class="report-field-value" id="report-quantity">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Type</span>
                        <span class="report-field-value" id="report-item-type">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Date</span>
                        <span class="report-field-value" id="report-date">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Time</span>
                        <span class="report-field-value" id="report-time">—</span>
                    </div>
                    <div class="report-field report-field--full">
                        <span class="report-field-label">Tracking Number</span>
                        <span class="report-field-value" id="report-tracking-detail">—</span>
                    </div>
                </div>
            </div>

            <!-- Designated Food Bank -->
            <div class="report-section">
                <div class="report-section-bg"></div>
                <h3 class="report-section-title">Designated Food Bank</h3>
                <div class="report-grid">
                    <div class="report-field">
                        <span class="report-field-label">Food Bank Name</span>
                        <span class="report-field-value" id="report-fb-name">—</span>
                    </div>
                    <div class="report-field">
                        <span class="report-field-label">Food Bank ID</span>
                        <span class="report-field-value" id="report-fb-id">—</span>
                    </div>
                    <div class="report-field report-field--full">
                        <span class="report-field-label">Location</span>
                        <span class="report-field-value" id="report-fb-location">—</span>
                    </div>
                    <div class="report-field report-field--full">
                        <span class="report-field-label">Contact Information</span>
                        <span class="report-field-value" id="report-fb-contact">—</span>
                    </div>
                </div>
            </div>

            <!-- Proof of Delivery -->
            <div class="report-section">
                <div class="report-section-bg"></div>
                <h3 class="report-section-title">Proof of Delivery</h3>
                <div id="report-proof-wrap">
                    <p class="report-no-proof">No proof of delivery uploaded.</p>
                </div>
                <p class="report-proof-verified" id="report-proof-verified" style="display:none;">
                    ✓ Delivery verified with photographic evidence
                </p>
            </div>

            <!-- Additional Notes -->
            <div class="report-section">
                <div class="report-section-bg"></div>
                <h3 class="report-section-title">Additional Notes</h3>
                <p class="report-notes" id="report-notes">—</p>
            </div>

        </div><!-- /report-body -->
    </div>
</div>