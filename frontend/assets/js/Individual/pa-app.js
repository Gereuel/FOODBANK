/**
 * pa-app.js
 * SPA navigation logic for the Personal Account dashboard.
 */

function paAppUrl(path) {
    const base = window.FOODBANK_BASE_URL || (window.location.pathname.startsWith('/foodbank/') ? '/foodbank' : '');
    return `${base}/${String(path || '').replace(/^\/+/, '')}`;
}

function loadPaComponent(containerId, filePath, callback = null) {
    fetch(filePath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Unable to load ${filePath}`);
            }

            return response.text();
        })
        .then(data => {
            const container = document.getElementById(containerId);
            if (!container) return;

            const headerWrapper = document.querySelector('.header-wrapper');
            if (headerWrapper) {
                headerWrapper.style.display = '';
            }

            container.innerHTML = data;

            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            setTimeout(() => {
                if (typeof initMap === 'function') {
                    initMap();

                    if (typeof map !== 'undefined') {
                        map.resize();
                    }
                }
            }, 150);

            if (callback) callback();
            window.scrollTo(0, 0);
        })
        .catch(error => {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>Unable to load this section.</p></div>';
            }
            console.error(error);
        });
}

document.addEventListener('click', function(e) {
    const favoriteBtn = e.target.closest('.fav-btn');
    if (favoriteBtn) {
        e.preventDefault();
        e.stopPropagation();

        const foodBankId = favoriteBtn.dataset.id;
        if (!foodBankId || favoriteBtn.dataset.loading === 'true') return;

        favoriteBtn.dataset.loading = 'true';
        const formData = new FormData();
        formData.append('foodbank_id', foodBankId);

        fetch(paAppUrl('/backend/controllers/individual/foodbanks/toggle_favorite.php'), {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to update favorite.');

                document.querySelectorAll(`.fav-btn[data-id="${foodBankId}"]`).forEach(button => {
                    button.classList.toggle('fav-btn--active', data.favorited);
                    button.setAttribute('aria-label', data.favorited ? 'Remove saved food bank' : 'Save food bank');

                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fas', data.favorited);
                        icon.classList.toggle('far', !data.favorited);
                    }
                });
            })
            .catch(error => alert(error.message))
            .finally(() => {
                favoriteBtn.dataset.loading = 'false';
            });
        return;
    }

    const navLink = e.target.closest('.nav-list a, .navigation-buttons a, .nav-link, .view-all-link');

    if (!navLink) return;

    let targetUrl = navLink.getAttribute('href');
    if (!targetUrl || targetUrl === '#') {
        targetUrl = navLink.dataset.target;
    }

    if (!targetUrl || targetUrl === '#' || targetUrl.startsWith('http')) {
        return;
    }

    e.preventDefault();
    loadPaComponent('pa-page-content', targetUrl);

    document.querySelectorAll('.nav-list li').forEach(el => el.classList.remove('active'));
    const navListItem = navLink.closest('.nav-list li');
    if (navListItem) {
        navListItem.classList.add('active');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const pageContent = document.getElementById('pa-page-content');

    if (pageContent && pageContent.innerHTML.trim() === '') {
        loadPaComponent('pa-page-content', paAppUrl('/frontend/views/individual/pa_foodbanks.php'));
    }
});
