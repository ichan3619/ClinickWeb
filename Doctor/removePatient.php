<?php
require '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appID = $_POST['appID'] ?? ''; 

    if (!empty($appID)) {
        try {
            $query = "UPDATE appointments SET status = 'Prescribed' WHERE appID = :appID";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':appID', $appID, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Patient removed successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing consultation ID.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}

$conn = null;
?>
