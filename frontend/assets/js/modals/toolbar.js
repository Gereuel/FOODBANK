function initToolbar() {
    const toolbars = document.querySelectorAll('.table-toolbar');

    toolbars.forEach(toolbar => {
        if (toolbar.dataset.toolbarInitialized === 'true') return;

        const tableCard = toolbar.closest('.table-card') || toolbar.parentElement;
        const tableBody = tableCard ? tableCard.querySelector('.data-table tbody') : document.querySelector('.data-table tbody');
        const searchInput = toolbar.querySelector('#search-input');
        const filterBtn = toolbar.querySelector('#filter-btn');
        const filterDropdown = toolbar.querySelector('#filter-dropdown');
        const filterBadge = toolbar.querySelector('#filter-badge');
        const filterApplyBtn = toolbar.querySelector('#filter-apply-btn');
        const filterResetBtn = toolbar.querySelector('#filter-reset-btn');
        const exportBtn = toolbar.querySelector('#export-btn');
        const exportDropdown = toolbar.querySelector('#export-dropdown');
        const exportCsvBtn = toolbar.querySelector('#export-csv-btn');
        const exportPdfBtn = toolbar.querySelector('#export-pdf-btn');
        const showAllBtn = toolbar.querySelector('#show-all-btn');

        if (!tableBody && !filterBtn && !exportBtn) return;

        toolbar.dataset.toolbarInitialized = 'true';
        const rows = () => tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];

        const closeDropdowns = () => {
            if (filterDropdown) filterDropdown.classList.remove('show');
            if (exportDropdown) exportDropdown.classList.remove('show');
        };

        const pageMode = detectToolbarMode(toolbar, tableBody);
        const activeFilters = { search: '', verification: [], orgStatuses: [], roles: [], statuses: [] };

        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function () {
                activeFilters.search = this.value.toLowerCase().trim();
                applyFilters();
            });
        }

        if (filterBtn && filterDropdown) {
            filterBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                filterDropdown.classList.toggle('show');
                if (exportDropdown) exportDropdown.classList.remove('show');
            });
            filterDropdown.addEventListener('click', e => e.stopPropagation());
        }

        if (exportBtn && exportDropdown) {
            exportBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                exportDropdown.classList.toggle('show');
                if (filterDropdown) filterDropdown.classList.remove('show');
            });
            exportDropdown.addEventListener('click', e => e.stopPropagation());
        }

        document.addEventListener('click', closeDropdowns);

        if (filterApplyBtn) {
            filterApplyBtn.addEventListener('click', function (e) {
                e.preventDefault();
                activeFilters.roles = checkedValues(toolbar, 'role');
                activeFilters.statuses = checkedValues(toolbar, 'status').map(value => value.toLowerCase());
                activeFilters.verification = checkedValues(toolbar, 'verification');
                activeFilters.orgStatuses = checkedValues(toolbar, 'org_status');
                applyFilters();
                updateFilterBadge();
                closeDropdowns();
            });
        }

        function resetFilters(e) {
            if (e) e.preventDefault();
            activeFilters.search = '';
            activeFilters.roles = [];
            activeFilters.statuses = [];
            activeFilters.verification = [];
            activeFilters.orgStatuses = [];

            if (searchInput) searchInput.value = '';
            toolbar.querySelectorAll('input[type="checkbox"]').forEach(input => input.checked = false);
            rows().forEach(row => row.style.display = '');
            updateFilterBadge();
            closeDropdowns();
        }

        if (filterResetBtn) filterResetBtn.addEventListener('click', resetFilters);
        if (showAllBtn) showAllBtn.addEventListener('click', resetFilters);

        if (exportCsvBtn && tableBody) {
            exportCsvBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const headers = tableHeaders(tableBody);
                const csvRows = [headers.join(',')];

                rows().filter(row => row.style.display !== 'none').forEach(row => {
                    const values = Array.from(row.querySelectorAll('td')).slice(0, headers.length).map(cell => {
                        const text = cell.textContent.trim().replace(/"/g, '""');
                        return `"${text}"`;
                    });
                    csvRows.push(values.join(','));
                });

                downloadFile(csvRows.join('\n'), `${pageMode}-export.csv`, 'text/csv');
                closeDropdowns();
            });
        }

        if (exportPdfBtn && tableBody) {
            exportPdfBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const headers = tableHeaders(tableBody);
                let tableHtml = `<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;font-family:sans-serif;font-size:13px;"><thead style="background:#1a4731;color:white;"><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>`;

                rows().filter(row => row.style.display !== 'none').forEach((row, index) => {
                    const bg = index % 2 === 0 ? '#f9fafb' : '#ffffff';
                    tableHtml += `<tr style="background:${bg};">`;
                    Array.from(row.querySelectorAll('td')).slice(0, headers.length).forEach(cell => {
                        tableHtml += `<td>${cell.textContent.trim()}</td>`;
                    });
                    tableHtml += '</tr>';
                });

                tableHtml += '</tbody></table>';
                const win = window.open('', '_blank');
                if (!win) return;
                win.document.write(`<!DOCTYPE html><html><head><title>Export</title></head><body>${tableHtml}</body></html>`);
                win.document.close();
                win.print();
                closeDropdowns();
            });
        }

        function applyFilters() {
            rows().forEach(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                const rowText = row.textContent.toLowerCase();
                const searchMatch = !activeFilters.search || rowText.includes(activeFilters.search);
                let filterMatch = true;

                if (pageMode === 'foodbanks') {
                    const verificationText = cells[4]?.textContent.trim() || '';
                    const orgStatusText = row.dataset.orgStatus || '';
                    filterMatch =
                        (activeFilters.verification.length === 0 || activeFilters.verification.includes(verificationText)) &&
                        (activeFilters.orgStatuses.length === 0 || activeFilters.orgStatuses.includes(orgStatusText));
                } else {
                    const roleText = cells[2]?.textContent.trim() || '';
                    const roleValue = getRoleValue(roleText);
                    const statusText = cells[4]?.textContent.trim().toLowerCase() || '';
                    filterMatch =
                        (activeFilters.roles.length === 0 || activeFilters.roles.includes(roleValue)) &&
                        (activeFilters.statuses.length === 0 || activeFilters.statuses.includes(statusText));
                }

                row.style.display = (searchMatch && filterMatch) ? '' : 'none';
            });
        }

        function updateFilterBadge() {
            if (!filterBadge) return;
            const count = activeFilters.roles.length + activeFilters.statuses.length +
                activeFilters.verification.length + activeFilters.orgStatuses.length;
            filterBadge.textContent = count;
            filterBadge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    });
}

function checkedValues(scope, name) {
    return Array.from(scope.querySelectorAll(`input[name="${name}"]:checked`)).map(input => input.value);
}

function detectToolbarMode(toolbar, tableBody) {
    if (toolbar.querySelector('input[name="verification"], input[name="org_status"]')) return 'foodbanks';
    if (!tableBody) return 'table';

    const headers = tableHeaders(tableBody).map(header => header.toLowerCase());
    if (headers.includes('office #')) return 'foodbanks';
    if (headers.includes('role')) return 'users';
    return 'table';
}

function tableHeaders(tableBody) {
    const table = tableBody.closest('table');
    if (!table) return [];

    return Array.from(table.querySelectorAll('thead th'))
        .map(th => th.textContent.trim())
        .filter(text => text && text.toLowerCase() !== 'actions');
}

function getRoleValue(roleText) {
    const map = { Donor: 'PA', 'Food Bank Manager': 'FA', Admin: 'AA' };
    return map[roleText] || roleText;
}

function downloadFile(content, filename, type) {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
