<?php
session_start();

// General session check: Ensure user is logged in
if (!isset($_SESSION['UID'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

include '../config/database.php'; // Moved DB include after session checks
header('Content-Type: application/json'); //

// Get filter values from the URL
$category_filter = $_GET['category'] ?? ''; //
$search_filter = $_GET['search'] ?? ''; //
$campus_filter_from_request = $_GET['campus'] ?? ''; // This is the campus ID from the request

// SQL to get archived inventory items and their campus names
$sql = "SELECT i.*, c.campusName AS invCampus 
        FROM inventory i 
        JOIN campus c ON i.campusID = c.campusID 
        WHERE i.invStatus = 'archived'"; // Only archived items
$params = []; //

if (!empty($category_filter)) {
    $sql .= " AND i.invCategory = :category"; //
    $params[':category'] = $category_filter; //
}

if (!empty($search_filter)) {
    $sql .= " AND (i.invID LIKE :search OR i.invName LIKE :search)"; //
    $params[':search'] = "%" . $search_filter . "%"; //
}

// Campus Filtering Logic for Archived Items:
$campus_condition_added = false;
if (!empty($campus_filter_from_request)) {
    if (is_numeric($campus_filter_from_request)) { // Assuming campus IDs are numeric
        $sql .= " AND i.campusID = :campus_req"; //
        $params[':campus_req'] = $campus_filter_from_request; //
        $campus_condition_added = true;
    } elseif (strtolower($campus_filter_from_request) === 'all') {
        // "All" selected, so no campus-specific WHERE clause for i.campusID needed from filter
        $campus_condition_added = true; // Considered handled, shows all
    }
}

if (!$campus_condition_added) {
    // If no valid campus filter from request (empty or not 'all'), use session's activeCampusID
    if (isset($_SESSION['activeCampusID']) && !empty($_SESSION['activeCampusID'])) {
        $sql .= " AND i.campusID = :session_campus";
        $params[':session_campus'] = $_SESSION['activeCampusID'];
    } else {
        // User is logged in (UID is set), but no campus filter and no activeCampusID in session.
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Active campus context is required to view archived items or select "All" campuses.']);
        exit;
    }
}

$sql .= " ORDER BY i.archivedTimestamp DESC"; // Show recently archived first

try {
    $stmt = $conn->prepare($sql); //
    $stmt->execute($params); //
    $archivedItems = $stmt->fetchAll(PDO::FETCH_ASSOC); //
    echo json_encode($archivedItems); //

} catch (PDOException $e) {
    error_log("Error in FetchArch.php: " . $e->getMessage()); //
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Could not fetch archived inventory.", "details" => $e->getMessage()]); //
}
?>