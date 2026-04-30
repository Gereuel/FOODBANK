function openDeleteModal(user) {
    const roleMap = { PA: 'Donor', FA: 'Food Bank Manager', AA: 'Admin' };
    const fullName = [user.First_Name, user.Middle_Name, user.Last_Name]
        .filter(Boolean).join(' ');

    // Populate summary
    document.getElementById('delete-name').textContent    = fullName || '—';
    document.getElementById('delete-email').textContent   = user.Email || '—';
    document.getElementById('delete-role').textContent    = roleMap[user.Account_Type] || user.Account_Type || '—';
    document.getElementById('delete-app-id').textContent  = user.Custom_App_ID || '—';

    // Hidden fields for form
    document.getElementById('delete-user-id').value       = user.User_ID || '';
    document.getElementById('delete-account-id').value    = user.Account_ID || '';

    document.getElementById('deleteUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Close on outside click
document.getElementById('deleteUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});