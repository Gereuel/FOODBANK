/**
 * pa-app.js
 * SPA Navigation Logic for the Personal Account (Individual) Dashboard
 */

// 1. Core Function: Load Page Fragments
function loadPaComponent(containerId, filePath, callback = null) {
    // Show a small loader if necessary, then fetch
    fetch(filePath)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok: ' + filePath);
            return response.text();
        })
        .then(data => {
            const container = document.getElementById(containerId);
            if (!container) return;

            // Inject the HTML content
            container.innerHTML = data;
            
            // Manually execute scripts found in the injected HTML to ensure 
            // page-specific logic (like chat or maps) actually runs.
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            if (callback) callback();
            
            // Optional: Scroll to top on navigation
            window.scrollTo(0, 0);
        })
        .catch(error => console.error(`SPA Load Error:`, error));
}

// 2. Global Event Listeners for Navigation
document.addEventListener('click', function(e) {
    // Look for links in your sidebar nav (handles both 'nav-item' and button styles)
    const navLink = e.target.closest('.nav-list a, .navigation-buttons a');
    
    if (navLink) {
        const targetUrl = navLink.getAttribute('href');
        
        // Filter: only handle internal PHP files, ignore external links or stubs
        if (targetUrl && targetUrl !== '#' && !targetUrl.startsWith('http')) {
            e.preventDefault();
            
            // Use 'pa-main-content' as the target ID (ensure this exists in pa_index.php)
            loadPaComponent('pa-main-content', targetUrl);

            // --- UI Cleanup: Update Active States ---
            
            // Remove active from all possible sidebar items
            document.querySelectorAll('.nav-item, .navigation-buttons button').forEach(el => {
                el.classList.remove('active');
            });

            // Apply active to the clicked item
            if (navLink.classList.contains('nav-item')) {
                navLink.classList.add('active');
            } else {
                const btn = navLink.querySelector('button');
                if (btn) btn.classList.add('active');
            }
        }
    }
});

// 3. Dashboard Initialization
document.addEventListener('DOMContentLoaded', () => {
    const mainDisplay = document.getElementById('pa-main-content');
    
    // If the shell is empty on load, pull in the default home page
    if (mainDisplay && mainDisplay.innerHTML.trim() === '') {
        console.log('SPA: Initializing default Individual Dashboard view...');
        loadPaComponent('pa-main-content', 'pa_home_page.php');
    }
});
