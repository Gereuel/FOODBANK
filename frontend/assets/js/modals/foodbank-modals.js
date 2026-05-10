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
    syncOperatingDaysPickers(stepEl);
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

const FB_DAY_ORDER = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function compressOperatingDays(days) {
    const selected = FB_DAY_ORDER.filter(day => days.includes(day));

    if (selected.length === 7) {
        return 'Daily';
    }

    if (!selected.length) {
        return '';
    }

    const indexes = selected.map(day => FB_DAY_ORDER.indexOf(day));
    const isContiguous = indexes.every((index, position) => position === 0 || index === indexes[position - 1] + 1);

    if (isContiguous && selected.length > 1) {
        return `${selected[0]}-${selected[selected.length - 1]}`;
    }

    return selected.join(', ');
}

function syncOperatingDaysPicker(picker) {
    const hidden = document.getElementById(picker.dataset.hiddenTarget);
    if (!hidden) return;

    const selectedDays = Array.from(picker.querySelectorAll('input[type="checkbox"]:checked'))
        .map(input => input.value);
    hidden.value = compressOperatingDays(selectedDays);
}

function syncOperatingDaysPickers(scope = document) {
    scope.querySelectorAll('.operating-days-picker').forEach(syncOperatingDaysPicker);
}

function setOperatingDaysPicker(hiddenId, value) {
    const hidden = document.getElementById(hiddenId);
    const picker = document.querySelector(`.operating-days-picker[data-hidden-target="${hiddenId}"]`);
    if (!hidden || !picker) return;

    hidden.value = value || '';
    const normalized = parseOperatingDays(value || '');
    picker.querySelectorAll('input[type="checkbox"]').forEach(input => {
        input.checked = normalized.includes(input.value);
    });
    syncOperatingDaysPicker(picker);
}

function parseOperatingDays(value) {
    const text = String(value || '').trim().toLowerCase();
    if (!text) return [];
    if (['daily', 'everyday', 'every day'].includes(text)) return [...FB_DAY_ORDER];

    const selected = new Set();
    const dayPattern = /(mon(?:day)?|tue(?:sday)?|tues(?:day)?|wed(?:nesday)?|thu(?:rsday)?|thur(?:sday)?|thurs(?:day)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)(?:\s*(?:-|to)\s*(mon(?:day)?|tue(?:sday)?|tues(?:day)?|wed(?:nesday)?|thu(?:rsday)?|thur(?:sday)?|thurs(?:day)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?))?/gi;
    let match;

    while ((match = dayPattern.exec(text)) !== null) {
        const start = dayIndexFromText(match[1]);
        const end = match[2] ? dayIndexFromText(match[2]) : start;
        if (start === -1 || end === -1) continue;

        if (start <= end) {
            for (let i = start; i <= end; i++) selected.add(FB_DAY_ORDER[i]);
        } else {
            for (let i = start; i < FB_DAY_ORDER.length; i++) selected.add(FB_DAY_ORDER[i]);
            for (let i = 0; i <= end; i++) selected.add(FB_DAY_ORDER[i]);
        }
    }

    return FB_DAY_ORDER.filter(day => selected.has(day));
}

function dayIndexFromText(day) {
    const key = String(day || '').slice(0, 3).toLowerCase();
    return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'].indexOf(key);
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
    setOperatingDaysPicker('efb-days', fb.Operating_Days || '');
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

    document.querySelectorAll('.operating-days-picker input[type="checkbox"]').forEach(input => {
        if (input.dataset.daysBound === 'true') return;
        input.dataset.daysBound = 'true';
        input.addEventListener('change', () => {
            const picker = input.closest('.operating-days-picker');
            if (picker) syncOperatingDaysPicker(picker);
        });
    });

    ['addFoodBankForm', 'editFoodBankForm'].forEach(id => {
        const form = document.getElementById(id);
        if (!form) return;
        if (form.dataset.daysSubmitBound === 'true') return;
        form.dataset.daysSubmitBound = 'true';
        form.addEventListener('submit', event => {
            syncOperatingDaysPickers(form);
            const daysInput = form.querySelector('input[name="operating_days"]');
            if (daysInput && !daysInput.value) {
                event.preventDefault();
                alert('Please select at least one operating day.');
            }
        });
    });
}
