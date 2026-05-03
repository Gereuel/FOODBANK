function initToolbar() {
    const searchInput    = document.getElementById('search-input');
    const filterBtn      = document.getElementById('filter-btn');
    const filterDropdown = document.getElementById('filter-dropdown');
    const filterBadge    = document.getElementById('filter-badge');
    const filterApplyBtn = document.getElementById('filter-apply-btn');
    const filterResetBtn = document.getElementById('filter-reset-btn');
    const exportBtn      = document.getElementById('export-btn');
    const exportDropdown = document.getElementById('export-dropdown');
    const exportCsvBtn   = document.getElementById('export-csv-btn');
    const exportPdfBtn   = document.getElementById('export-pdf-btn');
    const showAllBtn     = document.getElementById('show-all-btn');
    const tableBody      = document.querySelector('.data-table tbody');

    // Guard — minimum requirement is search + table
    if (!searchInput || !tableBody) return;

    let activeFilters = { roles: [], statuses: [], search: '' };
    const rows = () => Array.from(tableBody.querySelectorAll('tr'));

    // ── Search — always init if elements exist ─────────────
    searchInput.addEventListener('input', function () {
        activeFilters.search = this.value.toLowerCase().trim();
        applyFilters();
    });

    // ── Filter — only init if elements exist ───────────────
    if (filterBtn && filterDropdown) {
        filterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            filterDropdown.classList.toggle('show');
            if (exportDropdown) exportDropdown.classList.remove('show');
        });
        filterDropdown.addEventListener('click', e => e.stopPropagation());
    }

    // ── Export — only init if elements exist ───────────────
    if (exportBtn && exportDropdown) {
        exportBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            exportDropdown.classList.toggle('show');
            if (filterDropdown) filterDropdown.classList.remove('show');
        });
        exportDropdown.addEventListener('click', e => e.stopPropagation());
    }

    // ── Close dropdowns on outside click ──────────────────
    document.addEventListener('click', function () {
        if (filterDropdown) filterDropdown.classList.remove('show');
        if (exportDropdown) exportDropdown.classList.remove('show');
    });

    // ── Apply Filter ───────────────────────────────────────
    if (filterApplyBtn) {
        filterApplyBtn.addEventListener('click', function () {
            activeFilters.roles    = [...document.querySelectorAll('input[name="role"]:checked')].map(i => i.value);
            activeFilters.statuses = [...document.querySelectorAll('input[name="status"]:checked')].map(i => i.value);
            applyFilters();
            updateFilterBadge();
            if (filterDropdown) filterDropdown.classList.remove('show');
        });
    }

    // ── Reset / Show All ───────────────────────────────────
    function resetFilters() {
        activeFilters = { roles: [], statuses: [], search: '' };
        searchInput.value = '';
        document.querySelectorAll('input[name="role"]').forEach(i => i.checked = false);
        document.querySelectorAll('input[name="status"]').forEach(i => i.checked = false);
        rows().forEach(row => row.style.display = '');
        updateFilterBadge();
        if (filterDropdown) filterDropdown.classList.remove('show');
    }

    if (filterResetBtn) filterResetBtn.addEventListener('click', resetFilters);
    if (showAllBtn)     showAllBtn.addEventListener('click', resetFilters);

    // ── Apply Filters to Table ─────────────────────────────
    function applyFilters() {
        rows().forEach(row => {
            const cells      = row.querySelectorAll('td');
            const name       = cells[0]?.textContent.toLowerCase() || '';
            const email      = cells[1]?.textContent.toLowerCase() || '';
            const roleText   = cells[2]?.textContent.trim() || '';
            const roleValue  = getRoleValue(roleText);
            const statusText = cells[4]?.textContent.trim().toLowerCase() || '';
            const id         = cells[3]?.textContent.toLowerCase() || '';

            const searchMatch = !activeFilters.search ||
                name.includes(activeFilters.search) ||
                email.includes(activeFilters.search) ||
                id.includes(activeFilters.search) ||
                roleText.toLowerCase().includes(activeFilters.search);

            const roleMatch = activeFilters.roles.length === 0 ||
                activeFilters.roles.includes(roleValue);

            const statusMatch = activeFilters.statuses.length === 0 ||
                activeFilters.statuses.includes(statusText);

            row.style.display = (searchMatch && roleMatch && statusMatch) ? '' : 'none';
        });
    }

    function getRoleValue(roleText) {
        const map = { 'Donor': 'PA', 'Food Bank Manager': 'FA', 'Admin': 'AA' };
        return map[roleText] || roleText;
    }

    function updateFilterBadge() {
        if (!filterBadge) return;
        const count = activeFilters.roles.length + activeFilters.statuses.length;
        filterBadge.textContent   = count;
        filterBadge.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    // ── Export CSV ─────────────────────────────────────────
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function () {
            const visibleRows = rows().filter(r => r.style.display !== 'none');
            const headers     = ['Name', 'Email', 'Role', 'Location', 'Status', 'ID'];
            const csvRows     = [headers.join(',')];
            visibleRows.forEach(row => {
                const cells  = row.querySelectorAll('td');
                const values = Array.from(cells).slice(0, 6).map(cell => {
                    const text = cell.textContent.trim().replace(/"/g, '""');
                    return `"${text}"`;
                });
                csvRows.push(values.join(','));
            });
            downloadFile(csvRows.join('\n'), 'users-export.csv', 'text/csv');
            exportDropdown.classList.remove('show');
        });
    }

    // ── Export PDF ─────────────────────────────────────────
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function () {
            const visibleRows = rows().filter(r => r.style.display !== 'none');
            const headers     = ['Name', 'Email', 'Role', 'Location', 'Status', 'ID'];
            let tableHtml = `<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;font-family:sans-serif;font-size:13px;"><thead style="background:#1a4731;color:white;"><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>`;
            visibleRows.forEach((row, i) => {
                const cells = row.querySelectorAll('td');
                const bg    = i % 2 === 0 ? '#f9fafb' : '#ffffff';
                tableHtml  += `<tr style="background:${bg};">`;
                Array.from(cells).slice(0, 6).forEach(cell => {
                    tableHtml += `<td>${cell.textContent.trim()}</td>`;
                });
                tableHtml += `</tr>`;
            });
            tableHtml += `</tbody></table>`;
            const win = window.open('', '_blank');
            win.document.write(`<!DOCTYPE html><html><head><title>Export</title></head><body>${tableHtml}</body></html>`);
            win.document.close();
            win.print();
            exportDropdown.classList.remove('show');
        });
    }

    // ── Download Helper ────────────────────────────────────
    function downloadFile(content, filename, type) {
        const blob = new Blob([content], { type });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

} // end initToolbar