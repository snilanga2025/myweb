<?php
// session_start(); // REMOVE THIS
require_once "security_utils.php"; // ADD THIS (handles session_start)
require_once "db_connect.php";

// Check if the admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

$student_id = $first_name = $last_name = $email = $profile_image_path = "";
$first_name_err = $last_name_err = $email_err = $password_err = $profile_image_err = $general_err = $success_msg = "";

// Check if student ID is provided in URL
if(!isset($_GET["id"]) || empty(trim($_GET["id"])) || !ctype_digit($_GET["id"])){
    $_SESSION['error_message'] = "Invalid student ID specified.";
    header("location: view_students.php");
    exit;
}
$student_id_from_get = trim($_GET["id"]); // Use a distinct variable for GET ID initially

// Define upload directory relative to this script's location
define("UPLOAD_DIR", "../uploads/"); 
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true); // Create if it doesn't exist
}


// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // 1. Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $general_err = "CSRF token validation failed. Please try submitting the form again.";
    } else {
        generate_csrf_token(true); // Regenerate token after successful validation for this POST.

        // Ensure student_id from POST matches GET param to prevent manipulation via hidden field
        if(empty($_POST["student_id"]) || $_POST["student_id"] != $student_id_from_get){ // Compare with ID from GET
            $general_err = "Student ID mismatch. Please try again.";
        } else {
            $student_id = $_POST["student_id"]; // Now safe to use student_id from POST

            $first_name = trim($_POST["first_name"]);
            $last_name = trim($_POST["last_name"]);
            $email_check_post = trim($_POST["email"]); // Use a different var name for POSTed email

            // Validate first name
            if(empty($first_name)){ $first_name_err = "Please enter first name."; }
            // Validate last name
            if(empty($last_name)){ $last_name_err = "Please enter last name."; }

            // Validate email
            if(empty($email_check_post)){
                $email_err = "Please enter email.";
            } elseif(!filter_var($email_check_post, FILTER_VALIDATE_EMAIL)){
                $email_err = "Invalid email format.";
            } else {
                $sql_check_email = "SELECT id FROM students WHERE email = ? AND id != ?";
                if($stmt_check = $conn->prepare($sql_check_email)){
                    $stmt_check->bind_param("si", $param_email_check, $param_id_check);
                    $param_email_check = $email_check_post; 
                    $param_id_check = $student_id;
                    if($stmt_check->execute()){
                        $stmt_check->store_result();
                        if($stmt_check->num_rows > 0){ $email_err = "This email is already registered by another student."; } 
                        else { $email = $email_check_post; } // Assign to $email only if valid and not taken
                    } else { $general_err = "Error checking email."; }
                    $stmt_check->close();
                } else { $general_err = "Database error (email check)."; }
            }

            // Optional: Validate and update password
            $password_val = ""; // Initialize to avoid issues if not set
            if(!empty(trim($_POST["password"]))){
                if(strlen(trim($_POST["password"])) < 6){
                    $password_err = "Password must have at least 6 characters.";
                } else{
                    $password_val = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
                }
            }

            // Handle file upload
            $new_profile_image_path_db = ""; // Path to store in DB for this update
            if(isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0){
                $allowed_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
                $file_name = $_FILES["profile_image"]["name"];
                $file_type = $_FILES["profile_image"]["type"];
                $file_size = $_FILES["profile_image"]["size"];
                $file_tmp_name = $_FILES["profile_image"]["tmp_name"];
                
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if(!array_key_exists($ext, $allowed_types) || !in_array($file_type, $allowed_types)){
                    $profile_image_err = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
                } elseif ($file_size > 2097152) { // 2MB limit
                    $profile_image_err = "File size exceeds 2MB limit.";
                } else {
                    $unique_file_name = uniqid("student_".$student_id."_", true) . "." . $ext;
                    $destination = UPLOAD_DIR . $unique_file_name;
                    
                    if(move_uploaded_file($file_tmp_name, $destination)){
                        $new_profile_image_path_db = "uploads/" . $unique_file_name; 
                    } else {
                        $profile_image_err = "Failed to move uploaded file.";
                    }
                }
            } elseif (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] != UPLOAD_ERR_NO_FILE && $_FILES["profile_image"]["error"] != 0) {
                 $profile_image_err = "Error uploading file. Code: " . $_FILES["profile_image"]["error"];
            }


            // Check input errors before updating database
            if(empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($profile_image_err) && empty($general_err)){
                
                $sql_update_parts = ["first_name = ?", "last_name = ?", "email = ?"];
                $params = [$first_name, $last_name, $email]; // $email is now the validated one from POST
                $types = "sss";

                if(!empty($password_val)){ // If a new password was provided and validated
                    $sql_update_parts[] = "password = ?";
                    $params[] = $password_val;
                    $types .= "s";
                }
                if(!empty($new_profile_image_path_db)){ // If a new image was successfully uploaded
                    $sql_update_parts[] = "profile_image_path = ?";
                    $params[] = $new_profile_image_path_db;
                    $types .= "s";
                }
                
                $params[] = $student_id; // ID for WHERE clause
                $types .= "i";

                $sql = "UPDATE students SET " . implode(", ", $sql_update_parts) . " WHERE id = ?";
                            
                if($stmt = $conn->prepare($sql)){
                    // Dynamically bind parameters
                    $stmt->bind_param($types, ...$params);
                    
                    if($stmt->execute()){
                        $success_msg = "Student profile updated successfully!";
                        // If a new image was uploaded, update $profile_image_path for immediate display
                        if(!empty($new_profile_image_path_db)) {
                            $profile_image_path = $new_profile_image_path_db;
                        }
                    } else{
                        $general_err = "Something went wrong updating the profile. SQL Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                     $general_err = "Database prepare error (update). SQL Error: " . $conn->error;
                }
            }
        }
    } // End CSRF validation block

    // If form is re-displayed due to errors (CSRF or other validation errors), ensure a new token is available.
    if (!empty($general_err) || !empty($first_name_err) || !empty($last_name_err) || !empty($email_err) || !empty($password_err) || !empty($profile_image_err)) {
        generate_csrf_token(true); // Regenerate if any error caused form redisplay
    }
} else {
    // This is a GET request, so generate a token for the form.
    generate_csrf_token(true);
}

// Fetch current student data
// Use $student_id_from_get for fetching initially on GET.
// If it's a POST that failed or succeeded, $student_id would be set from the POST data if it was valid.
// If POST failed due to ID mismatch, $student_id is not set, so $student_id_from_get is primary for fetching.
$current_student_id_for_fetch = $student_id_from_get; // Always fetch based on GET ID to populate form initially or show correct context

// If it was a POST and there was no success_msg (meaning likely an error, or data needs refresh)
// and $student_id IS set (meaning ID from post was valid), we might want to ensure data is fresh.
// However, the logic below already handles repopulating form fields from $db_ variables or keeping POSTed values on error.

$sql_fetch = "SELECT first_name, last_name, email, profile_image_path FROM students WHERE id = ?";
if($stmt_fetch = $conn->prepare($sql_fetch)){
    $stmt_fetch->bind_param("i", $current_student_id_for_fetch);
    if($stmt_fetch->execute()){
        $stmt_fetch->store_result();
        if($stmt_fetch->num_rows == 1){
            $stmt_fetch->bind_result($db_first_name, $db_last_name, $db_email, $db_profile_image_path);
            if($stmt_fetch->fetch()){
                // On initial GET, or if success message is set (meaning update was good, refresh data)
                if($_SERVER["REQUEST_METHOD"] != "POST" || !empty($success_msg)){
                     $first_name = $db_first_name;
                     $last_name = $db_last_name;
                     $email = $db_email; // Display DB email
                     $profile_image_path = $db_profile_image_path;
                } else {
                    // On POST with errors, retain user's valid inputs, otherwise use DB data.
                    // $first_name, $last_name are already from POST.
                    // $email from POST ($email_check_post) is assigned to $email if it passed validation.
                    // If $email is empty (due to POST validation error for email), then show $db_email.
                    if(empty($email) && isset($email_check_post) && !empty($email_err)) $email = $email_check_post; // Show erroneous POSTed email
                    elseif(empty($email)) $email = $db_email; // Default to DB if POSTed was invalid/empty

                    $profile_image_path = $db_profile_image_path; // Always show current stored image unless successfully updated
                }
            }
        } else {
            $_SESSION['error_message'] = "Student not found (ID: " . htmlspecialchars($current_student_id_for_fetch) . ").";
            header("location: view_students.php");
            exit;
        }
    } else {
        $general_err = "Error fetching student data. SQL Error: " . $stmt_fetch->error;
        if($_SERVER["REQUEST_METHOD"] == "GET") generate_csrf_token(true); // Ensure token if error on GET
    }
    $stmt_fetch->close();
} else {
    $general_err = "Database error (fetch prepare). SQL Error: " . $conn->error;
    if($_SERVER["REQUEST_METHOD"] == "GET") generate_csrf_token(true); // Ensure token if error on GET
}

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - Admin</title>
    <link rel="stylesheet" href="../assets/main_style.css">
    <style>
        /* (Existing CSS from edit_student.php or main_style.css) */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
        .dashboard-header { background-color: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-header .logo a { color: white; text-decoration: none; font-size: 1.5em; font-weight: bold;}
        .dashboard-header nav a { color: white; text-decoration: none; margin-left: 20px; }
        .dashboard-header nav a:hover { text-decoration: underline; }

        .container { padding: 20px; max-width: 700px; margin: 20px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .container h1 { color: #333; margin-bottom: 20px; text-align: center; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group input[type="file"] { padding: 3px; }
        .form-group .error { color: red; font-size: 0.9em; margin-top: 5px; }
        .form-group input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #ffc107; 
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover { background-color: #e0a800; }
        .general-error { color: red; text-align: center; margin-bottom: 15px; }
        .success-message { color: green; text-align: center; margin-bottom: 15px; }
        .form-note { font-size: 0.9em; color: #666; margin-top: 5px; }
        .profile-image-current { margin-bottom: 10px; }
        .profile-image-current img { max-width: 150px; max-height: 150px; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="logo"><a href="admin_dashboard.php">Admin Panel</a></div>
        <nav> <a href="admin_dashboard.php">Dashboard</a> <a href="view_students.php">View Students</a> <a href="create_student.php">Add Student</a> <a href="admin_logout.php">Logout</a> </nav>
    </div>

    <div class="container">
        <h1>Edit Student Profile (ID: <?php echo htmlspecialchars($student_id_from_get); ?>)</h1>

        <?php 
        if(!empty($general_err)){ echo '<p class="general-error">' . htmlspecialchars($general_err) . '</p>'; }
        if(!empty($success_msg)){ echo '<p class="success-message">' . htmlspecialchars($success_msg) . '</p>'; }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo htmlspecialchars($student_id_from_get); ?>" method="post" enctype="multipart/form-data">
            <?php csrf_input_field(); ?>
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id_from_get); ?>">
            
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                <span class="error"><?php echo htmlspecialchars($first_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                <span class="error"><?php echo htmlspecialchars($last_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="error"><?php echo htmlspecialchars($email_err); ?></span>
            </div>
            <div class="form-group">
                <label>New Password (optional)</label>
                <input type="password" name="password" id="password">
                <p class="form-note">Leave blank if you don't want to change the password.</p>
                <span class="error"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            
            <div class="form-group">
                <label>Profile Image</label>
                <?php if(!empty($profile_image_path)): ?>
                    <div class="profile-image-current">
                        <p>Current Image:</p>
                        <img src="../<?php echo htmlspecialchars($profile_image_path); ?>" alt="Current Profile Image">
                    </div>
                <?php else: ?>
                    <p class="form-note">No profile image uploaded yet.</p>
                <?php endif; ?>
                <input type="file" name="profile_image" id="profile_image_input_edit" accept="image/png, image/jpeg, image/gif">
                <div id="image_preview_container_edit"></div>
                <p class="form-note">Max file size: 2MB. Allowed types: JPG, JPEG, PNG, GIF.</p>
                <span class="error"><?php echo htmlspecialchars($profile_image_err); ?></span>
            </div>

            <div class="form-group">
                <input type="submit" value="Update Profile">
            </div>
        </form>
        <p style="text-align:center; margin-top:15px;"><a href="view_students.php">Back to Student List</a></p>
    </div>
    <script src="../assets/form_enhancements.js"></script>
</body>
</html>
