<?php
// consultGrant.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../Config/database.php';
session_start();

// No need for $patientID

$query = "SELECT
    a.appID,
    u.schoolID AS school_no,
    CONCAT(u.fname, ' ', u.lname) AS name,
    dept.deptName AS department,
    a.consultDate AS consult_date,
    a.consultType AS consult_type,
    a.mode,
    c.campusName AS campus,
    a.status
FROM appointments a
JOIN userInfo u      ON a.UID = u.UID
JOIN departments dept ON u.deptID = dept.deptID
JOIN campus c        ON a.campID = c.campusID
WHERE a.status = 'Pending'
  AND DATE(a.consultDate) >= CURDATE()
ORDER BY a.consultDate DESC";

$stmt = $conn->prepare($query);
$stmt->execute(); // ✅ no parameters
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);


// If fragment=1, return only the <tbody> rows and exit
if (isset($_GET['fragment'])) {
  if (count($appointments) === 0) {
    echo "<tr><td colspan='8' style='text-align:center;'>No pending consultation requests found</td></tr>";
  } else {
    foreach ($appointments as $row) {
      echo "<tr data-app-id='{$row['appID']}'>";
      echo "<td>" . htmlspecialchars($row['school_no'] ?? '')      . "</td>";
      echo "<td>" . htmlspecialchars($row['name'] ?? '')            . "</td>";
      echo "<td>" . htmlspecialchars($row['department'] ?? '')      . "</td>";
      echo "<td>" . htmlspecialchars($row['consult_date'] ?? '')    . "</td>";
      echo "<td>" . htmlspecialchars($row['consult_type'] ?? '')    . "</td>";
      echo "<td>" . htmlspecialchars($row['mode'] ?? '')            . "</td>";
      echo "<td>" . htmlspecialchars($row['campus'] ?? '')          . "</td>";
      echo "<td>
              <form class='status-form' method='post' action='updateConsultStatus.php' style='display:inline;'>
                <input type='hidden' name='id'     value='{$row['appID']}'>
                <input type='hidden' name='status' value='Approved'>
                <button type='submit' class='approve' title='Approve'>&#10004;</button>
              </form>
              <form class='status-form' method='post' action='updateConsultStatus.php' style='display:inline;'>
                <input type='hidden' name='id'     value='{$row['appID']}'>
                <input type='hidden' name='status' value='Rejected'>
                <button type='submit' class='deny' title='Reject'>&#10006;</button>
              </form>
            </td>";
      echo "</tr>";
    }
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Consultation Requests</title>
  <link rel="stylesheet" href="../Stylesheet/consultGrant.css">
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
  <style>
    .approve, .deny {
      border: none; padding: 4px 8px; margin:0 2px;
      cursor: pointer; border-radius: 3px;
    }
    .approve { background: #4CAF50; color: #fff; }
    .deny    { background: #f44336; color: #fff; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
  </style>
</head>
<body>
  <div class="sidebar">
    <a href="viewAppointments.php"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="#"><i class="fa-solid fa-clipboard fa-3x"></i></a>
    <a href="doneAppoint.php"><i class="fa-solid fa-book-medical fa-3x"></i></a>
  </div>
  <div class="main-content">
    <nav>
      <a href="docDashboard.php">Dashboard</a>
      <a href="#" class="active">Admission</a>
      <a href="../Inventory/INVDASH.html">Inventory</a>
    </nav>
    <h2>Consultation Requests | <?= htmlspecialchars(date('F d, Y') ?? '') ?></h2>
    <table>
      <thead>
        <tr>
          <th>School No.</th>
          <th>Name</th>
          <th>Department</th>
          <th>Consult Date</th>
          <th>Consult Type</th>
          <th>Mode</th>
          <th>Campus</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="requestsBody">
        <?php foreach ($appointments as $row): ?>
          <tr data-app-id="<?= htmlspecialchars($row['appID'] ?? '') ?>">
            <td><?= htmlspecialchars($row['school_no'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['department'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['consult_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['consult_type'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['mode'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['campus'] ?? '') ?></td>
            <td>
              <form class="status-form" method="post" action="updateConsultStatus.php" style="display:inline;">
                <input type="hidden" name="id"     value="<?= htmlspecialchars($row['appID'] ?? '') ?>">
                <input type="hidden" name="status" value="Approved">
                <button type="submit" class="approve">&#10004;</button>
              </form>
              <form class="status-form" method="post" action="updateConsultStatus.php" style="display:inline;">
                <input type="hidden" name="id"     value="<?= htmlspecialchars($row['appID'] ?? '') ?>">
                <input type="hidden" name="status" value="Rejected">
                <button type="submit" class="deny">&#10006;</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($appointments) === 0): ?>
          <tr><td colspan="8" style="text-align:center;">No pending consultation requests found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <i class="fa-regular fa-user" id="profile"></i>

  <script>
  // AJAX Approve/Reject
  function bindStatusForms() {
    document.querySelectorAll('.status-form').forEach(form => {
      form.addEventListener('submit', async e => {
        e.preventDefault();
        const status = form.querySelector('[name=status]').value.toLowerCase();
        if (!confirm(`Confirm ${status}?`)) return;

        const data = new FormData(form);
        const res  = await fetch(form.action, { method:'POST', body:data });
        const json = await res.json();

        if (json.status === 'success') {
          form.closest('tr').remove();

          // Notify upcoming.php if rejected
          if (status === 'rejected') {
            localStorage.setItem('refreshUpcoming', Date.now());
          }
          // Check if tbody is empty and add message if so
          const tbody = document.getElementById('requestsBody');
          if (tbody.children.length === 0) {
            tbody.innerHTML = "<tr><td colspan='8' style='text-align:center;'>No pending consultation requests found</td></tr>";
          }

        } else {
          alert('Error: ' + json.message);
        }
      });
    });
  }

  // Poll fragment every 15s
  async function reloadRequests() {
    try {
      const res  = await fetch('consultGrant.php?fragment=1');
      const html = await res.text();
      const tbody= document.getElementById('requestsBody');
      tbody.innerHTML = html;
      bindStatusForms();
    } catch(err) {
      console.error(err);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindStatusForms();
    setInterval(reloadRequests, 15000);
  });

  let doctorLastTs = 0;
  const doctorEvents = new EventSource('events.php?ts=' + doctorLastTs);

  // On init, store the server timestamp
  doctorEvents.addEventListener('init', e => {
    doctorLastTs = parseInt(e.data, 10);
  });

  </script>
</body>
</html>