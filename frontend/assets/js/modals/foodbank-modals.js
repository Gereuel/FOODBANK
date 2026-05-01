// ── Add Food Bank — Stepper ────────────────────────────────
let currentStep = 1;
const totalSteps = 4;

function openAddFoodBankModal() {
    currentStep = 1;
    showFoodBankStep(1);
    document.getElementById('addFoodBankModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAddFoodBankModal() {
    document.getElementById('addFoodBankModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function showFoodBankStep(step) {
    for (let i = 1; i <= totalSteps; i++) {
        const formStep = document.getElementById(`form-step-${i}`);
        const indicator = document.getElementById(`step-indicator-${i}`);
        if (formStep) formStep.style.display = i === step ? 'block' : 'none';
        if (indicator) {
            indicator.classList.remove('active', 'completed');
            if (i < step) indicator.classList.add('completed');
            if (i === step) indicator.classList.add('active');
        }
    }

    const prevBtn   = document.getElementById('fb-prev-btn');
    const nextBtn   = document.getElementById('fb-next-btn');
    const submitBtn = document.getElementById('fb-submit-btn');

    prevBtn.style.display   = step > 1 ? 'block' : 'none';
    nextBtn.style.display   = step < totalSteps ? 'block' : 'none';
    submitBtn.style.display = step === totalSteps ? 'block' : 'none';
}

function fbNextStep() {
    if (!validateFoodBankStep(currentStep)) return;
    if (currentStep < totalSteps) {
        currentStep++;
        showFoodBankStep(currentStep);
    }
}

function fbPrevStep() {
    if (currentStep > 1) {
        currentStep--;
        showFoodBankStep(currentStep);
    }
}

function validateFoodBankStep(step) {
    const stepEl = document.getElementById(`form-step-${step}`);
    const inputs = stepEl.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;

    inputs.forEach(input => {
        input.style.borderColor = '';
        if (!input.value.trim()) {
            input.style.borderColor = '#dc2626';
            valid = false;
        }
    });

    if (step === 1) {
        const pass    = stepEl.querySelector('input[name="org_password"]').value;
        const confirm = stepEl.querySelector('input[name="org_password_confirm"]').value;
        if (pass !== confirm) {
            stepEl.querySelector('input[name="org_password_confirm"]').style.borderColor = '#dc2626';
            alert('Passwords do not match.');
            valid = false;
        }
    }

    return valid;
}

// ── View Food Bank Modal ───────────────────────────────────
function openViewFoodBankModal(fb) {
    document.getElementById('vfb-name').textContent         = fb.Organization_Name || '—';
    document.getElementById('vfb-id').textContent           = fb.Custom_FoodBank_ID || '—';
    document.getElementById('vfb-org-email').textContent    = fb.Org_Email || '—';
    document.getElementById('vfb-verification').textContent = fb.Verification_Status || '—';
    document.getElementById('vfb-org-status').textContent   = fb.Org_Status || '—';
    document.getElementById('vfb-address').textContent      = fb.Physical_Address || '—';
    document.getElementById('vfb-date').textContent         = fb.Date_Registered
        ? new Date(fb.Date_Registered).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';

    const timeOpen  = fb.Time_Open  ? formatTimeFB(fb.Time_Open)  : '—';
    const timeClose = fb.Time_Close ? formatTimeFB(fb.Time_Close) : '—';
    document.getElementById('vfb-hours').textContent        = `${timeOpen} - ${timeClose}`;
    document.getElementById('vfb-days').textContent         = fb.Operating_Days || '—';

    document.getElementById('vfb-public-email').textContent  = fb.Public_Email || '—';
    document.getElementById('vfb-public-phone').textContent  = fb.Public_Phone || '—';

    const managerName = [fb.Manager_First_Name, fb.Manager_Last_Name].filter(Boolean).join(' ');
    document.getElementById('vfb-manager-name').textContent    = managerName || '—';
    document.getElementById('vfb-manager-email').textContent   = fb.Manager_Email || '—';
    document.getElementById('vfb-manager-phone').textContent   = fb.Manager_Phone || '—';
    document.getElementById('vfb-manager-address').textContent = fb.Manager_Address || '—';

    document.getElementById('viewFoodBankModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeViewFoodBankModal() {
    document.getElementById('viewFoodBankModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Edit Food Bank Modal ───────────────────────────────────
function openEditFoodBankModal(fb) {
    document.getElementById('efb-id').value            = fb.FoodBank_ID || '';
    document.getElementById('efb-name').value          = fb.Organization_Name || '';
    document.getElementById('efb-address').value       = fb.Physical_Address || '';
    document.getElementById('efb-org-email').value     = fb.Org_Email || '';
    document.getElementById('efb-verification').value  = fb.Verification_Status || 'Pending';
    document.getElementById('efb-org-status').value    = fb.Org_Status || 'Pending';
    document.getElementById('efb-time-open').value     = fb.Time_Open ? fb.Time_Open.substring(0, 5) : '';
    document.getElementById('efb-time-close').value    = fb.Time_Close ? fb.Time_Close.substring(0, 5) : '';
    document.getElementById('efb-days').value          = fb.Operating_Days || '';
    document.getElementById('efb-public-email').value  = fb.Public_Email || '';
    document.getElementById('efb-public-phone').value  = fb.Public_Phone || '';
    document.getElementById('efb-mgr-first').value     = fb.Manager_First_Name || '';
    document.getElementById('efb-mgr-last').value      = fb.Manager_Last_Name || '';
    document.getElementById('efb-mgr-email').value     = fb.Manager_Email || '';
    document.getElementById('efb-mgr-phone').value     = fb.Manager_Phone || '';
    document.getElementById('efb-mgr-address').value   = fb.Manager_Address || '';

    document.getElementById('editFoodBankModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditFoodBankModal() {
    document.getElementById('editFoodBankModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Delete Food Bank Modal ─────────────────────────────────
function openDeleteFoodBankModal(fb) {
    document.getElementById('dfb-id').value           = fb.FoodBank_ID || '';
    document.getElementById('dfb-name').textContent   = fb.Organization_Name || '—';
    document.getElementById('deleteFoodBankModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteFoodBankModal() {
    document.getElementById('deleteFoodBankModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Helper ─────────────────────────────────────────────────
function formatTimeFB(timeStr) {
    if (!timeStr) return '—';
    const [h, m] = timeStr.split(':');
    const hour   = parseInt(h);
    const ampm   = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${m} ${ampm}`;
}

// ── Init ───────────────────────────────────────────────────
function initFoodBankModals() {
    ['addFoodBankModal', 'viewFoodBankModal', 'editFoodBankModal', 'deleteFoodBankModal'].forEach(id => {
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
            closeAddFoodBankModal();
            closeViewFoodBankModal();
            closeEditFoodBankModal();
            closeDeleteFoodBankModal();
        }
    });
}