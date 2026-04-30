function openSecurityModal(user) {
    const roleMap = { PA: 'Donor', FA: 'Food Bank Manager', AA: 'Admin' };
    const fullName = [user.First_Name, user.Middle_Name, user.Last_Name].filter(Boolean).join(' ');

    // Populate user info
    document.getElementById('security-name').textContent  = fullName || '—';
    document.getElementById('security-email').textContent = user.Email || '—';
    document.getElementById('security-user-id').value     = user.User_ID || '';
    document.getElementById('security-account-id').value  = user.Account_ID || '';

    // Status badge
    const badge   = document.getElementById('security-status-badge');
    const status  = user.Status || 'Active';
    badge.textContent = status;
    badge.className   = status === 'Active' ? 'badge badge-active' : 'badge badge-inactive';

    // Status toggle button
    const statusLabel     = document.getElementById('status-label');
    const toggleStatusBtn = document.getElementById('toggle-status-btn');
    statusLabel.textContent       = `Status: ${status}`;
    toggleStatusBtn.textContent   = status === 'Active' ? 'Disable Account' : 'Enable Account';
    toggleStatusBtn.className     = status === 'Active'
        ? 'security-btn security-btn--danger'
        : 'security-btn security-btn--success';

    // 2FA toggle button
    const twofaEnabled  = parseInt(user.Two_FA_Enabled) === 1;
    const twofaLabel    = document.getElementById('twofa-label');
    const toggle2faBtn  = document.getElementById('toggle-2fa-btn');
    twofaLabel.textContent     = `2FA: ${twofaEnabled ? 'Enabled' : 'Disabled'}`;
    toggle2faBtn.textContent   = twofaEnabled ? 'Disable 2FA' : 'Enable 2FA';
    toggle2faBtn.className     = twofaEnabled
        ? 'security-btn security-btn--danger'
        : 'security-btn security-btn--success';

    // Hide reset link box on open
    document.getElementById('reset-link-box').style.display = 'none';
    document.getElementById('reset-link-value').value = '';

    document.getElementById('securityUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSecurityModal() {
    document.getElementById('securityUserModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Close on outside click
document.getElementById('securityUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeSecurityModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSecurityModal();
});

// ── Password Reset ─────────────────────────────────────
document.getElementById('send-reset-btn')?.addEventListener('click', function () {
        const accountId = document.getElementById('security-account-id').value;
        const btn       = this;

        btn.textContent = 'Generating...';
        btn.disabled    = true;

        fetch('/foodbank/backend/controllers/process_reset_token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `account_id=${accountId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reset-link-box').style.display  = 'block';
                document.getElementById('reset-link-value').value        = data.reset_link;
            } else {
                alert('Error: ' + (data.message || 'Could not generate reset link.'));
            }
        })
        .catch(() => alert('Server error. Please try again.'))
        .finally(() => {
            btn.textContent = 'Send Password Reset Link';
            btn.disabled    = false;
        });
});

// ── Copy Reset Link ────────────────────────────────────
document.getElementById('copy-reset-btn')?.addEventListener('click', function () {
        const input = document.getElementById('reset-link-value');
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            this.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`;
            setTimeout(() => {
                this.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>`;
            }, 2000);
        });
});

// ── Toggle Account Status ──────────────────────────────
document.getElementById('toggle-status-btn')?.addEventListener('click', function () {
        const accountId   = document.getElementById('security-account-id').value;
        const btn         = this;
        const currentText = btn.textContent.trim();
        const newStatus   = currentText === 'Disable Account' ? 'Inactive' : 'Active';

        if (!confirm(`Are you sure you want to ${newStatus === 'Inactive' ? 'disable' : 'enable'} this account?`)) return;

        btn.disabled = true;

        fetch('/foodbank/backend/controllers/process_toggle_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `account_id=${accountId}&status=${newStatus}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const statusLabel = document.getElementById('status-label');
                const badge       = document.getElementById('security-status-badge');

                statusLabel.textContent = `Status: ${newStatus}`;
                badge.textContent       = newStatus;
                badge.className         = newStatus === 'Active' ? 'badge badge-active' : 'badge badge-inactive';
                btn.textContent         = newStatus === 'Active' ? 'Disable Account' : 'Enable Account';
                btn.className           = newStatus === 'Active'
                    ? 'security-btn security-btn--danger'
                    : 'security-btn security-btn--success';
            } else {
                alert('Error: ' + (data.message || 'Could not update status.'));
            }
        })
        .catch(() => alert('Server error. Please try again.'))
        .finally(() => btn.disabled = false);
});

// ── Toggle 2FA ─────────────────────────────────────────
document.getElementById('toggle-2fa-btn')?.addEventListener('click', function () {
        const accountId   = document.getElementById('security-account-id').value;
        const btn         = this;
        const currentText = btn.textContent.trim();
        const newValue    = currentText === 'Enable 2FA' ? 1 : 0;

        if (!confirm(`Are you sure you want to ${newValue ? 'enable' : 'disable'} 2FA for this account?`)) return;

        btn.disabled = true;

        fetch('/foodbank/backend/controllers/process_toggle_2fa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `account_id=${accountId}&two_fa=${newValue}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const twofaLabel = document.getElementById('twofa-label');
                twofaLabel.textContent = `2FA: ${newValue ? 'Enabled' : 'Disabled'}`;
                btn.textContent        = newValue ? 'Disable 2FA' : 'Enable 2FA';
                btn.className          = newValue
                    ? 'security-btn security-btn--danger'
                    : 'security-btn security-btn--success';
            } else {
                alert('Error: ' + (data.message || 'Could not update 2FA.'));
            }
        })
        .catch(() => alert('Server error. Please try again.'))
        .finally(() => btn.disabled = false);
});