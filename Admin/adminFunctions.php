<?php
session_start(); 

if (!isset($_SESSION['UID']) || !isset($_SESSION['roleName']) || $_SESSION['roleName'] !== 'Admin') {
    http_response_code(403); 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in as an Admin.']);
    exit;
}

header('Content-Type: application/json'); 
require_once '../Config/database.php'; 

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Database connection not established. Check Config/database.php."]);
    exit;
}

$action = $_REQUEST['action'] ?? ''; 

switch ($action) {
    // Department Actions
    case 'get_departments': getDepartments($conn); break;
    case 'add_department': addDepartment($conn); break;
    case 'update_department': updateDepartment($conn); break;
    case 'delete_department': deleteDepartment($conn); break;

    // Campus Actions
    case 'get_campuses': getCampuses($conn); break;
    case 'add_campus': addCampus($conn); break;
    case 'update_campus': updateCampus($conn); break;
    case 'delete_campus': deleteCampus($conn); break;

    // Role Definition Actions
    case 'get_roles': getRoles($conn); break;
    case 'add_role': addRole($conn); break;
    case 'update_role': updateRole($conn); break;
    case 'delete_role': deleteRole($conn); break;

    // Role Assignment Approval Actions
    case 'get_pending_role_assignments': getPendingRoleAssignments($conn); break;
    case 'approve_role_assignment': approveRoleAssignment($conn); break;
    case 'reject_role_assignment': rejectRoleAssignment($conn); break; // ADDED CASE

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}

// --- DEPARTMENT FUNCTIONS ---
// ... (Your existing department functions: getDepartments, addDepartment, updateDepartment, deleteDepartment) ...
function getDepartments($db) {
    try {
        $stmt = $db->query("SELECT deptID, deptName FROM departments ORDER BY deptName");
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $departments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch departments: ' . $e->getMessage()]);
    }
}

function addDepartment($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['deptName'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Department name is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("INSERT INTO departments (deptName) VALUES (:deptName)");
        $stmt->bindParam(':deptName', $data['deptName']);
        $stmt->execute();
        $lastId = $db->lastInsertId();
        echo json_encode(['status' => 'success', 'message' => 'Department added successfully.', 'data' => ['deptID' => $lastId, 'deptName' => $data['deptName']]]);
    } catch (PDOException $e) {
        http_response_code(500);
        if ($e->getCode() == 23000) { 
             echo json_encode(['status' => 'error', 'message' => 'Department name already exists.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Failed to add department: ' . $e->getMessage()]);
        }
    }
}

function updateDepartment($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['deptID']) || !isset($data['deptName'])) { 
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Department ID and name are required.']);
        return;
    }
    try {
        $stmt = $db->prepare("UPDATE departments SET deptName = :deptName WHERE deptID = :deptID");
        $stmt->bindParam(':deptName', $data['deptName']);
        $stmt->bindParam(':deptID', $data['deptID'], PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Department updated successfully.']);
        } else {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE deptID = :deptID AND deptName = :deptName");
            $checkStmt->bindParam(':deptID', $data['deptID'], PDO::PARAM_INT);
            $checkStmt->bindParam(':deptName', $data['deptName']);
            $checkStmt->execute();
            if($checkStmt->fetchColumn() > 0) {
                 echo json_encode(['status' => 'info', 'message' => 'No changes made to the department name.']);
            } else {
                 $checkExistStmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE deptID = :deptID");
                 $checkExistStmt->bindParam(':deptID', $data['deptID'], PDO::PARAM_INT);
                 $checkExistStmt->execute();
                 if($checkExistStmt->fetchColumn() == 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Department not found.']);
                 } else {
                    echo json_encode(['status' => 'info', 'message' => 'No changes made to the department name (it might be the same).']);
                 }
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        if ($e->getCode() == 23000) {
             echo json_encode(['status' => 'error', 'message' => 'Another department with this name already exists.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Failed to update department: ' . $e->getMessage()]);
        }
    }
}

function deleteDepartment($db) {
    $deptID = $_REQUEST['deptID'] ?? null; 
    if (empty($deptID)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Department ID is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("DELETE FROM departments WHERE deptID = :deptID");
        $stmt->bindParam(':deptID', $deptID, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Department deleted successfully.']);
        } else {
            echo json_encode(['status' => 'info', 'message' => 'Department not found or already deleted.']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') { 
            http_response_code(409); 
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete department. It is referenced by other records (e.g., user info, appointments). Please update or remove those references first.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete department: ' . $e->getMessage()]);
        }
    }
}

// --- CAMPUS FUNCTIONS ---
// ... (Your existing campus functions: getCampuses, addCampus, updateCampus, deleteCampus) ...
function getCampuses($db) {
    try {
        $stmt = $db->query("SELECT campusID, campusName FROM campus ORDER BY campusName");
        $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $campuses]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch campuses: ' . $e->getMessage()]);
    }
}

function addCampus($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['campusName'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Campus name is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("INSERT INTO campus (campusName) VALUES (:campusName)");
        $stmt->bindParam(':campusName', $data['campusName']);
        $stmt->execute();
        $lastId = $db->lastInsertId();
        echo json_encode(['status' => 'success', 'message' => 'Campus added successfully.', 'data' => ['campusID' => $lastId, 'campusName' => $data['campusName']]]);
    } catch (PDOException $e) {
        http_response_code(500);
         if ($e->getCode() == 23000) {
             echo json_encode(['status' => 'error', 'message' => 'Campus name already exists.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add campus: ' . $e->getMessage()]);
        }
    }
}

function updateCampus($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['campusID']) || !isset($data['campusName'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Campus ID and name are required.']);
        return;
    }
    try {
        $stmt = $db->prepare("UPDATE campus SET campusName = :campusName WHERE campusID = :campusID");
        $stmt->bindParam(':campusName', $data['campusName']);
        $stmt->bindParam(':campusID', $data['campusID'], PDO::PARAM_INT);
        $stmt->execute();
         if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Campus updated successfully.']);
        } else {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM campus WHERE campusID = :campusID AND campusName = :campusName");
            $checkStmt->bindParam(':campusID', $data['campusID'], PDO::PARAM_INT);
            $checkStmt->bindParam(':campusName', $data['campusName']);
            $checkStmt->execute();
            if($checkStmt->fetchColumn() > 0) {
                 echo json_encode(['status' => 'info', 'message' => 'No changes made to the campus name.']);
            } else {
                 $checkExistStmt = $db->prepare("SELECT COUNT(*) FROM campus WHERE campusID = :campusID");
                 $checkExistStmt->bindParam(':campusID', $data['campusID'], PDO::PARAM_INT);
                 $checkExistStmt->execute();
                 if($checkExistStmt->fetchColumn() == 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Campus not found.']);
                 } else {
                    echo json_encode(['status' => 'info', 'message' => 'No changes made to the campus name (it might be the same).']);
                 }
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        if ($e->getCode() == 23000) {
             echo json_encode(['status' => 'error', 'message' => 'Another campus with this name already exists.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update campus: ' . $e->getMessage()]);
        }
    }
}

function deleteCampus($db) {
    $campusID = $_REQUEST['campusID'] ?? null;
    if (empty($campusID)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Campus ID is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("DELETE FROM campus WHERE campusID = :campusID");
        $stmt->bindParam(':campusID', $campusID, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Campus deleted successfully.']);
        } else {
            echo json_encode(['status' => 'info', 'message' => 'Campus not found or already deleted.']);
        }
    } catch (PDOException $e) {
         if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete campus. It is referenced by other records. Please update or remove those references first.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete campus: ' . $e->getMessage()]);
        }
    }
}

// --- ROLE DEFINITION FUNCTIONS ---
// ... (Your existing role definition functions: getRoles, addRole, updateRole, deleteRole) ...
function getRoles($db) {
    try {
        $stmt = $db->query("SELECT roleID, roleName FROM roles ORDER BY roleName");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $roles]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch roles: ' . $e->getMessage()]);
    }
}

function addRole($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['roleName'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Role name is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("INSERT INTO roles (roleName) VALUES (:roleName)");
        $stmt->bindParam(':roleName', $data['roleName']);
        $stmt->execute();
        $lastId = $db->lastInsertId();
        echo json_encode(['status' => 'success', 'message' => 'Role added successfully.', 'data' => ['roleID' => $lastId, 'roleName' => $data['roleName']]]);
    } catch (PDOException $e) {
        http_response_code(500);
         if ($e->getCode() == 23000) {
             echo json_encode(['status' => 'error', 'message' => 'Role name already exists.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add role: ' . $e->getMessage()]);
        }
    }
}

function updateRole($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['roleID']) || !isset($data['roleName'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Role ID and name are required.']);
        return;
    }
    try {
        $stmt = $db->prepare("UPDATE roles SET roleName = :roleName WHERE roleID = :roleID");
        $stmt->bindParam(':roleName', $data['roleName']);
        $stmt->bindParam(':roleID', $data['roleID'], PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Role updated successfully.']);
        } else {
             $checkStmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE roleID = :roleID AND roleName = :roleName");
            $checkStmt->bindParam(':roleID', $data['roleID'], PDO::PARAM_INT);
            $checkStmt->bindParam(':roleName', $data['roleName']);
            $checkStmt->execute();
            if($checkStmt->fetchColumn() > 0) {
                 echo json_encode(['status' => 'info', 'message' => 'No changes made to the role name.']);
            } else {
                 $checkExistStmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE roleID = :roleID");
                 $checkExistStmt->bindParam(':roleID', $data['roleID'], PDO::PARAM_INT);
                 $checkExistStmt->execute();
                 if($checkExistStmt->fetchColumn() == 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Role not found.']);
                 } else {
                    echo json_encode(['status' => 'info', 'message' => 'No changes made to the role name (it might be the same).']);
                 }
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
         if ($e->getCode() == 23000) {
             echo json_encode(['status' => 'error', 'message' => 'Another role with this name already exists.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update role: ' . $e->getMessage()]);
        }
    }
}

function deleteRole($db) {
    $roleID = $_REQUEST['roleID'] ?? null;
    if (empty($roleID)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Role ID is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("DELETE FROM roles WHERE roleID = :roleID");
        $stmt->bindParam(':roleID', $roleID, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Role deleted successfully.']);
        } else {
            echo json_encode(['status' => 'info', 'message' => 'Role not found or already deleted.']);
        }
    } catch (PDOException $e) {
         if ($e->getCode() == '23000') { 
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete role. It is referenced by user roles. Please update or remove those references first.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete role: ' . $e->getMessage()]);
        }
    }
}


// --- ROLE ASSIGNMENT APPROVAL FUNCTIONS ---
function getPendingRoleAssignments($db) {
    try {
        $stmt = $db->query("SELECT ur.uRolesID, ui.fname, ui.lname, ua.email, r.roleName 
                            FROM userRoles ur
                            JOIN userInfo ui ON ur.UID = ui.UID
                            JOIN userAccounts ua ON ui.accID = ua.accID
                            JOIN roles r ON ur.roleID = r.roleID
                            WHERE ur.status = 'not approved'
                            ORDER BY ur.uRolesID DESC");
        $pending_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $pending_assignments]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Get Pending Roles Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch pending role assignments. ' . $e->getMessage()]);
    }
}

function approveRoleAssignment($db) {
    $uRolesID = $_POST['uRolesID'] ?? $_GET['uRolesID'] ?? null;
    if (empty($uRolesID)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'User Role Assignment ID (uRolesID) is required.']);
        return;
    }
    try {
        $stmt = $db->prepare("UPDATE userRoles SET status = 'approved' 
                              WHERE uRolesID = :uRolesID AND status = 'not approved'");
        $stmt->bindParam(':uRolesID', $uRolesID, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Role assignment approved successfully.']);
        } else {
            $checkStmt = $db->prepare("SELECT status FROM userRoles WHERE uRolesID = :uRolesID");
            $checkStmt->bindParam(':uRolesID', $uRolesID, PDO::PARAM_INT);
            $checkStmt->execute();
            $currentAssignment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($currentAssignment) {
                if ($currentAssignment['status'] === 'approved') {
                    echo json_encode(['status' => 'info', 'message' => 'This role assignment was already approved.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Role assignment status could not be updated. Current status: ' . htmlspecialchars($currentAssignment['status'])]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Role assignment not found.']);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Approve Role Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to approve role assignment: ' . $e->getMessage()]);
    }
}

// NEW FUNCTION FOR REJECTING ROLE ASSIGNMENT START
function rejectRoleAssignment($db) {
    $uRolesID = $_POST['uRolesID'] ?? $_GET['uRolesID'] ?? null;

    if (empty($uRolesID)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'User Role Assignment ID (uRolesID) is required for rejection.']);
        return;
    }

    try {
        // Attempt to delete the userRole entry directly.
        // It's good practice to also check status = 'not approved' if that's the explicit business rule
        // for what can be rejected, but deletion by uRolesID is the primary action.
        $stmt = $db->prepare("DELETE FROM userRoles WHERE uRolesID = :uRolesID AND status = 'not approved'");
        $stmt->bindParam(':uRolesID', $uRolesID, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Role assignment rejected and deleted successfully.']);
        } else {
            // Check if it existed to provide better feedback
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM userRoles WHERE uRolesID = :uRolesID");
            $checkStmt->bindParam(':uRolesID', $uRolesID, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                // Row exists but was not deleted (e.g., status was already 'approved')
                echo json_encode(['status' => 'info', 'message' => 'Role assignment was not in a rejectable state (e.g., already approved or status changed). No changes made.']);
            } else {
                // Row does not exist at all
                echo json_encode(['status' => 'error', 'message' => 'Role assignment not found.']);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Reject Role Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to reject role assignment: ' . $e->getMessage()]);
    }
}
// NEW FUNCTION FOR REJECTING ROLE ASSIGNMENT END

?>