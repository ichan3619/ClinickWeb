  <?php
      require '../Config/database.php';
      session_start();
      $_SESSION['patientID'] = $_SESSION['UID'] ?? null;
      if (!isset($_SESSION['patientID'])) {
          die("Unauthorized. Please log in.");
      }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Clinick</title>
      <link rel="stylesheet" href="../Stylesheet/style.css">
      <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
      <a href="../login/logout.php" style="display: flex;"><span style="justify-content: center;">Logout</span></a>
  </head>
  <body>
      <div class="container">
          <div class="sidebar"></div>
      
          <div class="main-content">
            <div class="top-nav">
              <div class="nav-links">
                <a href="patientHome.php" class="active">Home</a>
                <a href="reqConsult.php">Consultation</a>
              </div>
              <div class="user-icon">
                  <i class="fa-regular fa-user fa-L"></i>
              </div>
            </div>
      
            <div class="content">
              <!-- Main page content -->
              <d class="upcoming-events">
                <p>No upcoming events</p>
              </div>
            </div>
          </div>
        </div>
  </body>
  </html>
  <?php
    $conn = null;
  ?>
