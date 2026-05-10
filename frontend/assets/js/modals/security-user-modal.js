// security-user-modal.js — full corrected version

function securityAppUrl(path) {
    const base = window.FOODBANK_BASE_URL || (window.location.pathname.startsWith('/foodbank/') ? '/foodbank' : '');
    return `${base}/${String(path || '').replace(/^\/+/, '')}`;
}

function openSecurityModal(user) {
    const fullName = [user.First_Name, user.Middle_Name, user.Last_Name].filter(Boolean).join(' ');

    document.getElementById('security-name').textContent  = fullName || '—';
    document.getElementById('security-email').textContent = user.Email || '—';
    document.getElementById('security-user-id').value     = user.User_ID || '';
    document.getElementById('security-account-id').value  = user.Account_ID ?? user.account_id ?? '';

    const badge  = document.getElementById('security-status-badge');
    const status = user.Status || 'Active';
    badge.textContent = status;
    badge.className   = status === 'Active' ? 'badge badge-active' : 'badge badge-inactive';

    const statusLabel     = document.getElementById('status-label');
    const toggleStatusBtn = document.getElementById('toggle-status-btn');
    statusLabel.textContent     = `Status: ${status}`;
    toggleStatusBtn.textContent = status === 'Active' ? 'Disable Account' : 'Enable Account';
    toggleStatusBtn.className   = status === 'Active'
        ? 'security-btn security-btn--danger'
        : 'security-btn security-btn--success';

    document.getElementById('reset-link-box').style.display = 'none';
    document.getElementById('reset-link-value').value = '';

    const sendBtn = document.getElementById('send-reset-btn');
    sendBtn.textContent = 'Send Password Reset Link';
    sendBtn.className   = 'security-btn security-btn--primary';
    sendBtn.disabled    = false;

    document.getElementById('securityUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSecurityModal() {
    document.getElementById('securityUserModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function initSecurityModal() {

    const modal       = document.getElementById('securityUserModal');
    const sendBtn     = document.getElementById('send-reset-btn');
    const copyBtn     = document.getElementById('copy-reset-btn');
    const toggleBtn   = document.getElementById('toggle-status-btn');

    if (!modal) return;

    // ── Close on backdrop ──────────────────────────────────
    modal.addEventListener('click', function (e) {
        if (e.target === this) closeSecurityModal();
    });

    // ── Escape key ─────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSecurityModal();
    });

    // ── Send Password Reset Link ───────────────────────────
    if (sendBtn) {
        sendBtn.addEventListener('click', function () {
            const accountId = document.getElementById('security-account-id').value;
            const btn       = this;

            btn.textContent = 'Generating...';
            btn.disabled    = true;

            fetch(securityAppUrl('/backend/controllers/auth/password_reset_token.php'), {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    `account_id=${accountId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('reset-link-box').style.display = 'flex';
                    document.getElementById('reset-link-value').value       = data.reset_link;
                    btn.textContent = '✓ Link Generated';
                    btn.className   = 'security-btn security-btn--success';
                } else {
                    alert('Error: ' + (data.message || 'Could not generate reset link.'));
                    btn.textContent = 'Send Password Reset Link';
                    btn.disabled    = false;
                }
            })
            .catch(() => {
                alert('Server error. Please try again.');
                btn.textContent = 'Send Password Reset Link';
                btn.disabled    = false;
            });
        });
    }

    // ── Copy Reset Link ────────────────────────────────────
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const input = document.getElementById('reset-link-value');
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                this.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`;
                setTimeout(() => {
                    this.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>`;
                }, 2000);
            });
        });
    }

    // ── Toggle Account Status ──────────────────────────────
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const accountId   = document.getElementById('security-account-id').value;
            const btn         = this;
            const newStatus   = btn.textContent.trim() === 'Disable Account' ? 'Inactive' : 'Active';

            console.log('Toggle firing — account_id:', accountId, '| status:', newStatus);

            if (!confirm(`Are you sure you want to ${newStatus === 'Inactive' ? 'disable' : 'enable'} this account?`)) return;

            btn.disabled = true;

            fetch(securityAppUrl('/backend/controllers/admin/users/process_toggle_status.php'), {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    `account_id=${accountId}&status=${newStatus}`
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
    }
}
