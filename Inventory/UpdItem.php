<?php
session_start();

// General session check: Ensure user is logged in
if (!isset($_SESSION['UID'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

include '../config/database.php'; // Moved DB include after session checks

$data = json_decode(file_get_contents("php://input"), true);

// Basic check if data is received
if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
    exit;
}

$invID = $data['invID'] ?? '';
$invName = $data['invName'] ?? '';
$invDescription = $data['invDescription'] ?? '';
$invCategory = $data['invCategory'] ?? '';
$invDosage = $data['invDosage'] ?? '';
$itemQuantity = $data['itemQuantity'] ?? '';
$invSupplyDate = $data['invSupplyDate'] ?? '';
$invExpiryDate = $data['invExpiryDate'] ?? NULL;
$campusID = $data['campusID'] ?? '';

// Add validation for required fields for an update
if (empty($invID)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Inventory ID is required for update.']);
    exit;
}
// Add other necessary validations for $invName, $campusID etc. if they are mandatory

try {
    $sql = "UPDATE inventory 
            SET 
                invName = :invName, 
                invDescription = :invDescription, 
                invCategory = :invCategory, 
                invDosage = :invDosage, 
                itemQuantity = :itemQuantity, 
                invSupplyDate = :invSupplyDate, 
                invExpiryDate = :invExpiryDate,
                campusID = :campusID
            WHERE 
                invID = :invID";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([
        ':invID' => $invID,
        ':invName' => $invName,
        ':invDescription' => $invDescription,
        ':invCategory' => $invCategory,
        ':invDosage' => $invDosage,
        ':itemQuantity' => $itemQuantity,
        ':invSupplyDate' => $invSupplyDate,
        ':invExpiryDate' => $invExpiryDate,
        ':campusID' => $campusID
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Inventory updated successfully.']);
    } else {
        $checkStmt = $conn->prepare("SELECT invID FROM inventory WHERE invID = :invID");
        $checkStmt->execute([':invID' => $invID]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
        } else {
            echo json_encode(['status' => 'info', 'message' => 'No changes were made to the inventory item.']);
        }
    }

} catch (PDOException $e) {
    error_log("Error in UpdItem.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>