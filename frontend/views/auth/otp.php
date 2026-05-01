<?php
session_start();

if (!isset($_SESSION['pending_account_id'])) {
    header("Location: ../../../login.php");
    exit();
}

$method  = $_SESSION['otp_method'] ?? 'email';
$is_reset = isset($_SESSION['reset_mode']) && $_SESSION['reset_mode'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Code - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Commissioner:wght@400;500;600&family=Roboto+Flex:wght@400;600;700&family=Roboto+Mono&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/pages/verification.css">
    <link rel="icon" href="../../../favicon.ico">
</head>
<body>
<div class="container">

    <!-- LEFT PANEL -->
    <div class="left">
        <img src="../../assets/images/logo.png" class="logo" alt="Food Bank Logo">
        <h1 class="welcome"><?= $method === 'email' ? 'Email Verification' : 'SMS Verification' ?></h1>
        <p class="subtitle"><?= $method === 'email' ? 'Code sent to your email' : 'Code sent via text message' ?></p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right">
        <div class="right-inner">
            <h2 class="title">Enter Code</h2>
            <p class="desc">Please enter the 6-digit verification code</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="error-msg">
                    <?php
                    $errs = [
                        'invalid' => 'Incorrect code. Please try again.',
                        'expired' => 'Code has expired. Please resend.',
                    ];
                    echo $errs[$_GET['error']] ?? 'An error occurred.';
                    ?>
                </p>
            <?php endif; ?>

            <form action="../../../backend/controllers/auth/process_verify_otp.php" method="POST" id="otp-form">
                <div class="otp-boxes">
                    <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                    <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                    <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                    <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                    <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                    <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                </div>
                <input type="hidden" name="otp_code" id="otp-hidden">
                <button type="submit" class="btn" id="verify-btn" disabled>Verify</button>
            </form>

            <div class="otp-links">
                <p>Didn't receive code? <a href="#" id="resend-btn">Resend</a></p>
                <p class="otp-expiry">Code expires in <span id="countdown">5:00</span></p>
            </div>

            <div class="back-home">
                <a href="verification.php">← Change verification method</a>
            </div>
        </div>
    </div>

</div>

<script>
// ── OTP Box Logic ──────────────────────────────────────────
const inputs  = document.querySelectorAll('.otp-input');
const hidden  = document.getElementById('otp-hidden');
const verBtn  = document.getElementById('verify-btn');

inputs.forEach((input, i) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value && i < inputs.length - 1) inputs[i + 1].focus();
        updateHidden();
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !input.value && i > 0) {
            inputs[i - 1].focus();
        }
    });

    input.addEventListener('paste', e => {
        e.preventDefault();
        const text = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        text.split('').forEach((char, idx) => {
            if (inputs[idx]) inputs[idx].value = char;
        });
        updateHidden();
        inputs[Math.min(text.length, 5)].focus();
    });
});

function updateHidden() {
    const code = Array.from(inputs).map(i => i.value).join('');
    hidden.value = code;
    verBtn.disabled = code.length < 6;
}

// ── Countdown Timer ────────────────────────────────────────
let seconds = 300; // 5 minutes
const countdownEl = document.getElementById('countdown');

const timer = setInterval(() => {
    seconds--;
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    countdownEl.textContent = `${m}:${s.toString().padStart(2, '0')}`;
    if (seconds <= 0) {
        clearInterval(timer);
        countdownEl.textContent = '0:00';
        countdownEl.style.color = '#dc2626';
    }
}, 1000);

// ── Resend Button ──────────────────────────────────────────
document.getElementById('resend-btn').addEventListener('click', function(e) {
    e.preventDefault();
    fetch('../../../backend/controllers/auth/process_resend_otp.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Reset timer
                seconds = 300;
                countdownEl.style.color = '';
                inputs.forEach(i => i.value = '');
                inputs[0].focus();
                updateHidden();
            }
        });
});
</script>
</body>
</html>