<?php
session_start();
require_once __DIR__ . '/../../../backend/helpers/auth_redirect.php';

send_no_store_headers();
redirect_authenticated_user_to_dashboard();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Commissioner:wght@400;500;600&family=Roboto+Flex:wght@400;600;700&family=Roboto+Mono&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/pages/verification.css">
    <link rel="icon" type="image/png" href="/foodbank/frontend/assets/images/logo.png">
</head>
<body>
<div class="container">

    <div class="left">
        <img src="../../assets/images/logo.png" class="logo" alt="Food Bank Logo">
        <h1 class="welcome">Forgot Password?</h1>
        <p class="subtitle">Make a new and secure password</p>
    </div>

    <div class="right">
        <div class="right-inner">
            <h2 class="title">Forgot Password</h2>
            <p class="desc">Enter your email to receive a verification code</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="error-msg">
                    <?php
                    $errs = [
                        'not_found'     => 'No account found with that email.',
                        'missing_email' => 'Please enter your email address.',
                    ];
                    echo $errs[$_GET['error']] ?? 'An error occurred.';
                    ?>
                </p>
            <?php endif; ?>

            <form action="../../../backend/controllers/auth/process_forgot_password.php" method="POST">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your.email@example.com" required>
                <button type="submit" class="btn">Continue</button>
            </form>

            <div class="back-home">
                <a href="../../../login.php">← Back to home</a>
            </div>
        </div>
    </div>

</div>
</body>
</html>
