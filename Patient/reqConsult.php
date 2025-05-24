<?php
session_start();
require '../Config/database.php';
$_SESSION['patientID'] = $_SESSION['UID'] ?? null; // Assuming UID is set when the patient logs in
if (!isset($_SESSION['patientID'])) {
  die("Unauthorized. Please log in.");
}

$patientID = $_SESSION['patientID'];

// Fetch department from the database
$deptStmt = $conn->prepare("
    SELECT u.deptID, d.deptName 
    FROM userinfo u
    JOIN departments d ON u.deptID = d.deptID
    WHERE u.UID = :UID
");

$deptStmt->execute([':UID' => $patientID]);
$deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
$departmentID = $deptRow['deptID'];
$departmentName = $deptRow['deptName']; // For display only


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $consultationType = $_POST['consultationType'];
  $campus = $_POST['campus'];
  $mode = $_POST['mode'];
  $consultationDate = $_POST['consultationDate'];
  $reason = $_POST['reason'];

$stmt = $conn->prepare("INSERT INTO appointments 
  (UID, deptID, consultType, campID, mode, consultDate, reason) 
  VALUES 
  (:patientID, :departmentID, :consultationType, :campus, :mode, :consultationDate, :reason)");

$stmt->execute([
  ':patientID' => $patientID,
  ':departmentID' => $departmentID,
  ':consultationType' => $consultationType,
  ':campus' => $campus,
  ':mode' => $mode,
  ':consultationDate' => $consultationDate,
  ':reason' => $reason
]);


  $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Consultation</title>
  <link rel="stylesheet" href="../Stylesheet/form.css">
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
</head>
<body>

  <div class="sidebar">
    <a href="reqConsult.php"><i class="fa-solid fa-notes-medical fa-3x" title="Request Consultation"></i></a>
    <a href="viewSum.php"><i class="fa-solid fa-clock-rotate-left fa-3x" title="History"></i></a>
    <a href="upcoming.php"><i class="fa-regular fa-calendar-xmark fa-3x" title="Appointments"></i></a>
  </div>

  <div class="main">
    <nav>
      <a href="patientHome.php">Home</a>
      <a href="#" class="active">Consultation</a>
    </nav>

    <div class="form-container">
      <h2>Request Consultation</h2>
      <?php if (!empty($success)): ?>
        <p style="color: green;">Consultation Request Submitted Successfully!</p>
        <script>
          // Notify other tabs (like consultGrant.php) to reload
          localStorage.setItem('refreshConsultGrant', Date.now());
        </script>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
            <input type="text" value="<?php echo htmlspecialchars($departmentName); ?>" readonly>
        </div>


        <div class="form-group">
          <select name="consultationType" required>
            <option value="">Select Consultation Type</option>
            <option value="Checkup">Check up Consultation</option>
            <option value="Follow-up">Follow-up Consultation</option>
          </select>

          <select name="campus" required>
            <option value="">Select Campus</option>
            <option value="1">Marciano Campus</option>
            <option value="2">Elida Campus</option>
          </select>
        </div>

        <div class="radio-group">
            <label><input type="radio" name="mode" value="Physical" id="physicalRadio" required> Personal (Face-to-Face)</label>
            <label><input type="radio" name="mode" value="Online" id="onlineRadio" required> Online Consultation</label>
        </div>


        <div class="form-group">
          <input type="date" name="consultationDate" required>
        </div>

        <div class="form-group">
          <textarea id="reasonBox" name="reason" placeholder="Reason (For Online Consultation)"></textarea>
        </div>

        <button type="submit" class="submit-btn">Submit</button>
      </form>
    </div>
  </div>

  <i class="fa-regular fa-user fa-2xl" id="profile"></i>
  
  <script>
      const physicalRadio = document.getElementById('physicalRadio');
      const onlineRadio = document.getElementById('onlineRadio');
      const reasonBox = document.getElementById('reasonBox');

      function toggleReasonRequirement() {
        if (physicalRadio.checked) {
          reasonBox.required = false;
        } else if (onlineRadio.checked) {
          reasonBox.required = true;
        }
      }

      // Run on page load
      window.addEventListener('DOMContentLoaded', toggleReasonRequirement);

      // Attach change events
      physicalRadio.addEventListener('change', toggleReasonRequirement);
      onlineRadio.addEventListener('change', toggleReasonRequirement);
    </script>

</body>
</html>
<?php $conn = null; ?>