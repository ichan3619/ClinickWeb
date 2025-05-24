<?php
session_start();

// General session check: Ensure user is logged in
if (!isset($_SESSION['UID'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

// If nextInvID generation were campus-specific, you would also check for $_SESSION['activeCampusID'] here.
// For example:
// if (!isset($_SESSION['activeCampusID']) || empty($_SESSION['activeCampusID'])) {
//     http_response_code(400);
//     echo json_encode(['status' => 'error', 'message' => 'Active campus context is required to generate the next Inventory ID.']);
//     exit;
// }
// $campusID_for_next_id = $_SESSION['activeCampusID'];
// And then use $campusID_for_next_id in your SQL query if IDs are per campus.

include '../config/database.php'; // Moved DB include after session checks
header('Content-Type: application/json'); // Added header for consistency

try {
    // Current query gets the global max ID.
    // If IDs are campus-specific, the SQL query would need to be adjusted,
    // e.g., "SELECT MAX(invID) AS max_id FROM inventory WHERE campusID = :campusID"
    $sql = "SELECT MAX(invID) AS max_id FROM inventory";
    $stmt = $conn->query($sql); // For a simple query without parameters
    // If you were using $campusID_for_next_id:
    // $stmt = $conn->prepare($sql);
    // $stmt->execute([':campusID' => $campusID_for_next_id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $nextId = $row['max_id'] ? $row['max_id'] + 1 : 1;

    echo json_encode(["next_id" => $nextId]);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>