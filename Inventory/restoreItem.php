<?php
session_start();

// General session check: Ensure user is logged in
if (!isset($_SESSION['UID'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

include '../config/database.php'; // Moved DB include after session checks
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// Basic check if data is received
if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
    exit;
}

$invID = $data['inventory_no'] ?? null;

if (empty($invID)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing inventory ID.']);
    exit;
}

try {
    // Update the status of the item to 'active' and clear the archivedTimestamp
    $sql = "UPDATE inventory 
            SET invStatus = 'active', 
                archivedTimestamp = NULL 
            WHERE invID = :invID AND invStatus = 'archived'"; // Ensure we only restore archived items

    $stmt = $conn->prepare($sql);
    $stmt->execute([':invID' => $invID]); //

    if ($stmt->rowCount() > 0) { //
        echo json_encode(['status' => 'success', 'message' => 'Item restored successfully.']); //
    } else {
        // Check if the item was already active or not found
        $checkSql = "SELECT invStatus FROM inventory WHERE invID = :invID"; //
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([':invID' => $invID]); //
        $item = $checkStmt->fetch(PDO::FETCH_ASSOC); //

        if ($item) { //
            if ($item['invStatus'] === 'active') { //
                echo json_encode(['status' => 'info', 'message' => 'Item was already active.']); //
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Failed to restore item. Item might not be archived or status is unexpected: ' . $item['invStatus']]); //
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found or could not be restored.']); //
        }
    }

} catch (PDOException $e) {
    error_log("Restore Error (restoreItem.php): " . $e->getMessage()); //
    echo json_encode(['status' => 'error', 'message' => 'Database error during restoration process.']); //
}
?>