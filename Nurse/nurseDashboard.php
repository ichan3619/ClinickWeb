<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UID'])) {
    // Not logged in – redirect to login page
    header("Location: ../login.php");
    exit;
}

// Optional: You can retrieve user data for display
$UID = $_SESSION['UID'];
$userName = isset($_SESSION['userName']) ? $_SESSION['userName'] : 'Nurse';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../Stylesheet/nurseDash.css">
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
</head>
<body>
  <div class="sidebar">
    <a href="#"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="patientsList.php"><i class="fa-solid fa-hospital-user fa-3x"></i></a>
    <a href="tracking.php"><i class="fa-solid fa-clock fa-3x"></i></a>
    <a href="../Inventory/INVDASH.html"><i class="fa-solid fa-box fa-3x"></i></a>
  </div>

  <nav>
    <a href="#" class="active">Dashboard</a>
    <a href="viewNurse.php">Admission</a>
    <a href="tracking_log.php">Tracking Log</a>
    <a href="../Inventory/INVDASH.html">Inventory</a>
  </nav>

  <div id="profile">
    <i class="fa-regular fa-user"></i>
  </div>

  <div class="main">
    <div class="left-panel">
      <div class="card">
        <h3>Low Items</h3>
        <p>All inventory items are sufficiently stocked!</p>
      </div>
      <div class="card">
        <h3>Expiring Soon</h3>
        <p>No upcoming expirations detected!</p>
      </div>
    </div>
    <div class="right-panel">
      <div class="card">
        <p>No upcoming events</p>
      </div>
    </div>
  </div>
</body>
</html>
