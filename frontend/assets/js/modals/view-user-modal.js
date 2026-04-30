function openViewModal(user) {
    const roleMap = { PA: 'Donor (Personal Account)', FA: 'Food Bank Account', AA: 'Admin Account' };

    document.getElementById('view-app-id').textContent        = user.Custom_App_ID || '—';
    document.getElementById('view-role').textContent          = roleMap[user.Account_Type] || user.Account_Type || '—';
    document.getElementById('view-email').textContent         = user.Email || '—';
    document.getElementById('view-phone').textContent         = user.Phone_Number || '—';
    document.getElementById('view-date-created').textContent  = user.Date_Created
        ? new Date(user.Date_Created).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';

    document.getElementById('view-first-name').textContent    = user.First_Name || '—';
    document.getElementById('view-middle-name').textContent   = user.Middle_Name || '—';
    document.getElementById('view-last-name').textContent     = user.Last_Name || '—';
    document.getElementById('view-suffix').textContent        = user.Suffix || '—';
    document.getElementById('view-address').textContent       = user.Address || '—';
    document.getElementById('view-birthdate').textContent     = user.Birthdate
        ? new Date(user.Birthdate).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';

    document.getElementById('viewUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeViewModal() {
    document.getElementById('viewUserModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Close on outside click
document.getElementById('viewUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeViewModal();
});