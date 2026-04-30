function openAddModal() {
    const modal = document.getElementById('addUserModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeAddModal() {
    const modal = document.getElementById('addUserModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// Close on outside click
document.getElementById('addUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAddModal();
});