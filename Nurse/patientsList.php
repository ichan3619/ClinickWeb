<?php
session_start();
require '../Config/database.php';
if (!isset($_SESSION['UID'])) {
    die("Unauthorized. Please log in.");
}
$UID = $_SESSION['UID'];
$campusID = $_SESSION['campusID'] ?? null; // Assuming campusID is set when the user logs in
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patients List</title>
  <link rel="stylesheet" href="../Stylesheet/doctorForm.css" />
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="sidebar">
    <a href="nurseDashboard.php"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="patientsList.php"><i class="fa-solid fa-hospital-user fa-3x"></i></a>
    <a href="tracking.php"><i class="fa-solid fa-clock fa-3x"></i></a>
    <a href="#"><i class="fa-solid fa-box fa-3x"></i></a>
  </div>

  <div class="main">
    <nav>
      <a href="nurseDashboard.php">Dashboard</a>
      <a href="viewNurse.php" class="active">Admission</a>
      <a href="tracking_log.php">Tracking Log</a>
      <a href="../Inventory/INVDASH.html">Inventory</a>
    </nav>
    <br>
    <div class="appointments-container">
      <h2>Patients List | <?php echo date('F d, Y'); ?></h2>
      <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search patients...">
      </div>
      <table class="appointments-table">
        <thead>
          <tr>
            <th>School ID</th>
            <th>Patient Name</th>
            <th>Contact Number</th>
            <th>Department</th>
            <th>Campus</th>
            <th>Address</th>
            <th>Emergency Contact</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
            $query = "
                      SELECT 
                        u.schoolID,
                        CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname, ' ', COALESCE(u.suffix, '')) AS full_name,
                        u.contactNum,
                        d.deptName,
                        c.campusName,
                        CONCAT(u.street, ', ', u.baranggay, ', ', u.city, ', ', u.province) AS full_address,
                        CONCAT(u.emergencyPerson, ' (', u.emergencyContact, ')') AS emergency_contact
                      FROM userInfo u
                      LEFT JOIN appointments a ON u.UID = a.UID
                      LEFT JOIN departments d ON u.deptID = d.deptID
                      LEFT JOIN campus c ON a.campID = c.campusID
                      WHERE c.campusID = :campusID
                      ORDER BY u.lname ASC, u.fname ASC
                    ";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':campusID', $campusID, PDO::PARAM_INT);
                $stmt->execute();


            if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['schoolID']) . "</td>";
                echo "<td>" . htmlspecialchars(trim($row['full_name'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['contactNum']) . "</td>";
                echo "<td>" . htmlspecialchars($row['deptName']) . "</td>";
                echo "<td>" . htmlspecialchars($row['campusName']) . "</td>";
                echo "<td>" . htmlspecialchars($row['full_address']) . "</td>";
                echo "<td>" . htmlspecialchars($row['emergency_contact']) . "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7' style='text-align: center;'>No patients found</td></tr>"; 
            }
          } catch (PDOException $e) {
            echo "<tr><td colspan='7' style='text-align: center; color: red;'>Error: " . $e->getMessage() . "</td></tr>"; 
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <i class="fa-regular fa-user" id="profile"></i>

  <script>
    // Add search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
      let searchQuery = this.value.toLowerCase();
      let tableRows = document.querySelectorAll('.appointments-table tbody tr');
      
      tableRows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchQuery) ? '' : 'none';
      });
    });
  </script>

  <script src="../JScripts/index.js"></script>
</body>
</html>
<?php $conn = null; ?>
