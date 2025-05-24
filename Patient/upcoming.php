<?php
require '../Config/database.php';
session_start();
$patientID = $_SESSION['patientID'];

$query = "SELECT a.appID, a.consultDate, a.consultType, a.link, c.campusName, a.mode, a.status
          FROM appointments a
          JOIN campus c ON a.campID = c.campusID
          WHERE a.UID = :UID 
            AND a.consultDate >= CURDATE()
            AND a.status NOT IN ('Cancelled', 'Prescribed', 'Rejected')
          ORDER BY 
            CASE WHEN a.status = 'Approved' THEN 0 ELSE 1 END,
            a.consultDate ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':UID', $patientID, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upcoming Consultations</title>
  <link rel="stylesheet" href="../Stylesheet/summ.css">
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
  <style>
    nav{
        margin-bottom: 40px;
    }
    h2{
        margin-left: 60px;
        padding-top: 20px;
        margin-top: 10px;
        margin-bottom: 10px;
    }
    .meeting-link {
    text-decoration: none;
    color: #0077cc;
    font-weight: bold;
    }
    .meeting-link i {
    margin-right: 5px;
    }

    .cancel-btn {
    background-color: #cc0000;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    }
    .cancel-btn:hover {
    background-color: #a00000;
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
      <a href="#" class="active">Consultations</a>
    </nav>

    <h2>Upcoming Consultations</h2>
    <?php if (count($appointments) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Campus</th>
            <th>Mode</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($appointments as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['consultDate']) ?></td>
              <td><?= htmlspecialchars($row['consultType']) ?></td>
              <td><?= htmlspecialchars($row['campusName']) ?></td>
              <td><?= htmlspecialchars($row['mode']) ?></td>
              <td>
                <span class="status <?= strtolower($row['status']) ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
            <td>
                <?php if (!empty($row['link'])): ?>
                    <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank" class="meeting-link">
                    <i class="fa-solid fa-video"></i> Join
                    </a>
                <?php elseif (strtolower($row['status']) === 'pending'): ?>
                    <form class="cancel-form" method="POST" action="cancelAppointment.php" style="display:inline;">
                    <input type="hidden" name="appID" value="<?= htmlspecialchars($row['appID']) ?>">
                    <button type="submit" class="cancel-btn">Cancel</button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>



            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div style="justify-content: center;
                  display: flex;
                  height: 100vh;">
        <p>No upcoming consultations found.</p>
      </div>
    <?php endif; ?>
  </main>
</div>

<i class="fa-regular fa-user fa-2xl" id="profile"></i>
<script>
document.querySelectorAll('.cancel-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();                       // Prevent normal form submit
    if (!confirm('Are you sure you want to cancel this appointment?')) return;

    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData
    })
    .then(res => res.text())
    .then(responseText => {
    // Remove the row immediately
    form.closest('tr').remove();

    // Notify other pages to refresh consultGrant.php
    localStorage.setItem('refreshConsultGrant', Date.now());
    })

    .catch(err => {
      console.error('Cancellation error:', err);
      alert('Failed to cancel. Please try again.');
    });
  });
});

  // 1) Create an EventSource to our SSE endpoint, include ts if you want
  let lastTs = 0;
  const es = new EventSource('events.php?ts=' + lastTs);

  // 2) When we get the init event, store the server's timestamp
  es.addEventListener('init', e => {
    lastTs = parseInt(e.data, 10);
  });

  // 3) When we get an update event, reload the page (or re-fetch the table)
  es.addEventListener('update', e => {
    console.log('Data changed on server, reloading...');
    window.location.reload();
  });

  // 4) Optional: handle errors
  es.onerror = () => {
    console.error('SSE connection error—will retry automatically');
  };
</script>
</body>
</html>

<?php $conn = null; ?>