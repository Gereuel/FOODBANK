function loadFbComponent(containerId, filePath, callback = null) {
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

            const headerWrapper = document.querySelector('.fb-header-wrapper');
            if (headerWrapper) {
                headerWrapper.style.display = '';
            }

            container.innerHTML = data;

            container.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            if (callback) callback();
            const main = document.getElementById('fb-main-content');
            if (main) main.scrollTop = 0;
        })
        .catch(error => {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '<div class="fb-empty-state"><i class="fas fa-circle-exclamation"></i><p>Unable to load this section.</p></div>';
            }
            console.error(error);
        });
}

document.addEventListener('click', event => {
    const navLink = event.target.closest('.nav-link');
    if (!navLink) return;

    let targetUrl = navLink.getAttribute('href');
    if (!targetUrl || targetUrl === '#') {
        targetUrl = navLink.dataset.target;
    }

    if (!targetUrl || targetUrl === '#' || targetUrl.startsWith('http')) {
        return;
    }

    event.preventDefault();
    loadFbComponent('fb-page-content', targetUrl);

    document.querySelectorAll('.nav-list li').forEach(item => item.classList.remove('active'));
    const item = navLink.closest('.nav-list li');
    if (item) item.classList.add('active');
});
