<?php
require '../Config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied."]);
    exit;
}

$appId = $_POST['appID'] ?? null;
$diagnosis = $_POST['diagnosis'] ?? '';
$recommendations = $_POST['recommendations'] ?? '';
$medicationNames = $_POST['medicationName'] ?? [];
$dosages = $_POST['dosage'] ?? [];
$frequencies = $_POST['frequency'] ?? [];
$durations = $_POST['duration'] ?? [];


if (!$appId) {
    echo json_encode(["status" => "error", "message" => "Appointment ID missing."]);
    exit;
}

try {
    // Check if appointment exists
    $stmt = $conn->prepare("SELECT appID FROM appointments WHERE appID = ?");
    $stmt->execute([$appId]);


    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["status" => "error", "message" => "Invalid appointment ID."]);
        exit;
    }

    // Insert into consultSummary with current date
    $stmt = $conn->prepare("INSERT INTO consultSummary (appID, diagnosis, recommendations, summaryDate) VALUES (?, ?, ?, CURDATE())");
    $stmt->execute([$appId, $diagnosis, $recommendations]);

    // Insert medications into ConsultationMedication
    $stmtMed = $conn->prepare("INSERT INTO ConsultationMedication (appID, medName, dosage, frequency, duration) VALUES (?, ?, ?, ?, ?)");

    for ($i = 0; $i < count($medicationNames); $i++) {
        if (trim($medicationNames[$i]) === '') continue;
        $stmtMed->execute([
            $appId,
            $medicationNames[$i],
            $dosages[$i] ?? '',
            $frequencies[$i] ?? '',
            $durations[$i] ?? ''
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Prescription submitted successfully."]);
    exit;

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>
