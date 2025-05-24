<?php
session_start();

require 'config/database.php'; 


if (isset($_SESSION['UID']) && isset($_SESSION['roleName'])) { // Added isset for roleName for robustness
    // ... (your existing switch case for redirection if already logged in) ...
    // This part is crucial for users who already have an active session and role
    // and try to access index.php   again.
    // We assume this existing logic is fine.
    // The new flow applies to a fresh login attempt.
    switch ($_SESSION['roleName']) {
        case 'Admin':
            // If admin is logged in and has active campus, go to admin page,
            // otherwise, they might also need to be routed to select_campus_page.php
            // For now, let's assume if session is fully set, they bypass.
            // A more robust check would be if activeCampusID is also set.
            if(isset($_SESSION['activeCampusID']) || !isset($_SESSION['roleName']) || $_SESSION['roleName'] == 'Patient'){ // Patients don't need active campus in this flow
                 header('Location: Admin/admin.html'); // Or their specific dashboard
            } else {
                 header('Location: campus/campusSelect.php'); // Force campus selection if admin logs back in and activeCampusID is lost
            }
            exit;
        case 'Doctor':
            if(isset($_SESSION['activeCampusID']) || !isset($_SESSION['roleName']) || $_SESSION['roleName'] == 'Patient'){
                header('Location: Doctor/docDashboard.php');
            } else {
                header('Location: campus/campusSelect.php');
            }
            exit;
        case 'Nurse':
             if(isset($_SESSION['activeCampusID']) || !isset($_SESSION['roleName']) || $_SESSION['roleName'] == 'Patient'){
                header('Location:  Nurse/viewNurse.php');
            } else {
                header('Location:  campus/campusSelect.php');
            }
            exit;
        case 'Patient':
        default:
            header('Location:  Patient/patientHome.php');
            exit;
    }
}


$error = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required!";
    } else {
        try {
            $stmt = $conn->prepare("SELECT accID, email, password FROM userAccounts WHERE email = ?");
            $stmt->execute([$email]);
            $userAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userAccount && password_verify($password, $userAccount['password'])) {
                $stmt_user_details = $conn->prepare("
                    SELECT ui.UID, ui.fname, ui.lname, ua.accID AS user_accID -- Fetched accID as user_accID to avoid conflict if needed elsewhere
                    FROM userInfo ui
                    JOIN userAccounts ua ON ui.accID = ua.accID
                    WHERE ua.accID = ? 
                    GROUP BY ui.UID, ui.fname, ui.lname, ua.accID
                "); // Removed campusID from here as it's no longer in userInfo for this context.
                   // Also simplified role fetching, will get approved roles next.
                $stmt_user_details->execute([$userAccount['accID']]);
                $userDetails = $stmt_user_details->fetch(PDO::FETCH_ASSOC);

                if ($userDetails) {
                    $_SESSION['accID'] = $userAccount['accID']; // Use accID from userAccounts
                    $_SESSION['UID'] = $userDetails['UID']; 
                    $_SESSION['email'] = $userAccount['email'];
                    $_SESSION['fname'] = $userDetails['fname'];
                    $_SESSION['lname'] = $userDetails['lname'];
                    
                    // Fetch *approved* roles separately for clarity
                    $stmt_roles = $conn->prepare("
                        SELECT GROUP_CONCAT(r.roleName SEPARATOR ',') AS roleNames
                        FROM userRoles ur
                        JOIN roles r ON ur.roleID = r.roleID
                        WHERE ur.UID = ? AND ur.status = 'approved'
                    ");
                    $stmt_roles->execute([$userDetails['UID']]);
                    $roles_data = $stmt_roles->fetch(PDO::FETCH_ASSOC);
                    $approved_roles_string = $roles_data ? $roles_data['roleNames'] : '';

                    $_SESSION['roleNames_str'] = $approved_roles_string; 
                    $approved_roles_array = [];
                    if (!empty($approved_roles_string)) {
                        $approved_roles_array = explode(',', $approved_roles_string);
                    }
                    $_SESSION['roles_array'] = $approved_roles_array;

                    $role_for_session = null;
                    $target_dashboard_url = null;
                    $needs_campus_selection_page = false;

                    // Determine role and target dashboard (prioritized)
                    if (in_array('Admin', $approved_roles_array)) {
                        $role_for_session = 'Admin';
                        $target_dashboard_url = ' Admin/admin.html'; // Using the wrapper page
                        $needs_campus_selection_page = true;
                    } elseif (in_array('Doctor', $approved_roles_array)) {
                        $role_for_session = 'Doctor';
                        $target_dashboard_url = ' Doctor/docDashboard.php'; // Assuming this exists
                        $needs_campus_selection_page = true;
                    } elseif (in_array('Nurse', $approved_roles_array)) {
                        $role_for_session = 'Nurse';
                        $target_dashboard_url = ' Nurse/viewNurse.php'; // Your specified path
                        $needs_campus_selection_page = true;
                    } elseif (in_array('Patient', $approved_roles_array)) {
                        $role_for_session = 'Patient';
                        $target_dashboard_url = ' Patient/patientHome.php';
                        $needs_campus_selection_page = false; // Patients bypass this
                    }

                    if ($role_for_session) {
                        $_SESSION['roleName'] = $role_for_session;

                        // Unset active campus from any previous session to force selection for non-patients
                        if ($needs_campus_selection_page) {
                            unset($_SESSION['activeCampusID']);
                            unset($_SESSION['activeCampusName']);
                        }

                        if ($needs_campus_selection_page) {
                            $_SESSION['post_campus_select_redirect_url'] = $target_dashboard_url;
                            header('Location:  campus/campusSelect.php'); // Redirect to the new intermediate page
                            exit;
                        } else {
                            // For Patients (or any role not needing campus selection)
                            header('Location: ' . $target_dashboard_url);
                            exit;
                        }
                    } else {
                        $error = "Login successful, but no recognized and approved role is assigned to your account.";
                    }
                } else {
                    $error = "User profile details not found. Please contact support."; 
                }
            } else {
                $error = "Invalid email or password!"; 
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage()); 
            $error = "An error occurred. Please try again."; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clinic-Login</title> 
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b2a53] min-h-screen flex items-center justify-center">
  <div class="flex w-full max-w-3xl bg-white rounded shadow overflow-hidden">
    <div class="w-1/2 bg-[#faf6eb] hidden md:flex items-center justify-center p-10 border-r border-gray-200">
      <div class="flex flex-col items-center">
        <img src=" Images/Logo.ko.png" alt="Clinic Logo"/> 
      </div>
    </div>

    <div class="w-full md:w-1/2 bg-white p-10 flex flex-col justify-center">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">Welcome!</h1>

      <?php if (!empty($error)): ?> 
        <p class="text-red-600 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="POST" action="index.php  " class="space-y-4"> 
        <input
          type="email"
          name="email"
          placeholder="Email"
          required
          value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
          class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-[#0b2a53]"
        />
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-[#0b2a53]"
        />
        <button
          type="submit"
          class="w-full bg-[#0b2a53] text-white py-2 rounded hover:bg-[#0a2346] transition"
        >
          Login
        </button>
      </form>

      <p class="text-sm mt-4 text-gray-700">
        Don’t have an account?
        <a href="SignIn.php" class="text-blue-700 underline">Sign up</a> 
      </p>
    </div>
  </div>
</body>
</html>