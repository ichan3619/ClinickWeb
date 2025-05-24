<?php
require '../Config/database.php';  

if (isset($_GET['id'])) {
    $appID = (int) $_GET['id'];

    $query = "SELECT a.consultDate, a.consultType, c.campusName, cs.diagnosis, cs.recommendations
              FROM appointments a
              LEFT JOIN consultSummary cs ON a.appID = cs.appID
              JOIN campus c ON a.campID = c.campusID
              WHERE a.appID = :appID";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);
    $stmt->execute();
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consultation) {
        echo "<p>Consultation not found.</p>";
        exit;
    }

    echo "<h2>Consultation Details</h2>";
    echo "<p><strong>Date:</strong> " . htmlspecialchars($consultation['consultDate']) . "</p>";
    echo "<p><strong>Type:</strong> " . htmlspecialchars($consultation['consultType']) . "</p>";
    echo "<p><strong>Campus:</strong> " . htmlspecialchars($consultation['campusName']) . "</p>";

    echo "<h2>Diagnosis</h2>";
    echo $consultation['diagnosis'] ? 
        "<p>" . nl2br(htmlspecialchars($consultation['diagnosis'])) . "</p>" : 
        "<p><em>No diagnosis provided.</em></p>";

    echo "<h2>Recommendations</h2>";
    echo $consultation['recommendations'] ? 
        "<p>" . nl2br(htmlspecialchars($consultation['recommendations'])) . "</p>" : 
        "<p><em>No recommendations provided.</em></p>";

    // Fetch medications
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
            echo "Duration: " . htmlspecialchars($med['duration']);
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><em>No medications prescribed.</em></p>";
    }

} else {
    echo "<p>No consultation selected.</p>";
}

$conn = null;
?>
