<?php
session_start();
require '../Config/database.php'; // Ensure $conn is established
if (!isset($_SESSION['UID'])) {
    // It's better to redirect to a login page or show a proper error page
    header("Location: ../login.php?error=unauthorized"); // Example redirect
    die("Unauthorized. Please log in.");
}
$UID = $_SESSION['UID']; // This variable is declared but not used in this specific script.
                        // It might be intended for future use (e.g., filtering logs by the logged-in nurse if needed).
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tracking Log</title>
  <link rel="stylesheet" href="../Stylesheet/doctorForm.css" />
  <link rel="stylesheet" href="../Stylesheet/tracking.css" />
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
  <style>
    /* Basic styling for the table if not fully covered by external CSS */
    .appointments-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .appointments-table th, .appointments-table td {
        border: 1px solid #ddd;
        padding: 10px; /* Increased padding */
        text-align: left;
        vertical-align: middle;
    }
    .appointments-table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .appointments-container h2 {
        margin-bottom: 15px;
    }
     /* Style for search input if needed, similar to tracking.php */
    .search-input { /* Assuming you might add search later */
        padding: 10px;
        margin-bottom: 15px;
        width: calc(100% - 22px);
        border: 1px solid #ccc;
        border-radius: 4px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <a href="nurseDashboard.php" title="Dashboard"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="patientsList.php" title="Patient List"><i class="fa-solid fa-hospital-user fa-3x"></i></a>
    <a href="tracking.php" title="Patient Tracking"><i class="fa-solid fa-clock fa-3x"></i></a>
    <a href="#" title="Inventory/Supplies"><i class="fa-solid fa-box fa-3x"></i></a>
  </div>

  <div class="main">
    <nav>
      <a href="nurseDashboard.php">Dashboard</a>
      <a href="tracking.php">Admission</a>
      <a href="#" class="active">Tracking Log</a>
      <a href="../Inventory/INVDASH.html">Inventory</a>
    </nav>
    <br>
    <div class="appointments-container">
      <h2>Tracking Log | <?php echo htmlspecialchars(date('F d, Y') ?? ''); ?></h2>
      <!-- 
      <div class="search-container">
        <input type="text" id="logSearchInput" class="search-input" placeholder="Search logs...">
      </div>
      -->
      <table class="appointments-table">
        <thead>
          <tr>
            <th>School No.</th>
            <th>Name</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Duration</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
            // Corrected SQL Query: Changed t.trackingid to t.tracking_id
            $query = "SELECT t.tracking_id, 
                            u.schoolID as school_no,
                            CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) as name,
                            t.trackingStart,
                            t.trackingEnd,
                            t.trackingStatusType as status
                      FROM tracking t
                      JOIN userInfo u ON t.UID = u.UID
                      WHERE t.trackingStatus = 'Completed' OR t.trackingEnd IS NOT NULL -- Show only completed or explicitly ended sessions
                      ORDER BY t.trackingStart DESC";

            $stmt = $conn->prepare($query);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $duration = 'Ongoing'; // Default for safety, though query should filter these out
                $formattedStartTime = '-';
                $formattedEndTime = '-';

                if (!empty($row['trackingStart'])) {
                    $startTime = new DateTime($row['trackingStart']);
                    $formattedStartTime = $startTime->format('Y-m-d H:i:s');

                    if (!empty($row['trackingEnd'])) {
                        $endTime = new DateTime($row['trackingEnd']);
                        $formattedEndTime = $endTime->format('Y-m-d H:i:s');
                        
                        $interval = $startTime->diff($endTime);
                        $hours = $interval->h + ($interval->days * 24);
                        $minutes = $interval->i;
                        $seconds = $interval->s;
                        
                        $durationParts = [];
                        if ($hours > 0) $durationParts[] = "{$hours}h";
                        if ($minutes > 0) $durationParts[] = "{$minutes}m";
                        if ($seconds > 0 || empty($durationParts)) $durationParts[] = "{$seconds}s"; // Show seconds if other parts are zero or if it's the only unit
                        $duration = implode(' ', $durationParts);

                    } else {
                        // This case should ideally not be hit if WHERE clause is t.trackingEnd IS NOT NULL
                        // Or if trackingStatus = 'Completed' implies trackingEnd is set.
                        // If a session is 'Ongoing' but somehow appears here, duration remains 'Ongoing'.
                        $now = new DateTime();
                        $interval = $startTime->diff($now);
                        $hours = $interval->h + ($interval->days * 24);
                        $minutes = $interval->i;
                        $seconds = $interval->s;
                        $durationParts = [];
                        if ($hours > 0) $durationParts[] = "{$hours}h";
                        if ($minutes > 0) $durationParts[] = "{$minutes}m";
                        if ($seconds > 0 || empty($durationParts)) $durationParts[] = "{$seconds}s";
                        $duration = implode(' ', $durationParts) . " (Ongoing)";
                    }
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['school_no'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($formattedStartTime) . "</td>";
                echo "<td>" . htmlspecialchars($formattedEndTime) . "</td>";
                echo "<td>" . htmlspecialchars($duration) . "</td>";
                echo "<td>" . htmlspecialchars($row['status'] ?? 'N/A') . "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='6' style='text-align: center;'>No tracking records found</td></tr>";
            }
          } catch (PDOException $e) {
            error_log("Database Error in tracking_log.php: " . $e->getMessage()); // Log the error
            echo "<tr><td colspan='6' style='text-align: center; color: red;'>Error retrieving data. Please try again later.</td></tr>";
          } catch (Exception $e) { // Catch DateTime related errors
            error_log("Date Error in tracking_log.php: " . $e->getMessage());
            echo "<tr><td colspan='6' style='text-align: center; color: red;'>Error processing date/time.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <i class="fa-regular fa-user" id="profile" title="Profile"></i>
  </body>
</html>
