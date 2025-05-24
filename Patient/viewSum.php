<?php
require '../Config/database.php';
session_start();

// 1. Correctly get patientID from session
$patientID = $_SESSION['UID'] ?? null; // Use UID from session, provide null if not set

if (is_null($patientID)) { // Check if patientID was actually found
    // You might want to redirect to a login page or show a more user-friendly error
    header("Location: ./index.php?error=unauthorized"); // Example redirect
    die("Unauthorized. Please log in. UID not found in session.");
}

$query = "SELECT DISTINCT a.consultDate AS dateTime, a.consultType, c.campusName, a.appID
    FROM Appointments a
    JOIN campus c ON a.campID = c.campusID
    JOIN consultationMedication cm ON a.appID = cm.appID -- Only shows appointments with medication entries
    JOIN userInfo u ON a.UID = u.UID
    JOIN consultSummary cs ON a.appID = cs.appID       -- Only shows appointments with summary entries
    WHERE a.UID = :UID AND a.status = 'Prescribed'
    ORDER BY a.consultDate DESC";

$stmt = $conn->prepare($query);
// Bind the correctly fetched $patientID
$stmt->bindParam(':UID', $patientID, PDO::PARAM_INT);
$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Consultation History</title>
  <link rel="stylesheet" href="../Stylesheet/summ.css"/>
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
  <style>
    h2 {
        margin-left: 60px;
        margin-top: 10px;
        margin-bottom: 10px;
    }
    /* Basic Modal Styles (ensure these are sufficient or adapt from summ.css) */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1000; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        align-items: center; /* For flex display */
        justify-content: center; /* For flex display */
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px; /* Max width */
        position: relative;
    }

    .close {
        color: #aaa;
        float: right; /* Position to the top-right */
        font-size: 28px;
        font-weight: bold;
        position: absolute; /* Position relative to modal-content */
        top: 10px;
        right: 20px;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    #summaryDetails p { /* Example styling for content */
        margin-bottom: 10px;
        line-height: 1.6;
    }
    #summaryDetails h3 {
        margin-top: 0;
    }
  </style>
</head>

<body>
<div class="sidebar">
    <a href="reqConsult.php"><i class="fa-solid fa-notes-medical fa-3x" title="Request Consultation"></i></a>
    <a href="viewSum.php"><i class="fa-solid fa-clock-rotate-left fa-3x" title="History"></i></a>
    <a href="upcoming.php"><i class="fa-regular fa-calendar-xmark fa-3x" title="Appointments"></i></a>
</div>

<div class="container">
  <main class="content">
    <nav>
      <a href="patientHome.php">Home</a>
      <a href="#" class="active">Consultation</a>
    </nav>
    <br>
    <h2>History</h2>
    <?php if (empty($consultations)): ?>
        <p style="margin-left: 60px;">No consultation history found with a 'Prescribed' status.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Consultation Type</th>
          <th>Campus</th>
          <th>Summary</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($consultations as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['dateTime'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['consultType'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['campusName'] ?? '') ?></td>
            <td><a href="#" class="view-summary" data-id="<?= htmlspecialchars($row['appID'] ?? '') ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </main>
</div>

<i class="fa-regular fa-user fa-2xl" id="profile"></i>

<div id="summaryModal" class="modal"> {/* Removed style="display:none;" as JS will handle it */}
  <div class="modal-content">
    <span class="close">&times;</span>
    <div id="summaryDetails">
      <p>Loading summary...</p> {/* Optional loading text */}
    </div>
  </div>
</div>

<script src="../JScripts/patient.js"></script> {/* Assuming this is for other functionalities */}

<script>
// Inline script, specific for this page, placed before </body>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('summaryModal');
    const closeBtn = modal.querySelector('.close'); // More specific selector
    const summaryDetails = document.getElementById('summaryDetails');

    // Ensure modal is hidden on page load (if not already by CSS)
    modal.style.display = 'none';

    document.querySelectorAll('.view-summary').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const consultID = this.dataset.id;

            // Show loading state
            summaryDetails.innerHTML = '<p>Loading summary...</p>';
            modal.style.display = 'flex'; // Or 'block', depends on your CSS for centering

            fetch('getSummary.php?id=' + consultID)
                .then(response => {
                    if (!response.ok) {
                        // If server response is not OK (e.g. 404, 500)
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.text(); // Assumes getSummary.php returns HTML/text
                })
                .then(data => {
                    // IMPORTANT: Ensure 'data' from getSummary.php is sanitized
                    // on the server-side to prevent XSS if it contains user input.
                    summaryDetails.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error fetching summary:', error);
                    summaryDetails.innerHTML = '<p>Sorry, couldn\'t load the summary. Please try again later.</p>';
                });
        });
    });

    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(e) {
        if (e.target == modal) { // If click is on the modal backdrop
            modal.style.display = 'none';
        }
    });

    // Hiding modal before leaving the page (optional, but can prevent layout shifts on back/forward)
    window.addEventListener('beforeunload', function() {
        modal.style.display = 'none';
    });
});
</script>

</body>
</html>

<?php
$conn = null; // Close the database connection
?>