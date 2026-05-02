// ── View Manager ───────────────────────────────────────────
function openViewManagerModal(mgr) {
    document.getElementById('vm-first-name').textContent  = mgr.Manager_First_Name || '—';
    document.getElementById('vm-last-name').textContent   = mgr.Manager_Last_Name  || '—';
    document.getElementById('vm-email').textContent       = mgr.Manager_Email      || '—';
    document.getElementById('vm-phone').textContent       = mgr.Manager_Phone      || '—';
    document.getElementById('vm-address').textContent     = mgr.Manager_Address    || '—';
    document.getElementById('vm-org-name').textContent    = mgr.Organization_Name  || '—';
    document.getElementById('vm-fb-id').textContent       = mgr.Custom_FoodBank_ID || '—';
    document.getElementById('vm-fb-address').textContent  = mgr.Physical_Address   || '—';
    document.getElementById('vm-verification').textContent = mgr.Verification_Status || '—';
    document.getElementById('vm-org-status').textContent  = mgr.Org_Status         || '—';

    document.getElementById('viewManagerModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeViewManagerModal() {
    document.getElementById('viewManagerModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Edit Manager ───────────────────────────────────────────
function openEditManagerModal(mgr) {
    document.getElementById('em-foodbank-id').value = mgr.FoodBank_ID        || '';
    document.getElementById('em-first-name').value  = mgr.Manager_First_Name || '';
    document.getElementById('em-last-name').value   = mgr.Manager_Last_Name  || '';
    document.getElementById('em-email').value       = mgr.Manager_Email      || '';
    document.getElementById('em-phone').value       = mgr.Manager_Phone      || '';
    document.getElementById('em-address').value     = mgr.Manager_Address    || '';

    document.getElementById('editManagerModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditManagerModal() {
    document.getElementById('editManagerModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Delete Manager ─────────────────────────────────────────
function openDeleteManagerModal(mgr) {
    const fullName = (mgr.Manager_First_Name || '') + ' ' + (mgr.Manager_Last_Name || '');
    document.getElementById('dm-name').textContent       = fullName.trim() || '—';
    document.getElementById('dm-org').textContent        = mgr.Organization_Name  || '—';
    document.getElementById('dm-foodbank-id').value      = mgr.FoodBank_ID        || '';

    document.getElementById('deleteManagerModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteManagerModal() {
    document.getElementById('deleteManagerModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Init ───────────────────────────────────────────────────
function initManagerModals() {
    ['viewManagerModal', 'editManagerModal', 'deleteManagerModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeViewManagerModal();
            closeEditManagerModal();
            closeDeleteManagerModal();
        }
    });
}