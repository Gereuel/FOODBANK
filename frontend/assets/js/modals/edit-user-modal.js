function openEditModal(user) {
    // Hidden IDs
    document.getElementById('edit-user-id').value        = user.User_ID || '';
    document.getElementById('edit-account-id').value     = user.Account_ID || '';

    // Account Details
    document.getElementById('edit-account-type').value   = user.Account_Type || '';
    document.getElementById('edit-email').value          = user.Email || '';
    document.getElementById('edit-phone').value          = user.Phone_Number || '';

    // Personal Information
    document.getElementById('edit-first-name').value     = user.First_Name || '';
    document.getElementById('edit-middle-name').value    = user.Middle_Name || '';
    document.getElementById('edit-last-name').value      = user.Last_Name || '';
    document.getElementById('edit-suffix').value         = user.Suffix || '';
    document.getElementById('edit-address').value        = user.Address || '';
    document.getElementById('edit-birthdate').value      = user.Birthdate
        ? user.Birthdate.split(' ')[0]  // strip time if datetime format
        : '';

    document.getElementById('editUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editUserModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Close on outside click
document.getElementById('editUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditModal();
});