/**
 * Users Overview JS
 * Handles user management modal interactions and actions
 */

// ─────────────────────────────────────────────────────────────
// ADD USER MODAL
// ─────────────────────────────────────────────────────────────

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

// ─────────────────────────────────────────────────────────────
// VIEW USER MODAL
// ─────────────────────────────────────────────────────────────

function openViewModal(user) {
    console.log('View user:', user);
    // TODO: Implement view modal with user details
}

// ─────────────────────────────────────────────────────────────
// EDIT USER MODAL
// ─────────────────────────────────────────────────────────────

function openEditModal(user) {
    console.log('Edit user:', user);
    // TODO: Implement edit modal with form pre-filled with user data
}

// ─────────────────────────────────────────────────────────────
// DELETE USER MODAL
// ─────────────────────────────────────────────────────────────

function openDeleteModal(user) {
    console.log('Delete user:', user);
    // TODO: Implement delete confirmation modal
}

// ─────────────────────────────────────────────────────────────
// MODAL CLOSE ON OUTSIDE CLICK
// ─────────────────────────────────────────────────────────────

function initializeModalCloseHandlers() {
    const addUserModal = document.getElementById('addUserModal');
    
    if (addUserModal) {
        addUserModal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeAddModal();
            }
        });
    }
}

// ─────────────────────────────────────────────────────────────
// KEYBOARD SHORTCUTS
// ─────────────────────────────────────────────────────────────

function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Close modals with Escape key
        if (event.key === 'Escape') {
            closeAddModal();
        }
    });
}

// ─────────────────────────────────────────────────────────────
// PAGE INITIALIZATION
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
    initializeModalCloseHandlers();
    initializeKeyboardShortcuts();
    console.log('Users Overview JS initialized');
});

// Ensure modal is hidden on page load
window.addEventListener('load', function() {
    const modal = document.getElementById('addUserModal');
    if (modal) {
        modal.classList.remove('show');
    }
});
