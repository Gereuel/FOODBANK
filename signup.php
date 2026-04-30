<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Food Bank App</title>
    <link href="https://fonts.googleapis.com/css2?family=Commissioner:wght@300;400;500&family=DM+Serif+Display&family=Inter:wght@400;600&family=Roboto+Flex:wght@400;600&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="frontend/assets/css/pages/signup.css">
    <link rel="icon" href="favicon.ico">
</head>
<body>
<div class="container">

  <!-- LEFT PANEL -->
  <div class="left">
    <img src="frontend/assets/images/logo.png" class="logo" alt="Food Bank Logo">
    <h1 class="welcome">Join Us Today!</h1>
    <p class="subtitle">Create your account and start making a difference</p>
    <link rel="icon" href="favicon.ico">
  </div>

  <!-- RIGHT PANEL -->
  <div class="right">

    <!-- PROGRESS BAR -->
    <div class="progress" id="progressBar">
      <span class="active" id="dot1"></span>
      <span id="dot2"></span>
    </div>

    <form action="backend/controllers/process_signup.php" method="POST" id="signupForm">

      <!-- ======================== -->
      <!-- STEP 1: PERSONAL INFO    -->
      <!-- ======================== -->
      <div class="step active" id="step1">
        <h2 class="title">Personal Information</h2>
        <p class="desc">Tell us about yourself</p>

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
        <input type="date" name="birthdate" required>

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
      <div class="step" id="step2">
        <h2 class="title">Account Details</h2>
        <p class="desc">Set up your account credentials</p>

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