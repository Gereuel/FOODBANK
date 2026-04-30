<?php
// donors.php
// Later you can connect database here
// session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../../assets/css/pages/donors.css">

<title>Food Bank - Donors</title>
</head>

<body>

<div class="donors">

  <!-- SIDEBAR -->
  <aside class="left-navigation">

    <div class="logo">
      <img src="img/logo.png" alt="logo">
      <h2>Food <span>Bank</span></h2>
      <p>APP</p>
    </div>

    <!-- PROFILE (STATIC FOR NOW) -->
    <div class="profile">
      <div class="avatar"></div>

      <div class="info">
        <h3>
          <?php echo "Gereuel Brillantes"; ?>
        </h3>

        <small>
          <?php echo "gereuel.brillantes@gnc.edu.ph"; ?>
        </small>
      </div>
    </div>

    <!-- NAVIGATION -->
     <div class="navigation-buttons">

        <a href="pa_home_page.php"><button>Food Banks</button></a>
        <a href="pa_messages.php"><button>Messages</button></a>
        <a href="pa_donors.php"><button class="active">Donors</button></a>
        <a href="pa_donation.php"><button>Donation</button></a>
        <a href="pa_account.php"><button>Account</button></a>

    </div>
    

  </aside>

  <!-- MAIN CONTENT -->
  <main class="contents">

    <!-- HEADER -->
    <header class="header">
      <div class="top-background"></div>

      <div class="welcome-modal">
        <h1>
          <?php echo "Welcome back, Gereuel!"; ?>
        </h1>

        <p>Let's make a difference today</p>
      </div>
    </header>

    <!-- TITLE -->
    <section class="section-title">
      <h2>Active Donors</h2>
      <p>Connect with our generous community</p>
    </section>

    <!-- DONOR GRID (STATIC FIRST) -->
    <section class="donor-grid">

      <?php
      // Later this becomes database loop
      $donors = [
        ["name" => "Trixia Rivera", "donations" => "1.3M Donations"],
        ["name" => "Robert Villarin", "donations" => "99 Donations"],
        ["name" => "Jonie Briones", "donations" => "900.2K Donations"],
        ["name" => "Trishia Nangit", "donations" => "1.4K Donations"],
        ["name" => "Sean Bagang", "donations" => "7.4K Donations"],
        ["name" => "Juliana Garcia", "donations" => "803.4K Donations"]
      ];

      foreach ($donors as $donor) {
        echo '
        <div class="donor-card">
          <h3>' . $donor["name"] . '</h3>
          <p>' . $donor["donations"] . '</p>
        </div>
        ';
      }
      ?>

    </section>

  </main>

</div>

</body>
</html>
