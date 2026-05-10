(function () {
    const base = window.FOODBANK_BASE_URL || (window.location.pathname.startsWith('/foodbank/') ? '/foodbank' : '');
    const appUrl = path => `${base}/${String(path || '').replace(/^\/+/, '')}`;

    fetch(appUrl('/backend/api/auth/session_status.php'), {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) return;

            document.querySelectorAll('[data-auth-link]').forEach(link => {
                link.hidden = true;
            });

            document.querySelectorAll('[data-dashboard-link]').forEach(link => {
                if (data.dashboard_url) {
                    link.href = data.dashboard_url;
                    link.hidden = false;
                }
            });
        })
        .catch(error => console.error('Auth status check failed:', error));
})();
