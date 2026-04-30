<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css" href="frontend/assets/css/pages/index.css?v=1.1">
    <link rel="icon" href="favicon.ico">
</head>
<body>
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-veil"></div>
        <div class="hero-grain"></div>
        
        <!-- NAV -->
        <nav>
            <a href="index.php" class="logo">
            <div class="logo-icon">
                <img src="frontend/assets/images/logo.png"  alt="FoodBank App Logo">
            </div>
            <div class="logo-text">
                <strong>Food Bank</strong>
                <span>App</span>
            </div>
            </a>
        </nav>
        
        <!-- CONTENT -->
        <div class="hero-content">
        
            <h1 class="hero-headline">
            Every <em>Contribution</em><br>Counts
            </h1>
        
            <p class="hero-sub">
            Join thousands of donors making a real difference in the fight against hunger.
            Your donation helps provide nutritious meals to families in need across our community.
            </p>
        
            <!-- Stats -->
            <div class="stats" id="stats-container">
            </div>
        
            <!-- Buttons -->
            <div class="cta-group">
            <a href="login.php" class="btn btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                </svg>
                Login
            </a>
            <a href="signup.php" class="btn btn--outline">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8v6M23 11h-6"/>
                </svg>
                Sign Up Free
            </a>
            </div>
        </div>
        
        <!-- Bottom strip -->
        <footer class="strip">
            <div class="strip-left">FoodBank App © 2026 — Making Every Meal Matter</div>
            <div class="strip-right">
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <a href="#">Donate</a>
            </div>
        </footer>
    </section>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        fetch('frontend/assets/json/stats.json')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                const statsContainer = document.getElementById('stats-container');
                statsContainer.innerHTML = '';
                
                data.forEach((stat, index) => {
                    const statDiv = document.createElement('div');
                    statDiv.className = 'stat';
                    statDiv.innerHTML = `
                        <div class="stat-num">${stat.value}<span>${stat.suffix}</span></div>
                        <div class="stat-label">${stat.label}</div>
                    `;
                    statsContainer.appendChild(statDiv);

                    if (index < data.length - 1) {
                        const dividerDiv = document.createElement('div');
                        dividerDiv.className = 'stat-divider';
                        statsContainer.appendChild(dividerDiv);
                    }
                });
            })
            .catch(error => {
                console.error('Error loading stats:', error);
                const statsContainer = document.getElementById('stats-container');
                statsContainer.innerHTML = '<p style="color: #ffb74d; font-size: 0.9rem;">Could not load stats at the moment.</p>';
            });
    });
    </script>
</body>
</html>