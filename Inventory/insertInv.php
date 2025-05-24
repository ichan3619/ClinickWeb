<?php
session_start();

// General session check: Ensure user is logged in
if (!isset($_SESSION['UID'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

// Specific check for active campus, crucial for this script
if (!isset($_SESSION['activeCampusID']) || empty($_SESSION['activeCampusID'])) {
    http_response_code(400); // Bad Request (as a required parameter for operation is missing from session)
    echo json_encode(['status' => 'error', 'message' => 'Active campus information not found in session. Please log in again or select a campus.']);
    exit;
}
$campusID = $_SESSION['activeCampusID'];

include '../config/database.php'; // Moved DB include after session checks

$data = json_decode(file_get_contents("php://input"), true);

$invName = $data['invName'] ?? '';
$invDescription = $data['invDescription'] ?? '';
$invCategory = $data['invCategory'] ?? '';
$invDosage = $data['invDosage'] ?? '';
$itemQuantity = $data['itemQuantity'] ?? '';
$invSupplyDate = $data['invSupplyDate'] ?? '';
$invExpiryDate = $data['invExpiryDate'] ?? NULL;

if (empty($invName) || empty($invCategory) || empty($itemQuantity) || empty($invSupplyDate)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields (Name, Category, Quantity, Supply Date).']);
    exit;
}

if (!empty($invSupplyDate)) {
    $today = date("Y-m-d");
    if ($invSupplyDate > $today) {
        echo json_encode(['status' => 'error', 'message' => 'Supply date cannot be in the future.']);
        exit;
    }
}

try {
    $sql = "INSERT INTO inventory (invName, invDescription, invCategory, invDosage, itemQuantity, invSupplyDate, invExpiryDate, campusID, invStatus)
            VALUES (:invName, :invDescription, :invCategory, :invDosage, :itemQuantity, :invSupplyDate, :invExpiryDate, :campusID, 'active')";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([
        ':invName' => $invName,
        ':invDescription' => $invDescription,
        ':invCategory' => $invCategory,
        ':invDosage' => $invDosage,
        ':itemQuantity' => $itemQuantity,
        ':invSupplyDate' => $invSupplyDate,
        ':invExpiryDate' => $invExpiryDate,
        ':campusID' => $campusID
    ]);
    
    echo json_encode(['status' => 'success', 'message' => 'Item added successfully!']);

} catch (PDOException $e) {
    error_log("Error in insertInv.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>