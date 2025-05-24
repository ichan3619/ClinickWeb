<?php
require '../Config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointments</title>
  <link rel="stylesheet" href="../Stylesheet/doctorForm.css" />
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
</head>
<body>
  <div class="sidebar">
    <a href="#"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="consultGrant.php"><i class="fa-solid fa-clipboard fa-3x"></i></a>
    <a href="doneAppoint.php"><i class="fa-solid fa-book-medical fa-3x"></i></a>
  </div>

  <div class="main">
    <nav>
      <a href="docDashboard.php">Dashboard</a>
      <a href="#" class="active">Admission</a>
      <a href="../Inventory/INVDASH.html">Inventory</a>
    </nav>
    <br>
    <div class="appointments-container">
      <h2>My Patients | <?php echo date('F d, Y'); ?></h2>
    </div>
      <table class="appointments-table">
        <thead>
          <tr>
            <th>School No.</th>
            <th>Name</th>
            <th>Department</th>
            <th>Date</th>
            <th>Type of Visit</th>
            <th>Status</th>
            <th>Link</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
            $query = "SELECT 
                      a.appID AS appID,
                      u.schoolID AS school_no,
                      CONCAT(u.fname, ' ', u.lname) AS patient_name,
                      d.deptName AS department,
                      a.consultDate AS consultationDate,
                      a.mode AS type_of_visit,
                      a.link,
                      a.status
                      FROM appointments a
                      JOIN userInfo u    ON a.UID    = u.UID
                      JOIN departments d ON u.deptID = d.deptID
                      WHERE a.status = 'Approved'
                      AND DATE(a.consultDate) >= CURDATE()
                      ORDER BY a.consultDate ASC";


            $stmt = $conn->prepare($query);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['school_no']) . "</td>";
                echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                echo "<td>" . htmlspecialchars($row['consultationDate']) . "</td>";
                echo "<td>" . htmlspecialchars($row['type_of_visit']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>";
                if ($row['type_of_visit'] === 'Online') {
                  if (!empty($row['link'])) {
                    echo "<a href='" . htmlspecialchars($row['link']) . "' target='_blank' class='meeting-link'>";
                    echo "<i class='fa-solid fa-video'></i> Join";
                    echo "</a>";
                  } else {
                    echo "<input type='text' placeholder='Enter link' class='link-input' data-app-id='" . htmlspecialchars($row['appID']) . "' />";
                    echo "<button class='save-link-btn' data-app-id='" . htmlspecialchars($row['appID']) . "'>Save</button>";
                  }
                } else {
                  echo "—";
                }
                echo "</td>";

                echo "<td>";
                echo "<button class='prescribe-btn' 
                        data-patient-name='" . htmlspecialchars($row['patient_name']) . "' 
                        data-app-id='" . htmlspecialchars($row['appID']) . "'>
                        Prescribe</button>";
                echo " <button class='remove-btn' title='Remove Patient'>&#10060;</button>";
                echo "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7' style='text-align: center;'>No approved consultations found</td></tr>";
            }
          } catch (PDOException $e) {
            echo "<tr><td colspan='7' style='text-align: center; color: red;'>Error: " . $e->getMessage() . "</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal -->
  <div id="prescriptionModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeModal">&times;</span>
      <div id="modalBody">Loading...</div>
    </div>
  </div>

  <i class="fa-regular fa-user" id="profile"></i>
  <script src="../JScripts/index.js"></script>
</body>
</html>
<?php $conn = null; ?>
