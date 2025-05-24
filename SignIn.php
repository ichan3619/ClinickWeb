<?php
session_start();
require 'config/database.php'; 


if (isset($_SESSION['UID'])) { 
    if (isset($_SESSION['roleName']) && $_SESSION['roleName'] === 'Admin') { 
        header('Location: ./Admin/admin.html'); 
        exit;
    }  else {
        header('Location:./patient/patientHome.php'); 
        exit;
    }
}

$errors = [];
$success_message = '';
// define('DEFAULT_PATIENT_ROLE_NAME', 'Patient'); // No longer strictly needed if role is selected
define('MIN_AGE_YEARS', 5); 

// Fetch available departments for the dropdown
$available_departments = [];
try {
    $dept_stmt = $conn->query("SELECT deptID, deptName FROM departments ORDER BY deptName");
    $available_departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("SignIn Department Fetch Error for Dropdown: " . $e->getMessage());
}

// Fetch available roles for the dropdown
$available_roles = [];
try {
    $role_stmt = $conn->query("SELECT roleID, roleName FROM roles ORDER BY roleName");
    $available_roles = $role_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("SignIn Role Fetch Error for Dropdown: " . $e->getMessage());
    // You might want to add an error to $errors[] if roles are critical and failed to load
    // $errors[] = "Could not load roles. Please try again later or contact support.";
}

// NEW: Get the ID for the 'Patient' role
$patientRoleID = null;
$patientRoleNameForDefault = 'Patient'; // Define this clearly
if (empty($errors)) { // Proceed only if roles were fetched
    try {
        $stmt_patient_role_lookup = $conn->prepare("SELECT roleID FROM roles WHERE roleName = ?");
        $stmt_patient_role_lookup->execute([$patientRoleNameForDefault]);
        $patient_role_result = $stmt_patient_role_lookup->fetch(PDO::FETCH_ASSOC);
        if ($patient_role_result) {
            $patientRoleID = $patient_role_result['roleID'];
        } else {
            $errors[] = "Default 'Patient' role definition not found in the system. Please contact an administrator.";
            error_log("CRITICAL: 'Patient' role not found in roles table for default assignment.");
        }
    } catch (PDOException $e) {
        $errors[] = "Database error looking up default patient role. Please contact an administrator.";
        error_log("SignIn Patient Role Lookup Error: " . $e->getMessage());
    }
}




if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? ''); 
    $mname = trim($_POST['mname'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $schoolID = trim($_POST['schoolID'] ?? ''); 
    $contactNum = trim($_POST['contactNum'] ?? ''); 
    $bdate = trim($_POST['bdate'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $baranggay = trim($_POST['baranggay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $deptID = trim($_POST['deptID'] ?? '');
    $selectedRoleID_from_form = trim($_POST['roleID'] ?? ''); // New: Get selected role
    $emergencyPerson = trim($_POST['emergencyPerson'] ?? '');
    $emergencyContact = trim($_POST['emergencyContact'] ?? '');

    // --- Basic Validation ---
    if (empty($email)) { $errors[] = "Email is required."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email format."; }

    if (empty($password)) { $errors[] = "Password is required."; }
    elseif (strlen($password) < 6) { $errors[] = "Password must be at least 6 characters long."; }
    elseif ($password !== $confirm_password) { $errors[] = "Passwords do not match."; }

    if (empty($fname)) $errors[] = "First name is required.";
    
    if (empty($contactNum)) $errors[] = "Contact/Mobile number is required.";
    if (empty($bdate)) {
        $errors[] = "Birth date is required.";
    } else {
        try {
            $birthDateObj = new DateTime($bdate);
            $today = new DateTime();
            $minBirthDate = (new DateTime())->modify('-' . MIN_AGE_YEARS . ' years');
            if ($birthDateObj > $today) {
                $errors[] = "Birth date cannot be in the future.";
            } elseif ($birthDateObj > $minBirthDate) {
                $errors[] = "User must be at least " . MIN_AGE_YEARS . " years old.";
            }
        } catch (Exception $e) {
            $errors[] = "Invalid birth date format.";
        }
    }
    if (empty($sex)) $errors[] = "Sex is required.";
    if (empty($street) || $street === '') $errors[] = "Street is required (cannot be N/A).";
    if (empty($baranggay) || $baranggay === '') $errors[] = "Barangay is required (cannot be N/A).";
    if (empty($city) || $city === '') $errors[] = "City is required (cannot be N/A).";
    if (empty($province) || $province === '') $errors[] = "Province is required (cannot be N/A).";
    
    // New: Validate selected role
    if (empty($selectedRoleID_from_form)) {
        $errors[] = "Role selection is required.";
    } else {
        $stmt_check_role = $conn->prepare("SELECT COUNT(*) FROM roles WHERE roleID = ?");
        $stmt_check_role->execute([$selectedRoleID_from_form]);
        if ($stmt_check_role->fetchColumn() == 0) {
            $errors[] = "Invalid role selected from dropdown. Please choose from the list.";
        }
    }

    // Ensure Patient Role ID was found earlier for default assignment
    if ($patientRoleID === null && !in_array("Default 'Patient' role definition not found in the system. Please contact an administrator.", $errors) && !in_array("Database error looking up default patient role. Please contact an administrator.", $errors) ) {
        // This condition implies an issue not caught by initial error checks for patientRoleID but it should be.
        // However, adding it to errors if it's null and no prior related error exists.
        $errors[] = "System error: Default patient role ID could not be determined. Registration halted.";
    }


    // --- Check if email already exists ---
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT accID FROM userAccounts WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "This email address is already registered.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error checking email. Please try again.";
            error_log("SignIn Email Check Error: " . $e->getMessage());
        }
    }
    
    // --- Get 'Patient' Role ID (this will be their only role on sign-up) --- // This section is now replaced by user selection
    // $defaultRoleID = null; 
    // if (empty($errors)) {
    //     try {
    //         $stmt_role = $conn->prepare("SELECT roleID FROM roles WHERE roleName = ?");
    //         $stmt_role->execute([DEFAULT_PATIENT_ROLE_NAME]);
    //         $role_result = $stmt_role->fetch(PDO::FETCH_ASSOC);
    //         if ($role_result) {
    //             $defaultRoleID = $role_result['roleID'];
    //         } else {
    //             $errors[] = "Default system role '" . DEFAULT_PATIENT_ROLE_NAME . "' not found. Please contact administrator.";
    //             error_log("SignIn Error: Default patient role '" . DEFAULT_PATIENT_ROLE_NAME . "' not found in roles table.");
    //         }
    //     } catch (PDOException $e) {
    //         $errors[] = "Database error fetching system roles. Please try again.";
    //         error_log("SignIn Patient Role Fetch Error: " . $e->getMessage());
    //     }
    // }

    // --- If No Errors, Proceed to Insert Data ---
    if (empty($errors)) { // $defaultRoleID !== null condition removed
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $deptID = empty($deptID) ? NULL : $deptID; 
        $schoolID = empty($schoolID) ? NULL : $schoolID;
        $lname = empty($lname) ? NULL : $lname;
        $mname = empty($mname) ? NULL : $mname;
        $suffix = empty($suffix) ? NULL : $suffix;
        $emergencyPerson = empty($emergencyPerson) ? NULL : $emergencyPerson;
        $emergencyContact = empty($emergencyContact) ? NULL : $emergencyContact;

                try {
            $conn->beginTransaction();

            $sql_acc = "INSERT INTO userAccounts (email, password) VALUES (?, ?)";
            $stmt_acc = $conn->prepare($sql_acc);
            $stmt_acc->execute([$email, $hashed_password]);
            $accID = $conn->lastInsertId();

            $sql_info = "INSERT INTO userInfo (accID, schoolID, fname, mname, lname, suffix, contactNum, bdate, sex, street, baranggay, city, province, deptID, emergencyPerson, emergencyContact) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_info = $conn->prepare($sql_info);
            $stmt_info->execute([
                $accID, $schoolID, $fname, $mname, $lname, $suffix, $contactNum, $bdate, $sex,
                $street, $baranggay, $city, $province, $deptID,
                $emergencyPerson, $emergencyContact
            ]);
            $UID = $conn->lastInsertId();

            // 1. Always add the 'Patient' role as 'approved'
            if (!$patientRoleID) { // Should have been caught by $errors check, but defensive
                 throw new PDOException("Patient Role ID is critically missing before default assignment.");
            }
            $sql_patient_role = "INSERT INTO userRoles (UID, roleID, status) VALUES (?, ?, 'approved')";
            $stmt_patient_role_insert = $conn->prepare($sql_patient_role);
            $stmt_patient_role_insert->execute([$UID, $patientRoleID]);

            // 2. If a different role was selected from dropdown, add it as 'not approved'
            $additional_role_pending = false;
            if (!empty($selectedRoleID_from_form) && $selectedRoleID_from_form != $patientRoleID) {
                $sql_additional_role = "INSERT INTO userRoles (UID, roleID, status) VALUES (?, ?, 'not approved')";
                $stmt_additional_role_insert = $conn->prepare($sql_additional_role);
                $stmt_additional_role_insert->execute([$UID, $selectedRoleID_from_form]);
                $additional_role_pending = true;
            }

            $conn->commit();
            
            if ($additional_role_pending) {
                $success_message = "Registration successful! Your patient access is active. Your request for an additional role is now pending administrator approval.";
            } else {
                $success_message = "Registration successful! Your patient access is active. You can now log in.";
            }
            $_POST = array(); 
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Registration failed due to a database error. Please try again later.";
            error_log("SignIn Registration Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clinic - Sign Up</title>
  <link rel="stylesheet" href="  Stylesheet/Login/SignIn.css"> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
  <div class="signup-container">
    <div class="form-pane"> 
      <h2>Create Account</h2>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (!empty($success_message) && empty($errors)): ?>
          <div class="alert alert-success">
            <p><?php echo htmlspecialchars($success_message); ?> <a href="index.php">Login here</a>.</p>
          </div>
        <?php endif; ?>

        <form method="POST" action="SignIn.php" class="signup-form"> 
            <div class="form-main-columns-wrapper">
                <div class="form-main-column">
                    <fieldset>
                        <legend>Personal Information</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="schoolID">School ID (Optional)</label> 
                                <input type="text" name="schoolID" id="schoolID" value="<?php echo isset($_POST['schoolID']) ? htmlspecialchars($_POST['schoolID']) : ''; ?>" placeholder="Enter School ID">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fname">First Name<span class="required-asterisk">*</span></label>
                                <input type="text" name="fname" id="fname" required value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>" placeholder="Enter First Name">
                            </div>
                            <div class="form-group">
                                <label for="mname">Middle Name</label>
                                <input type="text" name="mname" id="mname" value="<?php echo isset($_POST['mname']) ? htmlspecialchars($_POST['mname']) : ''; ?>" placeholder="Enter Middle Name">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lname">Last Name</label> 
                                <input type="text" name="lname" id="lname" value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>" placeholder="Enter Last Name">
                            </div>
                            <div class="form-group">
                                <label for="suffix">Suffix</label>
                                <input type="text" name="suffix" id="suffix" value="<?php echo isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : ''; ?>" placeholder="e.g. Jr., Sr., III">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contactNum">Mobile Number<span class="required-asterisk">*</span></label>
                                <input type="text" name="contactNum" id="contactNum" required value="<?php echo isset($_POST['contactNum']) ? htmlspecialchars($_POST['contactNum']) : ''; ?>" placeholder="Enter Mobile Number">
                            </div>
                            <div class="form-group">
                                <label for="bdate">Birthdate<span class="required-asterisk">*</span></label>
                                <input type="date" name="bdate" id="bdate" required value="<?php echo isset($_POST['bdate']) ? htmlspecialchars($_POST['bdate']) : ''; ?>">
                            </div>
                        </div>
                         <div class="form-row">
                            <div class="form-group">
                                <label for="sex">Sex<span class="required-asterisk">*</span></label>
                                <select id="sex" name="sex" required>
                                  <option value="">Select Sex</option>
                                  <option value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                  <option value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                  <option value="Intersex" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Intersex') ? 'selected' : ''; ?>>Intersex</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <hr class="form-divider"> 

                    <fieldset>
                        <legend>Address</legend>
                        <p class="form-note address-note">Enter "N/A" if a field is not applicable, but details are preferred.</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="street">Street<span class="required-asterisk">*</span></label>
                                <input type="text" name="street" id="street" required value="<?php echo isset($_POST['street']) ? htmlspecialchars($_POST['street']) : ''; ?>" placeholder="Street Name, Building, House No.">
                            </div>
                            <div class="form-group">
                                <label for="baranggay">Barangay<span class="required-asterisk">*</span></label>
                                <input type="text" name="baranggay" id="baranggay" required value="<?php echo isset($_POST['baranggay']) ? htmlspecialchars($_POST['baranggay']) : ''; ?>" placeholder="Enter Barangay">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City/Municipality<span class="required-asterisk">*</span></label>
                                <input type="text" name="city" id="city" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" placeholder="Enter City/Municipality">
                            </div>
                            <div class="form-group">
                                <label for="province">Province<span class="required-asterisk">*</span></label>
                                <input type="text" name="province" id="province" required value="<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : ''; ?>" placeholder="Enter Province">
                            </div>
                        </div>
                    </fieldset>
                </div> 

                <div class="form-main-column">
                    <fieldset>
                        <legend>Account Credentials</legend>
                        <div class="form-row">
                             <div class="form-group">
                                <label for="email">Email<span class="required-asterisk">*</span></label>
                                <input id="email" name="email" type="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="Enter Email Address">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password<span class="required-asterisk">*</span></label>
                                <div class="input-group">
                                    <input id="password" name="password" type="password" required placeholder="Enter Password">
                                    <span class="password-toggle" onclick="togglePassword('password')"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password<span class="required-asterisk">*</span></label>
                                 <div class="input-group">
                                    <input id="confirm_password" name="confirm_password" type="password" required placeholder="Confirm Password">
                                    <span class="password-toggle" onclick="togglePassword('confirm_password')"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    
                    <hr class="form-divider">

                    <fieldset>
                        <legend>Affiliation Details</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="deptID">Department (Optional)</label>
                                <select id="deptID" name="deptID">
                                <option value="">Select Department</option>
                                <?php foreach ($available_departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['deptID']); ?>"
                                    <?php echo (isset($_POST['deptID']) && $_POST['deptID'] == $dept['deptID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['deptName']); ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="roleID">Role<span class="required-asterisk">*</span></label>
                                <select id="roleID" name="roleID" required>
                                <option value="">Select Role</option>
                                <?php foreach ($available_roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['roleID']); ?>"
                                    <?php echo (isset($_POST['roleID']) && $_POST['roleID'] == $role['roleID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['roleName']); ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <hr class="form-divider"> <fieldset>
                        <legend>Emergency Contact Information</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergencyPerson">Emergency Contact Name</label>
                                <input type="text" name="emergencyPerson" id="emergencyPerson"
                                        value="<?php echo isset($_POST['emergencyPerson']) ? htmlspecialchars($_POST['emergencyPerson']) : ''; ?>"
                                        placeholder="Emergency Contact Name">
                                </div>
                                <div class="form-group">
                                <label for="emergencyContact">Emergency Contact Number</label>
                                <input type="text" name="emergencyContact" id="emergencyContact"
                                        value="<?php echo isset($_POST['emergencyContact']) ? htmlspecialchars($_POST['emergencyContact']) : ''; ?>"
                                        placeholder="Emergency Contact Number">
                            </div>
                        </div>
                    </fieldset>
                </div> 
            </div> 

            <div class="form-group submit-button-group">
                <button type="submit" class="btn-submit">Sign Up</button>
            </div>
        </form>

        <p class="login-link-text">
          Already have an account? <a href="index.php">Login</a>
        </p>
      </div>
  </div>
  <script>
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = field.nextElementSibling.querySelector('i');
      if (field.type === "password") {
        field.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        field.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    const bdateInput = document.getElementById('bdate');
    if (bdateInput) {
        bdateInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const todayDate = new Date();
            const minAgeDate = new Date();
            minAgeDate.setFullYear(todayDate.getFullYear() - <?php echo MIN_AGE_YEARS; ?>);
            
            todayDate.setHours(0,0,0,0);
            birthDate.setHours(0,0,0,0);
            minAgeDate.setHours(0,0,0,0);

            if (birthDate > todayDate) {
                alert('Birth date cannot be in the future.');
                this.value = '';
            } else if (birthDate > minAgeDate) {
                 alert('User must be at least <?php echo MIN_AGE_YEARS; ?> years old.');
                 this.value = '';
            }
        });
    }
  </script>
</body>
</html>