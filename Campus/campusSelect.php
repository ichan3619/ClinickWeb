<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEFAULT PATHS - **PLEASE ADJUST THESE if your files are elsewhere**
$config_path = __DIR__ . '/../config/database.php'; // Common: if this page is in 'Login/' or 'Admin/', and 'config/' is one level up.
$login_page_url = 'index.php'; // Common: if index.php is in the same directory. Adjust to '../Login/index.php' if needed.
$default_dashboard_url = 'dashboard.php'; // A generic fallback if post_campus_select_redirect_url is missing

// If user is not logged in, or the crucial redirect URL is not set (meaning they didn't come from index.php properly),
// send them to the login page.
if (!isset($_SESSION['UID']) || !isset($_SESSION['post_campus_select_redirect_url'])) {
    header('Location: ' . $login_page_url);
    exit;
}

// Connect to the database
require_once $config_path;

// If an active campus IS ALREADY SET in the session (meaning this page's selection process
// just completed successfully from a user click, or it was already set and index.php didn't clear it for some reason),
// then proceed to the originally intended dashboard.
if (isset($_SESSION['activeCampusID']) && !empty($_SESSION['activeCampusID'])) {
    $redirect_to = $_SESSION['post_campus_select_redirect_url'];
    unset($_SESSION['post_campus_select_redirect_url']); // Clean up the session variable
    header('Location: ' . $redirect_to);
    exit;
}

// If we reach here, it means $_SESSION['activeCampusID'] is NOT set,
// and index.php intends for the user to make a selection on this page.

$current_uid = $_SESSION['UID'];
$feedback_message = ''; // For displaying messages to the user

// --- Part 1: Processing Logic if a campus is being selected (user clicked a link) ---
if (isset($_GET['set_active_campus_id'])) {
    $selected_campus_id = filter_var($_GET['set_active_campus_id'], FILTER_VALIDATE_INT);
    $current_page_url_for_redirect = strtok($_SERVER["REQUEST_URI"], '?'); // Base URL of this page for redirect

    if ($selected_campus_id && $selected_campus_id > 0) {
        try {
            $conn->beginTransaction(); // START TRANSACTION

            // Step A: Verify the selected campus ID is valid in the main 'campus' table
            $stmt_verify_campus_exists = $conn->prepare("SELECT campusName FROM campus WHERE campusID = :campus_id");
            $stmt_verify_campus_exists->bindParam(':campus_id', $selected_campus_id, PDO::PARAM_INT);
            $stmt_verify_campus_exists->execute();
            $campus_info_for_session = $stmt_verify_campus_exists->fetch(PDO::FETCH_ASSOC);

            if (!$campus_info_for_session) {
                $feedback_message = "Error: The selected campus is invalid or no longer exists.";
                $conn->rollBack(); // No need to proceed if campus itself is invalid
                header("Location: " . $current_page_url_for_redirect . "?campus_change_msg=" . urlencode($feedback_message));
                exit;
            }

            // Step B: Deactivate any currently active campus for this user in userCampus
            $stmt_deactivate = $conn->prepare("UPDATE userCampus SET isActive = FALSE WHERE UID = :uid AND isActive = TRUE");
            $stmt_deactivate->bindParam(':uid', $current_uid, PDO::PARAM_INT);
            $stmt_deactivate->execute();

            // Step C: Check if the user-campus link already exists in userCampus
            $stmt_check_exists = $conn->prepare("SELECT ucID FROM userCampus WHERE UID = :uid AND campusID = :campus_id");
            $stmt_check_exists->bindParam(':uid', $current_uid, PDO::PARAM_INT);
            $stmt_check_exists->bindParam(':campus_id', $selected_campus_id, PDO::PARAM_INT);
            $stmt_check_exists->execute();
            $existing_uc_entry = $stmt_check_exists->fetch(PDO::FETCH_ASSOC);

            if ($existing_uc_entry) {
                // Entry exists, so update it to active
                $stmt_activate_existing = $conn->prepare("UPDATE userCampus SET isActive = TRUE WHERE ucID = :uc_id");
                $stmt_activate_existing->bindParam(':uc_id', $existing_uc_entry['ucID'], PDO::PARAM_INT);
                $stmt_activate_existing->execute();
            } else {
                // Entry does not exist, so insert a new one as active
                $stmt_insert_active = $conn->prepare("INSERT INTO userCampus (UID, campusID, isActive) VALUES (:uid, :campus_id, TRUE)");
                $stmt_insert_active->bindParam(':uid', $current_uid, PDO::PARAM_INT);
                $stmt_insert_active->bindParam(':campus_id', $selected_campus_id, PDO::PARAM_INT);
                $stmt_insert_active->execute();
            }

            // Set session variables
            $_SESSION['activeCampusID'] = $selected_campus_id;
            $_SESSION['activeCampusName'] = $campus_info_for_session['campusName'];
            $feedback_message = "Active campus set to: " . htmlspecialchars($campus_info_for_session['campusName']);

            $conn->commit(); // COMMIT TRANSACTION

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $feedback_message = "Database error. Please try again."; // Simple message for user
            error_log("Campus Selection Page DB Error (UID: $current_uid, CampusID: $selected_campus_id): " . $e->getMessage()); // Log full error
            unset($_SESSION['activeCampusID']); // Clear on error to maintain consistent state
            unset($_SESSION['activeCampusName']);
        }
        // Redirect to this same page. The check at the top will then redirect to the dashboard.
        header("Location: " . $current_page_url_for_redirect . "?campus_change_msg=" . urlencode($feedback_message));
        exit;
    } else {
        $feedback_message = "Invalid campus selection input.";
        header("Location: " . $current_page_url_for_redirect . "?campus_change_msg=" . urlencode($feedback_message));
        exit;
    }
}

// Display feedback message if redirected from self after update attempt
if (isset($_GET['campus_change_msg'])) {
    // This message is mostly for debugging or brief user feedback before the final redirect.
    $feedback_message = htmlspecialchars(urldecode($_GET['campus_change_msg']));
}

// --- Part 2: Display Logic ---
// Fetch ALL available campuses from the 'campus' table for the user to pick from.
$all_system_campuses = [];
try {
    $stmt_all_campuses = $conn->query("SELECT campusID, campusName FROM campus ORDER BY campusName");
    $all_system_campuses = $stmt_all_campuses->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // "Amateur" error display for critical failure
    echo "<p style='color:red; text-align:center; padding:20px;'>FATAL DATABASE ERROR: Could not fetch list of available campuses. Please contact support. <br><small>" . $e->getMessage() . "</small></p>";
    // You might want to exit here if no campuses can be shown.
    $all_system_campuses = []; // Ensure it's an empty array
}

// For styling the links, what is currently active in the session (should be null at this point if selection is forced)
$page_display_active_campus_id = $_SESSION['activeCampusID'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Active Campus</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 95vh; /* Full viewport height */
            background-color: #f4f7f9; /* Softer background */
            margin: 0;
            padding: 15px; /* Padding for small screens */
            box-sizing: border-box;
        }
        .selection-panel {
            background-color: #ffffff;
            padding: 35px 45px; /* More padding */
            border-radius: 10px; /* More rounded */
            box-shadow: 0 5px 20px rgba(0,0,0,0.1); /* Softer shadow */
            text-align: center;
            width: 100%;
            max-width: 500px; /* Slightly narrower */
        }
        .selection-panel h1 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #2c3e50; /* Darker, modern blue-grey */
            font-size: 1.7em; /* Slightly smaller H1 */
            font-weight: 600;
        }
        .selection-panel p.welcome-text {
            margin-bottom: 25px;
            color: #525f7f; /* Softer text color */
            font-size: 1.05em;
            line-height: 1.6;
        }
        .selection-panel hr.divider { /* Custom class for HR */
            border: 0;
            height: 1px;
            background-color: #e9ecef; /* Lighter divider */
            margin: 30px 0;
        }
        .campus-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column; /* Vertical stack */
            gap: 12px; /* Space between buttons */
            margin-top: 15px;
        }
        .campus-list li {
            margin-bottom: 0;
        }
        .campus-link-button {
            display: block;
            width: 100%; /* Full width buttons */
            padding: 12px 18px;
            border: 1px solid #adb5bd; /* Neutral border */
            text-decoration: none;
            border-radius: 6px; /* More rounded buttons */
            background-color: #f8f9fa; /* Light button background */
            color: #495057; /* Button text color */
            font-weight: 500; /* Medium weight */
            font-size: 1em;
            transition: all 0.2s ease-in-out;
            box-sizing: border-box;
        }
        .campus-link-button:hover {
            background-color: #e9ecef; /* Hover effect */
            border-color: #6c757d;
            color: #343a40;
        }
        .campus-link-button.active { /* Style for the currently selected campus (if page were to re-display it) */
            background-color: #007bff; /* Bootstrap primary blue */
            color: white;
            border-color: #0056b3;
            font-weight: bold;
        }
        .feedback-message {
            padding:12px 15px;
            border:1px solid transparent;
            margin-bottom:20px;
            border-radius: 5px;
            font-size: 0.95em;
        }
        .feedback-message.success { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc;}
        .feedback-message.error { background-color: #f8d7da; color: #842029; border-color: #f5c2c7;}

        .no-campus-message { color: #6c757d; font-style: italic; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="selection-panel">
        <h1>Campus Selection</h1>
        <p class="welcome-text">
            Hello, <?php echo isset($_SESSION['fname']) ? htmlspecialchars($_SESSION['fname']) : 'User'; ?>!<br>
            Please choose the campus you'll be working with for this session.
        </p>

        <?php if (!empty($feedback_message)): ?>
            <p class="feedback-message <?php
                // Simple check for error/success styling based on message content
                if (strpos(strtolower($feedback_message), 'error') !== false || strpos(strtolower($feedback_message), 'invalid') !== false) {
                    echo 'error';
                } else {
                    echo 'success';
                }
            ?>">
                <?php echo $feedback_message; ?>
            </p>
        <?php endif; ?>

        <hr class="divider">

        <?php if (count($all_system_campuses) > 0): ?>
            <ul class="campus-list">
                <?php foreach ($all_system_campuses as $campus_item): ?>
                    <li>
                        <a href="?set_active_campus_id=<?php echo htmlspecialchars($campus_item['campusID']); ?>"
                           class="campus-link-button <?php echo ($page_display_active_campus_id == $campus_item['campusID']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($campus_item['campusName']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-campus-message">
                <em>No campuses are currently available in the system. Please contact an administrator.</em>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>