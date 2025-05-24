<?php
require '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo 'invalid';
  exit;
}

$appID = $_POST['appID'] ?? null;
if (!$appID) {
  echo 'invalid';
  exit;
}

// Only cancel if not already cancelled
$query = "UPDATE appointments 
          SET status = 'Cancelled' 
          WHERE appID = :appID 
            AND status != 'Cancelled'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':appID', $appID, PDO::PARAM_INT);
$stmt->execute();

echo $stmt->rowCount() ? 'success' : 'error';
$conn = null;
