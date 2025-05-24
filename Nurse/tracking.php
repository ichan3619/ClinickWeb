<?php
session_start();
require '../Config/database.php'; // Ensure $conn is established
if (!isset($_SESSION['UID'])) {
    // It's better to redirect to a login page or show a proper error page
    // For now, just dying, but consider a more user-friendly approach.
    header("Location: ../login.php?error=unauthorized"); // Example redirect
    die("Unauthorized. Please log in.");
}
$UID = $_SESSION['UID']; // This variable is declared but not used in the provided snippet.
                        // It might be intended for future use or a different part of the page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patient Tracking</title>
  <link rel="stylesheet" href="../Stylesheet/doctorForm.css" />
  <link rel="stylesheet" href="../Stylesheet/tracking.css" />
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
  <style>
    /* Add some basic styling for buttons if not in tracking.css */
    .tracking-btn {
        padding: 8px 12px;
        margin: 2px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    .start-tracking {
        background-color: #28a745; /* Green */
        color: white;
    }
    .end-tracking {
        background-color: #dc3545; /* Red */
        color: white;
    }
    .tracking-active {
        background-color: #ffc107; /* Yellow */
        color: black;
    }
    .search-input {
        padding: 10px;
        margin-bottom: 15px;
        width: calc(100% - 22px); /* Adjust width considering padding and border */
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .appointments-table {
        width: 100%;
        border-collapse: collapse;
    }
    .appointments-table th, .appointments-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .appointments-table th {
        background-color: #f2f2f2;
    }
    .tracking-info {
        font-size: 0.8em;
        color: #555;
        display: block; /* Makes the span take its own line */
        margin-top: 4px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <a href="nurseDashboard.php" title="Dashboard"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="patientsList.php" title="Patient List"><i class="fa-solid fa-hospital-user fa-3x"></i></a>
    <a href="tracking.php" class="active" title="Patient Tracking"><i class="fa-solid fa-clock fa-3x"></i></a>
    <a href="#" title="Inventory/Supplies"><i class="fa-solid fa-box fa-3x"></i></a>
  </div>

  <div class="main">
    <nav>
      <a href="nurseDashboard.php">Dashboard</a>
      <a href="#" class="active">Admission</a>
      <a href="tracking_log.php">Tracking Log</a>
      <a href="../Inventory/INVDASH.html">Inventory</a>
    </nav>
    <br>
    <div class="appointments-container">
      <h2>Patient Tracking | <?php echo htmlspecialchars(date('F d, Y')); ?></h2>
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by name...">
      </div>
      <table class="appointments-table">
        <thead>
          <tr>
            <th>School No.</th>
            <th>Name</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
            <th>Tracking</th>
            <th>Last Seen in Clinic</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
            // Corrected SQL Query
            $query = "SELECT 
                        u.UID,
                        u.schoolID as school_no,
                        CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) as name,
                        t.appID,
                        t.tracking_id, -- Select tracking_id from the outer table t
                        t.trackingStatusType as status,
                        t.trackingStart,
                        t.trackingEnd,
                        t.trackingStatus as tracking_status,
                        (
                            SELECT t2.trackingEnd
                            FROM tracking t2 
                            WHERE t2.UID = u.UID 
                            AND t2.trackingEnd IS NOT NULL
                            AND t2.tracking_id != COALESCE(t.tracking_id, 0) -- Corrected: t2.tracking_id and t.tracking_id
                            ORDER BY t2.trackingEnd DESC 
                            LIMIT 1
                        ) as last_seen
                      FROM userInfo u
                      LEFT JOIN tracking t ON u.UID = t.UID AND t.trackingStatus = 'Ongoing'
                      ORDER BY 
                        CASE WHEN t.trackingStatus = 'Ongoing' THEN 0 ELSE 1 END,
                        COALESCE(t.trackingStart, '9999-12-31') DESC, -- Handle NULL trackingStart for sorting
                        u.lname, u.fname"; // Added secondary sort by name

            $stmt = $conn->prepare($query);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $isTracking = $row['tracking_status'] === 'Ongoing';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['school_no'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                echo "<td>" . ($isTracking && !empty($row['trackingStart']) ? htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['trackingStart']))) : '-') . "</td>";
                echo "<td>" . ($isTracking && !empty($row['trackingEnd']) ? htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['trackingEnd']))) : '-') . "</td>";
                echo "<td>" . (!empty($row['status']) ? htmlspecialchars($row['status']) : 'Not In Clinic') . "</td>";
                echo "<td>";
                
                if ($isTracking) {
                  echo "<button class='tracking-btn tracking-active' disabled>Tracking...</button>"; // Made active button disabled as it's informational
                  echo "<button class='tracking-btn end-tracking' 
                          data-uid='" . htmlspecialchars($row['UID']) . "' 
                          data-trackingid='" . htmlspecialchars($row['tracking_id'] ?? '') . "'
                          data-appid='" . htmlspecialchars($row['appID'] ?? '') . "'>
                          End Tracking
                        </button>";
                  if (!empty($row['trackingStart'])) {
                    echo "<br><span class='tracking-info' data-start='" . htmlspecialchars($row['trackingStart']) . "'>Started: " . htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['trackingStart']))) . "</span>";
                  }
                } else {
                  echo "<button class='tracking-btn start-tracking' 
                          data-uid='" . htmlspecialchars($row['UID']) . "' 
                          data-appid='" . htmlspecialchars($row['appID'] ?? '') . "'>
                          Start Tracking
                        </button>";
                }
                
                echo "</td>";
                $lastSeen = !empty($row['last_seen']) ? htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['last_seen']))) : '-';
                echo "<td>" . $lastSeen . "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7' style='text-align: center;'>No records found</td></tr>"; // Adjusted colspan
            }
          } catch (PDOException $e) {
            error_log("Database Error in tracking.php: " . $e->getMessage()); // Log the error
            echo "<tr><td colspan='7' style='text-align: center; color: red;'>Error retrieving data. Please try again later.</td></tr>"; // Adjusted colspan
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <i class="fa-regular fa-user" id="profile" title="Profile"></i>
  <script src="../JScripts/tracking.js"></script>
</body>
</html>
