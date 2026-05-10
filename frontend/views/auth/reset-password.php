<?php
session_start();
require_once __DIR__ . '/../../../backend/helpers/auth_redirect.php';

send_no_store_headers();

// Must have passed OTP in reset mode
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    redirect_to_dashboard_or_login();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Food Bank App</title>
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
            <p class="desc">Enter a new password</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="error-msg">
                    <?php
                    $errs = [
                        'mismatch'       => 'Passwords do not match.',
                        'too_short'      => 'Password must be at least 8 characters.',
                        'missing_fields' => 'Please fill in all fields.',
                    ];
                    echo $errs[$_GET['error']] ?? 'An error occurred.';
                    ?>
                </p>
            <?php endif; ?>

            <form action="../../../backend/controllers/auth/process_reset_password.php" method="POST">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="create a new strong password" required>

                <label>Re-enter New Password</label>
                <input type="password" name="confirm_password" placeholder="re-enter your new password" required>

                <button type="submit" class="btn">Submit</button>
            </form>

            <div class="back-home">
                <a href="../../../login.php">← Back to home</a>
            </div>
        </div>
    </div>

</div>
</body>
</html>
