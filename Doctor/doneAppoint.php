<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../Config/database.php'; // Ensure this sets up $conn as a PDO instance
// session_start(); // If you need session variables, uncomment this
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Prescribed Consultations</title>
  <link rel="stylesheet" href="../Stylesheet/consultGrant.css">
  <script src="https://kit.fontawesome.com/503ea13a85.js" crossorigin="anonymous"></script>
  <style>
    .view-btn {
      background-color: #4CAF50;
      border: none;
      color: white;
      padding: 5px 12px;
      font-size: 14px;
      border-radius: 5px;
      cursor: pointer;
    }
    .view-btn:hover {
      background-color: #45a049;
    }
    #searchInput {
      margin-bottom: 15px;
      padding: 8px;
      width: 300px;
      font-size: 16px;
      margin-left: 0;
    }

    .main-content h2:first-of-type {
      margin-left: 0;
      font-weight: bold;
      color: #333;
    }

    .navigation { /* Removed .prescribed from this rule as it was not used */
      margin-bottom: 15px;
      margin-left: 0;
    }
    /* Modal styling */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.6);
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background-color: #fff;
      margin: auto;
      padding: 20px;
      border-radius: 10px;
      width: 60%;
      max-width: 700px;
      position: relative;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .close {
      color: #aaa;
      position: absolute;
      top: 10px;
      right: 20px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
    }

    /* Container aligns items horizontally */
    .report-controls {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
      padding: 0.5rem 0;
    }

    /* Label spacing */
    .report-controls label {
      font-weight: 600;
      color: #333;
    }

    /* Styled dropdown */
    .report-select {
      padding: 0.4rem 0.6rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 0.95rem;
      background: #fff;
      cursor: pointer;
    }

    /* Primary button */
    .report-btn {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      background: #007BFF;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      font-size: 0.95rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.2s ease;
    }

    .report-btn:hover {
      background: #0056b3;
    }

    .report-btn i {
      font-size: 1rem;
    }
    /* Ensure table styling is consistent */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    th, td {
        padding: 10px 12px;
        border: 1px solid #ddd;
        text-align: left;
        vertical-align: middle;
    }
    th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    tbody tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    tbody tr:hover {
        background-color: #e9ecef;
    }

  </style>
</head>
<body>
  <div class="sidebar">
    <a href="viewAppointments.php" title="Dashboard"><i class="fa-solid fa-house fa-3x"></i></a>
    <a href="consultGrant.php" title="Consultation Requests"><i class="fa-solid fa-clipboard fa-3x"></i></a>
    <a href="#" class="active" title="Consultation History"><i class="fa-solid fa-book-medical fa-3x"></i></a>
  </div>

  <div class="main-content">
    <div class="navigation">
      <nav>
          <a href="docDashboard.php">Dashboard</a>
          <a href="consultGrant.php">Admission</a>
          <a href="#" class="active">History</a>
          <a href="../Inventory/INVDASH.html">Inventory</a>
      </nav>
    </div>
    <br>
    <h2>Prescribed Consultations | <?= htmlspecialchars(date('F d, Y') ?? '') ?></h2>
    <input type="text" id="searchInput" placeholder="Search by name..." style="margin-bottom: 15px; padding: 8px; width: 300px; font-size: 16px;">

    <div class="report-controls">
      <label for="reportSpan">Print for:</label>
      <select id="reportSpan" class="report-select">
        <option value="day">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
      </select>
      <button id="printReportBtn" class="report-btn">
        <i class="fa-solid fa-print"></i> Print Report
      </button>
    </div>

    <table>
      <thead>
        <tr>
          <th>School No.</th>
          <th>Name</th>
          <th>Position</th>
          <th>Department</th>
          <th>Consult Date</th>
          <th>Consult Type</th>
          <th>Mode</th>
          <th>Campus</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        try {
          // SQL query to fetch prescribed consultations
          $sql = "SELECT
            a.appID,
            u.schoolID AS school_no,
            CONCAT(u.fname, ' ', u.lname) AS name,
            r.roleName AS position,
            dept.deptName AS department,
            DATE_FORMAT(a.consultDate, '%Y-%m-%d %h:%i %p') AS consult_date, /* Formatted date */
            a.consultType AS consult_type,
            a.mode,
            c.campusName AS campus
          FROM appointments a
          JOIN userInfo u ON a.UID = u.UID
          JOIN campus c ON a.campID = c.campusID
          JOIN departments dept ON u.deptID = dept.deptID
          JOIN userroles ur ON u.UID = ur.UID
          JOIN roles r ON ur.roleID = r.roleID
          WHERE a.status = 'Prescribed'
          ORDER BY a.consultDate DESC;";

          $stmt = $conn->prepare($sql);
          $stmt->execute();

          if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo "<tr>";
              // Apply null coalescing operator ?? '' to prevent errors if a field is NULL
              echo "<td>" . htmlspecialchars($row['school_no'] ?? '') . "</td>";
              echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
              echo "<td>" . ucfirst(htmlspecialchars($row['position'] ?? '')) . "</td>";
              echo "<td>" . htmlspecialchars($row['department'] ?? '') . "</td>";
              echo "<td>" . htmlspecialchars($row['consult_date'] ?? '') . "</td>";
              echo "<td>" . htmlspecialchars($row['consult_type'] ?? '') . "</td>";
              echo "<td>" . htmlspecialchars($row['mode'] ?? '') . "</td>";
              echo "<td>" . htmlspecialchars($row['campus'] ?? '') . "</td>";
              echo "<td><button class='view-btn' data-id='" . htmlspecialchars($row['appID'] ?? '') . "'>View</button></td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='9' style='text-align:center;'>No prescribed consultations found</td></tr>";
          }
        } catch (PDOException $e) {
          // Display a user-friendly error message and log the detailed error
          error_log("Database Error in doneAppoint.php: " . $e->getMessage()); // Log error to server logs
          echo "<tr><td colspan='9' style='text-align:center; color:red;'>Could not retrieve data. Please try again later.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <div id="summaryModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <div id="modalDetails"><p>Loading details...</p></div>
    </div>
  </div>

  <i class="fa-regular fa-user" id="profile" title="Profile/Logout" style="position: fixed; top: 20px; right: 20px; cursor: pointer; font-size: 1.5rem; color: #333;"></i>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('summaryModal');
      const modalDetails = document.getElementById('modalDetails');
      const closeBtn = modal.querySelector('.close'); 

      document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function () {
          const appID = this.getAttribute('data-id');
          modalDetails.innerHTML = '<p>Loading details...</p>'; 
          modal.style.display = 'flex'; 

          fetch('getConsultDetails.php?id=' + encodeURIComponent(appID)) 
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
              }
              return response.text(); 
            })
            .then(data => {
              modalDetails.innerHTML = data;
            })
            .catch(error => {
              console.error('Error fetching consultation details:', error);
              modalDetails.innerHTML = '<p style="color:red;">Could not load details. Please try again.</p>';
            });
        });
      });

      if(closeBtn) { 
        closeBtn.onclick = function () {
          modal.style.display = 'none';
        };
      }

      window.onclick = function (event) {
        if (event.target == modal) {
          modal.style.display = 'none';
        }
      };

      const printReportBtn = document.getElementById('printReportBtn');
      if(printReportBtn) { 
        printReportBtn.addEventListener('click', () => {
          const span = document.getElementById('reportSpan').value;
          window.open(`report.php?span=${encodeURIComponent(span)}`, '_blank');
        });
      }

      const searchInput = document.getElementById('searchInput');
      if(searchInput) { 
        searchInput.addEventListener('keyup', function () {
          const filter = this.value.toLowerCase().trim();
          const rows = document.querySelectorAll("table tbody tr");

          let foundVisibleRow = false;
          rows.forEach(row => {
            const firstCell = row.cells[0];
            if (firstCell && firstCell.getAttribute('colspan') === '9') {
                row.style.display = 'none'; 
                return; 
            }

            const nameCell = row.cells[1]; 
            if (nameCell) {
              const name = nameCell.textContent.toLowerCase();
              if (name.includes(filter)) {
                row.style.display = "";
                foundVisibleRow = true;
              } else {
                row.style.display = "none";
              }
            }
          });

          const tbody = document.querySelector("table tbody");
          let noResultsRow = tbody.querySelector(".no-results-row");

          if (!foundVisibleRow && filter !== "") {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.classList.add('no-results-row');
                const cell = document.createElement('td');
                cell.setAttribute('colspan', '9');
                cell.style.textAlign = 'center';
                cell.textContent = 'No matching records found.';
                noResultsRow.appendChild(cell);
                tbody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = ""; 
          } else if (noResultsRow) {
            noResultsRow.style.display = "none"; 
          }

        });
      }
    });
  </script>
</body>
</html>
