<?php
require '../Config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status'=>'error','message'=>'Invalid request']);
  exit;
}

$appID  = $_POST['id']     ?? null;
$status = $_POST['status'] ?? null;

if (!$appID || !$status) {
  echo json_encode(['status'=>'error','message'=>'Missing parameters']);
  exit;
}

// Only update if not already cancelled
$query = "UPDATE appointments 
          SET status = :status 
          WHERE appID = :appID 
            AND status != 'Cancelled'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':appID',  $appID, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
  echo json_encode([
    'status'  => 'error',
    'message' => 'Unable to update: appointment may already be cancelled.'
  ]);
} else {
  echo json_encode(['status'=>'success']);
}

$conn = null;
