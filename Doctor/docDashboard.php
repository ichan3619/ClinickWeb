<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Define Paths ---
// These paths assume docDashboard.php is in a folder like 'Doctor/',
// and 'Login/' and 'campus/' are sibling folders to 'Doctor/'.
// **Please adjust these paths if your file structure is different.**
$login_page_url = '../Login/Login.php';
$campus_select_url = '../campus/campusSelect.php'; // Path used in your Login.php

// 1. Check if user is logged in (UID is the primary session variable)
if (!isset($_SESSION['UID'])) {
    header('Location: ' . $login_page_url);
    exit;
}

// 2. Define allowed roles for this specific page
$allowed_roles = ['Doctor']; // Only 'Doctor' can access docDashboard.php

// 3. Check if user's role is set and is 'Doctor'
if (!isset($_SESSION['roleName']) || !in_array($_SESSION['roleName'], $allowed_roles)) {
    // If role is not set or not 'Doctor', redirect to login (optionally with an error message)
    header('Location: ' . $login_page_url . '?error=unauthorized_doctor_access');
    exit;
}

// 4. Check if an active campus is selected.
if (!isset($_SESSION['activeCampusID']) || empty($_SESSION['activeCampusID'])) {
    // Store the current page URL they were trying to access,
    // so campusSelect.php can redirect back here after selection.
    $_SESSION['post_campus_select_redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . $campus_select_url);
    exit;
}

// If all checks pass, the script continues and loads the HTML below.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../Stylesheet/docDash.css">
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
</head>
<body>
  <div class="sidebar">
    <div class="icon placeholder" title="Dashboard Home">
      </div>

    <div class="icon placeholder" title="Settings">
      </div>

    <div class="icon placeholder" title="Notifications">
      </div>

    <div class="icon" title="Logout" style="margin-top: auto; margin-bottom: 20px;"> <a href="../Login/logout.php" style="color:white; text-decoration:none; display:flex; justify-content:center; align-items:center; width:100%; height:100%;">
        <i class="fa-solid fa-right-from-bracket fa-2x" style="color: #ffffff;"></i>
      </a>
    </div>
  </div>

  <nav>
    <a href="#" class="active">Dashboard</a>
    <a href="viewAppointments.php">Admission</a>
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