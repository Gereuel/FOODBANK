<?php
session_start();

// Must come from login flow
if (!isset($_SESSION['pending_account_id'])) {
    header("Location: ../../../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Commissioner:wght@400;500;600&family=Roboto+Flex:wght@400;600;700&family=Roboto+Mono&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/pages/verification.css">
    <link rel="icon" href="../../../favicon.ico">
</head>
<body>
<div class="container">

    <!-- LEFT PANEL -->
    <div class="left">
        <img src="../../assets/images/logo.png" class="logo" alt="Food Bank Logo">
        <h1 class="welcome">Verification</h1>
        <p class="subtitle">Choose your preferred verification method</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right">
        <div class="right-inner">
            <h2 class="title">Select Method</h2>
            <p class="desc">How would you like to receive your verification code?</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="error-msg">Failed to send code. Please try again.</p>
            <?php endif; ?>

            <form action="../../../backend/controllers/auth/process_send_otp.php" method="POST">

                <button type="submit" name="method" value="email" class="method-card">
                    <div class="method-icon">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="method-text">
                        <div class="method-title">Email Verification</div>
                        <div class="method-desc">Receive code via email</div>
                    </div>
                </button>

                <button type="submit" name="method" value="sms" class="method-card">
                    <div class="method-icon">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                    </div>
                    <div class="method-text">
                        <div class="method-title">SMS Verification</div>
                        <div class="method-desc">Receive code via text message</div>
                    </div>
                </button>

            </form>

            <div class="back-home">
                <a href="../../../login.php">← Back</a>
            </div>
        </div>
    </div>

</div>
</body>
</html>