<?php
require '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appID = $_POST['appID'] ?? null;
  $link = $_POST['link'] ?? null;

  if ($appID && $link) {
    $query = "UPDATE appointments SET link = :link WHERE appID = :appID";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':link', $link);
    $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);

    if ($stmt->execute()) {
      echo "Link successfully updated.";
    } else {
      echo "Failed to update the link.";
    }
  } else {
    echo "Invalid request.";
  }
} else {
  echo "Invalid access.";
}

$conn = null;
?>
