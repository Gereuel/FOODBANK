<?php
require_once __DIR__ . '/backend/config/app.php';

$signupError = $_GET['error'] ?? '';
$signupErrorMessage = '';

if ($signupError === 'email_exists') {
    $signupErrorMessage = 'This email is already registered. Please use another email or log in to your existing account.';
} elseif ($signupError === 'registration_failed') {
    $signupErrorMessage = 'We could not complete your registration. Please check your details and try again.';
} elseif ($signupError === 'invalid_account_type') {
    $signupErrorMessage = 'Please select a valid account type.';
} elseif ($signupError === 'underage') {
    $signupErrorMessage = 'You must be at least 18 years old to create an account.';
} elseif ($signupError === 'invalid_birthdate') {
    $signupErrorMessage = 'Please enter a valid birthdate.';
}

$accountStepErrors = ['email_exists', 'registration_failed', 'invalid_account_type'];
$showAccountStep = in_array($signupError, $accountStepErrors, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=Commissioner:wght@300;400;500&family=DM+Serif+Display&family=Inter:wght@400;600&family=Roboto+Flex:wght@400;600&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="frontend/assets/css/pages/signup.css">
    <link rel="icon" type="image/png" href="/foodbank/frontend/assets/images/logo.png">
</head>
<body>
<div class="container">

  <!-- LEFT PANEL -->
  <div class="left">
    <img src="frontend/assets/images/logo.png" class="logo" alt="Food Bank Logo">
    <h1 class="welcome">Join Us Today!</h1>
    <p class="subtitle">Create your account and start making a difference</p>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right">

    <!-- PROGRESS BAR -->
    <div class="progress" id="progressBar">
      <span class="<?php echo $showAccountStep ? '' : 'active'; ?>" id="dot1"></span>
      <span class="<?php echo $showAccountStep ? 'active' : ''; ?>" id="dot2"></span>
    </div>

    <form action="backend/controllers/auth/process_signup.php" method="POST" id="signupForm">

      <!-- ======================== -->
      <!-- STEP 1: PERSONAL INFO    -->
      <!-- ======================== -->
      <div class="step <?php echo $showAccountStep ? '' : 'active'; ?>" id="step1">
        <h2 class="title">Personal Information</h2>
        <p class="desc">Tell us about yourself</p>

        <?php if ($signupErrorMessage && !$showAccountStep): ?>
          <div class="signup-notice" role="alert">
            <?php echo htmlspecialchars($signupErrorMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <label>First Name</label>
        <input type="text" name="first_name" placeholder="Enter first name" required>

        <label>Middle Name</label>
        <input type="text" name="middle_name" placeholder="Enter middle name (optional)">

        <label>Last Name</label>
        <input type="text" name="last_name" placeholder="Enter last name" required>

        <label>Suffix</label>
        <select name="suffix" id="suffix_dropdown" onchange="toggleCustomSuffix()">
          <option value="" disabled selected>Select Suffix (optional)</option>
          <option value="Jr.">Jr.</option>
          <option value="Sr.">Sr.</option>
          <option value="II">II</option>
          <option value="III">III</option>
          <option value="IV">IV</option>
          <option value="Others">Others</option>
        </select>
        <input type="text" id="custom_suffix" placeholder="Enter custom suffix">

        <label>Address</label>
        <textarea name="address" placeholder="Enter your complete address" required></textarea>

        <label>Birthdate</label>
        <input type="date" name="birthdate" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>

        <button type="button" class="btn" onclick="goToStep2()">Next</button>

        <div class="login-link">
          Already have an account? <a href="login.php">Login</a>
        </div>

        <div class="back-home">
          <a href="index.php">← Back to home</a>
        </div>
      </div>

      <!-- ======================== -->
      <!-- STEP 2: ACCOUNT DETAILS  -->
      <!-- ======================== -->
      <div class="step <?php echo $showAccountStep ? 'active' : ''; ?>" id="step2">
        <h2 class="title">Account Details</h2>
        <p class="desc">Set up your account credentials</p>

        <?php if ($signupErrorMessage && $showAccountStep): ?>
          <div class="signup-notice" role="alert">
            <?php echo htmlspecialchars($signupErrorMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <label>Account Type</label>
        <select name="account_type" required>
          <option value="PA">PA - Personal Account (Donor's Account)</option>
          <option value="FA">FA - Food Bank Account (Organization Account)</option>
          <option value="AA">AA - Admin Account</option>
        </select>

        <label>Email Address</label>
        <input type="email" name="email" placeholder="your.email@example.com" required>

        <label>Phone Number</label>
        <input type="tel" name="phone_number" placeholder="09123456789" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="create a strong password" required>

        <label>Re-enter Password</label>
        <input type="password" name="confirm_password" placeholder="re-enter your password" required>

        <button type="submit" class="btn">Sign up</button>

        <div class="nav-row">
          <a onclick="goToStep1()">← Previous step</a>
        </div>

        <div class="login-link">
          Already have an account? <a href="login.php">Login</a>
        </div>
      </div>

    </form>
  </div>
</div>
</body>

<script>
  function goToStep2() {
    // Basic validation for step 1 required fields
    const firstName = document.querySelector('[name="first_name"]');
    const lastName  = document.querySelector('[name="last_name"]');
    const address   = document.querySelector('[name="address"]');
    const birthdate = document.querySelector('[name="birthdate"]');

    if (!firstName.value.trim() || !lastName.value.trim() || !address.value.trim() || !birthdate.value) {
      alert('Please fill in all required fields before continuing.');
      return;
    }

    if (!isAtLeast18(birthdate.value)) {
      alert('You must be at least 18 years old to create an account.');
      birthdate.focus();
      return;
    }

    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.add('active');
    document.getElementById('dot1').classList.remove('active');
    document.getElementById('dot2').classList.add('active');

    // Scroll right panel to top
    document.querySelector('.right').scrollTop = 0;
  }

  function goToStep1() {
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');
    document.getElementById('dot2').classList.remove('active');
    document.getElementById('dot1').classList.add('active');

    document.querySelector('.right').scrollTop = 0;
  }

  function isAtLeast18(dateValue) {
    const birthDate = new Date(dateValue + 'T00:00:00');

    if (Number.isNaN(birthDate.getTime())) {
      return false;
    }

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDifference = today.getMonth() - birthDate.getMonth();
    const dayDifference = today.getDate() - birthDate.getDate();

    if (monthDifference < 0 || (monthDifference === 0 && dayDifference < 0)) {
      age--;
    }

    return age >= 18;
  }

  function toggleCustomSuffix() {
    const dropdown    = document.getElementById('suffix_dropdown');
    const customInput = document.getElementById('custom_suffix');

    if (dropdown.value === 'Others') {
      customInput.style.display = 'block';
      customInput.required = true;
      customInput.name = 'suffix';
      dropdown.removeAttribute('name');
    } else {
      customInput.style.display = 'none';
      customInput.required = false;
      customInput.value = '';
      customInput.removeAttribute('name');
      dropdown.name = 'suffix';
    }
  }
</script>
</html>
