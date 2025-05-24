<?php
session_start();

// General session check: Ensure user is logged in
if (!isset($_SESSION['UID'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

require '../Config/database.php'; // Moved DB include after session checks
header('Content-Type: application/json');

$category_filter = $_GET['category'] ?? '';
$search_filter = $_GET['search'] ?? '';
$campus_filter_from_request = $_GET['campus'] ?? '';

$sql = "SELECT i.*, c.campusName AS invCampus 
        FROM inventory i 
        JOIN campus c ON i.campusID = c.campusID 
        WHERE i.invStatus = 'active'";
$params = [];

if (!empty($category_filter)) {
    $sql .= " AND i.invCategory = :category";
    $params[':category'] = $category_filter;
}

if (!empty($search_filter)) {
    $sql .= " AND (i.invID LIKE :search OR i.invName LIKE :search)";
    $params[':search'] = "%" . $search_filter . "%";
}

$campus_condition_added = false;
if (!empty($campus_filter_from_request)) {
    if (is_numeric($campus_filter_from_request)) {
        $sql .= " AND i.campusID = :campus_req";
        $params[':campus_req'] = $campus_filter_from_request;
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
        // This state should ideally be prevented by campusSelect.php for relevant roles.
        // Return an error or empty set, rather than all campuses.
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Active campus context is required or select "All" campuses.']);
        exit;
    }
}

$sql .= " ORDER BY i.invID DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($inventory);

} catch (PDOException $e) {
    error_log("Error in loadInv.php: " . $e->getMessage());
    echo json_encode(["error" => "Could not fetch inventory.", "details" => $e->getMessage()]);
}
?>