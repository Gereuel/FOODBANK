<?php
session_start();

// Include database configuration
require_once '../../../backend/config/database.php';

// This will check if the user is logged in, pero kung hindi ma-redirect sha sa login
if (!isset($_SESSION['Account_ID'])) {
    header("Location: ../../../login.php");
    exit();
}

// Check if user is a Personal Account (Donor)
if ($_SESSION['Account_Type'] !== 'PA') {
    header("Location: ../../../login.php?error=unauthorized");
    exit();
}

// Fetch the logged-in donor's credentials
try {
    $stmt = $pdo->prepare("SELECT u.*, a.Email, a.Account_Type, a.Custom_App_ID 
                          FROM USERS u 
                          JOIN ACCOUNTS a ON u.User_ID = a.User_ID 
                          WHERE a.Account_ID = ? LIMIT 1");
    $stmt->execute([$_SESSION['Account_ID']]);
    
    $donor = $stmt->fetch();
    
    if (!$donor) {
        header("Location: ../../../login.php?error=user_not_found");
        exit();
    }
    
    // Store donor data in variables for use in the page
    $firstName = $donor['First_Name'];
    $lastName = $donor['Last_Name'];
    $email = $donor['Email'];
    
} catch (PDOException $e) {
    die("Error fetching donor credentials: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Food Bank App</title>
    <link rel="icon" href="favicon.ico">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Food Bank App</div>
        <div class="nav-links">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['First_Name'] . ' ' . $_SESSION['Last_Name']); ?>!</h2>
            <a href="../../../backend/controllers/auth/logout.php" style="color: red; text-decoration: none; margin-left: 15px;">Log Out</a>
        </div>
    </nav>
</body>
<script>
    function preventBack() { window.history.forward(); }
    setTimeout("preventBack()", 0);
    window.onunload = function () { null };
</script>
</html>

<!--
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Food Bank Dashboard</title>

  FIXED CSS PATH
  <link rel="stylesheet" href="../../assets/css/pages/home_page.css">
</head>

<body>

<div class="layout">

  SIDEBAR 
  <aside class="sidebar">

    <div class="logo">
      <h2>Food <span>Bank</span></h2>
      <small>APP</small>
    </div>

    <div class="profile">
      <h3><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h3>
      <p><?php echo htmlspecialchars($email); ?></p>
    </div>

    NAVIGATION
    <nav class="menu">
      <a href="pa_home_page.php"><button>Food Banks</button></a>
      <a href="pa_messages.php"><button>Messages</button></a>
      <a href="pa_donors.php"><button>Donors</button></a>
      <a href="#"><button>Donation</button></a>
      <a href="#"><button>Account</button></a>
      <a href="../../../backend/controllers/auth/logout.php"><button style="color:red;">Logout</button></a>
    </nav>

  </aside>

  MAIN
  <main class="main">

    <header class="header">
      <h1>Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h1>
      <p>Let’s make a difference today</p>
    </header>

    STATS
    <section class="stats">
      <div class="card">
        <h2>24+</h2>
        <p>Active Banks</p>
      </div>

      <div class="card">
        <h2>1.3M+</h2>
        <p>Donors</p>
      </div>

      <div class="card">
        <h2>1.5k+</h2>
        <p>Donations</p>
      </div>
    </section>

    FOOD BANKS
    <section class="section">
      <div class="section-title">
        <h2>Nearby Food Banks</h2>
        <button class="view-all">View All</button>
      </div>

      <div class="grid">

        <?php
        // Sample data (replace with DB later)
        $banks = [
          ["name" => "Community Food Bank", "distance" => "1.2 km"],
          ["name" => "Helping Hands Center", "distance" => "2.0 km"],
          ["name" => "City Food Hub", "distance" => "0.8 km"]
        ];

        foreach ($banks as $bank) {
            echo "
            <div class='bank'>
              <h3>{$bank['name']}</h3>
              <p>Open Now • {$bank['distance']}</p>
              <button>View Details</button>
            </div>
            ";
        }
        ?>

      </div>
    </section>

  </main>

</div>

</body>
</html> 
-->