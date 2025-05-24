<?php
$appId = $_GET['appID'] ?? '';
$patientName = $_GET['patient_name'] ?? '';
?>

<h3>Prescription for <?php echo htmlspecialchars($patientName); ?></h3>

<form id="prescriptionForm" method="post" action="submitprescription.php">
  <input type="hidden" name="appID" value="<?php echo htmlspecialchars($appId); ?>" />
  <label for="diagnosis">Diagnosis:</label><br>
  <textarea id="diagnosis" name="diagnosis" rows="3" required></textarea><br><br>

  <label for="recommendations">Recommendations:</label><br>
  <textarea id="recommendations" name="recommendations" rows="3" required></textarea><br><br>

  <div id="medicationsContainer">
    <div class="medicationEntry">
      <label>Medication Name:</label><br>
      <input type="text" name="medicationName[]" required><br><br>

      <label>Dosage:</label><br>
      <input type="text" name="dosage[]" required><br><br>

      <label>Frequency:</label><br>
      <input type="text" name="frequency[]" required><br><br>

      <label>Duration:</label><br>
      <input type="text" name="duration[]" required><br><br>
    </div>
  </div> 
  <button type="submit">Submit Prescription</button>
</form>
