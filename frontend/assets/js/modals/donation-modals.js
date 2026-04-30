// ── Current donation data ──────────────────────────────────
let currentDonation = null;

// ── Add Modal ──────────────────────────────────────────────
function openAddDonationModal() {
    document.getElementById('addDonationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeAddDonationModal() {
    document.getElementById('addDonationModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Donation Report Modal ──────────────────────────────────
function openDonationReport(d) {
    currentDonation = d;

    // Header
    document.getElementById('report-tracking').textContent        = 'Tracking Number: ' + (d.Tracking_Number || '—');
    document.getElementById('report-tracking-detail').textContent = d.Tracking_Number || '—';

    // Status
    const statusEl   = document.getElementById('report-status');
    const statusIcon  = document.getElementById('report-status-icon');
    statusEl.textContent = d.Status || '—';
    statusIcon.className = 'report-status-icon report-status-icon--' + (d.Status || 'Pending').toLowerCase().replace(' ', '-');

    // Generated on
    document.getElementById('report-generated-on').textContent = d.Generated_On
        ? new Date(d.Generated_On).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';

    // Donor info
    const fullName = [d.First_Name, d.Middle_Name, d.Last_Name].filter(Boolean).join(' ');
    document.getElementById('report-donor-name').textContent    = fullName || '—';
    document.getElementById('report-donor-email').textContent   = d.Email || '—';
    document.getElementById('report-donor-phone').textContent   = d.Phone_Number || '—';
    document.getElementById('report-donor-id').textContent      = d.Custom_App_ID || '—';
    document.getElementById('report-donor-address').textContent = d.Donor_Address || '—';

    // Donation details
    document.getElementById('report-quantity').textContent   = d.Quantity_Description || '—';
    document.getElementById('report-item-type').textContent  = d.Item_Type || '—';
    document.getElementById('report-date').textContent       = d.Date_Donated
        ? new Date(d.Date_Donated).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';
    document.getElementById('report-time').textContent       = d.Donation_Time
        ? formatTime(d.Donation_Time)
        : '—';

    // Food bank
    document.getElementById('report-fb-name').textContent     = d.Organization_Name || '—';
    document.getElementById('report-fb-id').textContent       = 'FB-' + (d.FoodBank_ID || '—');
    document.getElementById('report-fb-location').textContent = d.FoodBank_Address || '—';
    document.getElementById('report-fb-contact').textContent  = `Manager: ${d.Manager_First || ''} ${d.Manager_Last || ''}, Phone: ${d.Manager_Phone || '—'}`;

    // Proof of delivery
    const proofWrap     = document.getElementById('report-proof-wrap');
    const proofVerified = document.getElementById('report-proof-verified');
    if (d.Proof_Of_Delivery_URL) {
        proofWrap.innerHTML     = `<img src="${d.Proof_Of_Delivery_URL}" alt="Proof of Delivery" class="report-proof-img">`;
        proofVerified.style.display = 'block';
    } else {
        proofWrap.innerHTML         = `<p class="report-no-proof">No proof of delivery uploaded.</p>`;
        proofVerified.style.display = 'none';
    }

    // Notes
    document.getElementById('report-notes').textContent = d.Notes || 'No additional notes.';

    // Edit button
    document.getElementById('report-edit-btn').onclick = function () {
        closeDonationReport();
        openEditDonationModal(d);
    };

    document.getElementById('donationReportModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDonationReport() {
    document.getElementById('donationReportModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function formatTime(timeStr) {
    const [h, m] = timeStr.split(':');
    const hour   = parseInt(h);
    const ampm   = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${m} ${ampm}`;
}

function printDonationReport() {
    const content = document.getElementById('report-printable').innerHTML;
    const win     = window.open('', '_blank');
    win.document.write(`
        <!DOCTYPE html><html><head><title>Donation Report</title>
        <style>
            body { font-family: sans-serif; padding: 32px; color: #111; }
            h2   { color: #1a4731; }
            .report-section { margin-bottom: 24px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
            .report-section-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
            .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
            .report-field-label { font-size: 12px; color: #6b7280; display: block; }
            .report-field-value { font-weight: 600; font-size: 14px; display: block; }
            .report-status-bar { display: flex; justify-content: space-between; padding: 16px; background: #f3f4f6; border-radius: 8px; margin-bottom: 24px; }
            .report-proof-img { width: 100%; border-radius: 8px; }
            .report-section-bg, .report-close, .report-header-actions { display: none; }
        </style>
        </head><body>${content}</body></html>
    `);
    win.document.close();
    win.print();
}

function downloadDonationReport() {
    printDonationReport(); // triggers browser save as PDF via print dialog
}

// ── Edit Donation Modal ────────────────────────────────────
function openEditDonationModal(d) {
    document.getElementById('edit-donation-id').value       = d.Donation_ID || '';
    document.getElementById('edit-donation-status').value   = d.Status || '';
    document.getElementById('edit-donation-quantity').value = d.Quantity_Description || '';
    document.getElementById('edit-donation-notes').value    = d.Notes || '';
    document.getElementById('editDonationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeEditDonationModal() {
    document.getElementById('editDonationModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Delete Donation Modal ──────────────────────────────────
function openDeleteDonationModal(d) {
    document.getElementById('delete-donation-id').value = d.Donation_ID || '';
    document.getElementById('delete-donation-tracking').textContent = d.Tracking_Number || '—';
    document.getElementById('deleteDonationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeDeleteDonationModal() {
    document.getElementById('deleteDonationModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// ── Init (called by app.js initPageScripts) ────────────────
function initDonationModals() {
    ['donationReportModal', 'addDonationModal', 'editDonationModal', 'deleteDonationModal'].forEach(id => {
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
            closeDonationReport();
            closeAddDonationModal();
            closeEditDonationModal();
            closeDeleteDonationModal();
        }
    });
}