<?php
require '../Config/database.php';  
session_start();
if (!isset($_SESSION['patientID'])) {
    die("Unauthorized. Please log in.");
}
$patientID = $_SESSION['patientID'];

if (isset($_GET['id'])) {
    $appID = (int) $_GET['id'];

    // Fetch consultation details
    $query = "SELECT a.consultDate, a.consultType, c.campusName, cs.diagnosis, cs.recommendations
          FROM appointments a
          JOIN consultSummary cs ON a.appID = cs.appID
          JOIN campus c ON a.campID = c.campusID
          WHERE a.appID = :appID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);
    $stmt->execute();
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($consultation) {
        echo "<h2>Consultation Details</h2>";
        echo "<p><strong>Date:</strong> " . htmlspecialchars($consultation['consultDate']) . "</p>";
        echo "<p><strong>Type:</strong> " . htmlspecialchars($consultation['consultType']) . "</p>";
        echo "<p><strong>Campus:</strong> " . htmlspecialchars($consultation['campusName']) . "</p>";
        
        echo "<hr>";

/*        echo "<h2>Diagnosis</h2>";
        echo "<p>" . htmlspecialchars($consultation['diagnosis']) . "</p>";*/

        echo "<h2>Recommendations</h2>";
        echo "<p>" . htmlspecialchars($consultation['recommendations']) . "</p>";

        echo "<hr>";

        // Fetch medications from consultationMedications
        $medQuery = "SELECT medName, dosage, frequency, duration
                     FROM consultationMedication
                     WHERE appID = :appID";
        $medStmt = $conn->prepare($medQuery);
        $medStmt->bindParam(':appID', $appID, PDO::PARAM_INT);
        $medStmt->execute();
        $medications = $medStmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>Medications</h2>";
        if (!empty($medications)) {
            echo "<ul>";
            foreach ($medications as $med) {
                echo "<li>";
                echo "<strong>" . htmlspecialchars($med['medName']) . "</strong><br>";
                echo "Dosage: " . htmlspecialchars($med['dosage']) . "<br>";
                echo "Frequency: " . htmlspecialchars($med['frequency']) . "<br>";
                echo "Duration: " . htmlspecialchars($med['duration']) . "<br><br>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No medications prescribed.</p>";
        }
    } else {
        echo "<p>Summary not found.</p>";
    }
} else {
    echo "<p>No consultation selected.</p>";
}

$conn = null;
?>