<?php
require_once 'backend/config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Commissioner:wght@400;500;600&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="frontend/assets/css/pages/login.css">
    <link rel="icon" href="favicon.ico">
</head>
<body>
<div class="container">

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
      if(isset($_GET['error'])) {
          if($_GET['error'] == 'empty_fields') {
              echo '<p style="color: #8F1402; margin-bottom: 15px; font-size: 14px; font-weight: 500;">Please fill in all fields.</p>';
          } elseif($_GET['error'] == 'invalid_credentials') {
              echo '<p style="color: #8F1402; margin-bottom: 15px; font-size: 14px; font-weight: 500;">Incorrect email or password. Please try again.</p>';
          }
      } elseif(isset($_GET['status'])) {
          if($_GET['status'] == 'success') {
              echo '<p style="color: #3E8B34; margin-bottom: 15px; font-size: 14px; font-weight: 500;">Registration successful! Please log in.</p>';
          } elseif($_GET['status'] == 'logged_out') {
              echo '<p style="color: #3E8B34; margin-bottom: 15px; font-size: 14px; font-weight: 500;">You have been successfully logged out.</p>';
          }
      }
      ?>

      <form action="backend/controllers/process_login.php" method="POST" class="login-form">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="your.email@example.com" required>

        <div class="label-row">
          <label>Password</label>
        </div>
        <input type="password" name="password" placeholder="Enter your password" required>
        <a href="#" class="forgot-pass">Forgot password?</a>

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