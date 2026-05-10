<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/helpers/auth_redirect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

send_no_store_headers();
redirect_authenticated_user_to_dashboard();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Commissioner:wght@400;500;600&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="frontend/assets/css/pages/login.css">
    <link rel="icon" type="image/png" href="/foodbank/frontend/assets/images/logo.png">
</head>

<body>
<div class="container">

  <!-- Account Disabled Modal -->
  <div id="disabledModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; z-index:99999;">
      <div style="background:#fff; border-radius:12px; max-width:420px; width:90%; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.2); font-family:'Commissioner',sans-serif;">
          
          <!-- Red Header -->
          <div style="background:#dc2626; padding:24px; display:flex; justify-content:space-between; align-items:center;">
              <div>
                  <h2 style="color:#fff; margin:0; font-family:'DM Serif Display',serif; font-size:20px;">Account Disabled</h2>
                  <p style="color:rgba(255,255,255,0.75); margin:4px 0 0; font-size:13px;">Your access has been restricted.</p>
              </div>
              <button onclick="closeDisabledModal()" style="background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:20px; display:grid; place-items:center;">&times;</button>
          </div>

          <!-- Body -->
          <div style="padding:28px 24px; text-align:center;">
              <div style="width:64px; height:64px; background:#fee2e2; border-radius:50%; display:grid; place-items:center; margin:0 auto 16px;">
                  <svg width="28" height="28" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10"/>
                      <line x1="12" y1="8" x2="12" y2="12"/>
                      <line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
              </div>
              <p style="font-size:15px; font-weight:600; color:#1a1a1a; margin:0 0 8px;">Your account has been disabled.</p>
              <p style="font-size:13px; color:#6b7280; margin:0 0 24px; line-height:1.6;">Please contact the administrator to restore access to your account.</p>
              <button onclick="closeDisabledModal()" style="background:#dc2626; color:#fff; border:none; border-radius:8px; padding:10px 32px; font-size:14px; font-weight:600; cursor:pointer; font-family:'Commissioner',sans-serif;">
                  OK, Got It
              </button>
          </div>
      </div>
  </div>

  <script>
      (function () {
          const params = new URLSearchParams(window.location.search);
          if (params.get('error') === 'account_disabled') {
              document.getElementById('disabledModal').style.display = 'flex';
              window.history.replaceState({}, '', window.location.pathname);
          }
      })();

      function closeDisabledModal() {
          document.getElementById('disabledModal').style.display = 'none';
      }

      document.getElementById('disabledModal').addEventListener('click', function (e) {
          if (e.target === this) closeDisabledModal();
      });
  </script>

  <!-- LEFT PANEL -->
  <div class="left">
    <img src="frontend/assets/images/logo.png" class="logo" alt="Food Bank Logo">
    <h1 class="welcome">Welcome Back!</h1>
    <p class="subtitle">Login to continue making a difference</p>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right">
    <h2 class="title">Login</h2>
    <p class="desc">Enter your credentials to continue</p>

      
      <?php 
      // Display error or success messages
      if (isset($_GET['error'])) {
          $errors = [
              'empty_fields'         => 'Please fill in all fields.',
              'invalid_credentials'  => 'Incorrect email or password. Please try again.',
              'unknown_account_type' => 'Unknown account type. Please contact support.',
          ];
          // account_disabled is handled by the modal below, skip it here
          if (isset($errors[$_GET['error']])) {
              echo '<p style="color:#8F1402;margin-bottom:15px;font-size:14px;font-weight:500;">' . $errors[$_GET['error']] . '</p>';
          }

      } elseif (isset($_GET['status'])) {
          $statuses = [
              'success'        => 'Registration successful! Please log in.',
              'logged_out'     => 'You have been successfully logged out.',
              'password_reset' => 'Password reset successfully! Please log in.',
          ];
          $msg = $statuses[$_GET['status']] ?? null;
          if ($msg) {
              echo '<p style="color:#3E8B34;margin-bottom:15px;font-size:14px;font-weight:500;">' . $msg . '</p>';
          }
      }
      ?>

      <form action="backend/controllers/auth/process_login.php" method="POST" class="login-form">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="your.email@example.com" required>

        <div class="label-row">
          <label>Password</label>
        </div>
        <input type="password" name="password" placeholder="Enter your password" required>
        <a href="frontend/views/auth/forgot-password.php" class="forgot-pass">Forgot password?</a>

        <button type="submit" class="btn">Login</button>

        <div class="login-link">
          Don't have an account? <a href="signup.php">Sign Up</a>
        </div>

        <div class="back-home">
          <a href="index.php">← Back to home</a>
        </div>
      </form>

  </div>
</div>


</body>
</html>
