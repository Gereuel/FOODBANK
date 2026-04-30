<!-- Security Modal -->
<div id="securityUserModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <div class="modal-header-text">
                <h2>Password and Security</h2>
                <p>Manage account access and security settings.</p>
            </div>
            <button class="modal-close" onclick="closeSecurityModal()">&times;</button>
        </div>

        <div class="modal-body">

            <!-- User Summary -->
            <div class="security-user-info">
                <div class="security-user-avatar">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <div>
                    <div class="security-user-name" id="security-name">—</div>
                    <div class="security-user-email" id="security-email">—</div>
                    <span class="badge" id="security-status-badge">—</span>
                </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" id="security-user-id">
            <input type="hidden" id="security-account-id">

            <!-- Section 1: Password Reset -->
            <div class="security-section">
                <div class="security-section-header">
                    <div class="security-section-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="security-section-title">Password Reset</div>
                        <div class="security-section-desc">Generate a reset link and send it to the user's email.</div>
                    </div>
                </div>
                <button class="security-btn security-btn--primary" id="send-reset-btn">
                    Send Password Reset Link
                </button>
                <div class="reset-link-box" id="reset-link-box" style="display:none;">
                    <label>Reset Link (copy and share manually):</label>
                    <div class="reset-link-row">
                        <input type="text" id="reset-link-value" readonly>
                        <button class="copy-btn" id="copy-reset-btn" title="Copy">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                            </svg>
                        </button>
                    </div>
                    <span class="reset-link-expiry">This link expires in 24 hours.</span>
                </div>
            </div>

            <!-- Section 2: Account Status -->
            <div class="security-section">
                <div class="security-section-header">
                    <div class="security-section-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <div>
                        <div class="security-section-title">Account Status</div>
                        <div class="security-section-desc">Enable or disable this user's access to the system.</div>
                    </div>
                </div>
                <div class="security-toggle-row">
                    <span class="security-toggle-label" id="status-label">Status: Active</span>
                    <button class="security-btn" id="toggle-status-btn">Disable Account</button>
                </div>
            </div>

            <!-- Section 3: Two-Factor Authentication -->
            <div class="security-section">
                <div class="security-section-header">
                    <div class="security-section-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="security-section-title">Two-Factor Authentication</div>
                        <div class="security-section-desc">Force enable or disable 2FA for this account.</div>
                    </div>
                </div>
                <div class="security-toggle-row">
                    <span class="security-toggle-label" id="twofa-label">2FA: Disabled</span>
                    <button class="security-btn" id="toggle-2fa-btn">Enable 2FA</button>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeSecurityModal()">Close</button>
            </div>

        </div>
    </div>
</div>