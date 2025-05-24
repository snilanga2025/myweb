<?php
// session_start(); // REMOVE THIS
require_once "../backend/security_utils.php"; // ADD THIS, note path
require_once "../backend/db_connect.php"; // Path for this was already correct

// Variables initialization (remains the same)
$first_name = $last_name = $email = $password = $confirm_password = "";
$first_name_err = $last_name_err = $email_err = $password_err = $confirm_password_err = $profile_image_err = $general_err = $success_msg = "";

// UPLOAD_DIR_REGISTER definition (remains the same)
define("UPLOAD_DIR_REGISTER", "../uploads/");
if (!is_dir(UPLOAD_DIR_REGISTER)) {
    mkdir(UPLOAD_DIR_REGISTER, 0755, true);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // 1. Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $general_err = "CSRF token validation failed. Please try submitting the form again.";
    } else {
        generate_csrf_token(true); // Regenerate token after successful validation.

        // Validate first name
        if(empty(trim($_POST["first_name"]))){ $first_name_err = "Please enter your first name."; } 
        else { $first_name = trim($_POST["first_name"]); }

        // Validate last name
        if(empty(trim($_POST["last_name"]))){ $last_name_err = "Please enter your last name."; } 
        else { $last_name = trim($_POST["last_name"]); }

        // Validate email
        if(empty(trim($_POST["email"]))){ 
            $email_err = "Please enter your email address."; 
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
            $email_err = "Invalid email format.";
        } else {
            $email_check = trim($_POST["email"]);
            $sql_check_email = "SELECT id FROM students WHERE email = ?";
            if($stmt_check = $conn->prepare($sql_check_email)){
                $stmt_check->bind_param("s", $param_email_check);
                $param_email_check = $email_check;
                if($stmt_check->execute()){
                    $stmt_check->store_result();
                    if($stmt_check->num_rows > 0){
                        $email_err = "This email is already registered.";
                    } else {
                        $email = $email_check;
                    }
                } else { $general_err = "Error checking email. Please try again."; }
                $stmt_check->close();
            } else { $general_err = "Database error (email check). Please try again."; }
        }

        // Validate password
        if(empty(trim($_POST["password"]))){ $password_err = "Please create a password."; }
        elseif(strlen(trim($_POST["password"])) < 6){ $password_err = "Password must have at least 6 characters."; }
        else { $password = trim($_POST["password"]); }

        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){ $confirm_password_err = "Please confirm your password."; }
        else {
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($password_err) && ($password != $confirm_password)){
                $confirm_password_err = "Passwords did not match.";
            }
        }

        // Handle profile image upload
        $new_profile_image_path_db = NULL; 
        if(isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0){
            $allowed_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
            $file_name = $_FILES["profile_image"]["name"];
            $file_type = $_FILES["profile_image"]["type"];
            $file_size = $_FILES["profile_image"]["size"];
            $file_tmp_name = $_FILES["profile_image"]["tmp_name"];
            
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if(!array_key_exists($ext, $allowed_types) || !in_array($file_type, $allowed_types)){
                $profile_image_err = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
            } elseif ($file_size > 2097152) { 
                $profile_image_err = "File size exceeds 2MB limit.";
            } else {
                $unique_file_name = uniqid("student_reg_", true) . "." . $ext; 
                $destination = UPLOAD_DIR_REGISTER . $unique_file_name;
                
                if(move_uploaded_file($file_tmp_name, $destination)){
                    $new_profile_image_path_db = "uploads/" . $unique_file_name; 
                } else {
                    $profile_image_err = "Failed to move uploaded file.";
                }
            }
        } elseif (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] != UPLOAD_ERR_NO_FILE && $_FILES["profile_image"]["error"] != 0) {
             $profile_image_err = "Error uploading file. Code: " . $_FILES["profile_image"]["error"];
        }

        // Check input errors before inserting in database
        if(empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($profile_image_err) && empty($general_err)){
            
            $sql = "INSERT INTO students (first_name, last_name, email, password, profile_image_path) VALUES (?, ?, ?, ?, ?)";
             
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("sssss", $param_first_name, $param_last_name, $param_email, $param_password, $param_image_path);
                
                $param_first_name = $first_name;
                $param_last_name = $last_name;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_image_path = $new_profile_image_path_db;
                
                if($stmt->execute()){
                    $success_msg = "Registration successful! You can now be managed by an admin.";
                    $first_name = $last_name = $email = ""; 
                } else{
                    $general_err = "Something went wrong with registration. Please try again later. (DB Execute Error)";
                }
                $stmt->close();
            } else {
                 $general_err = "Database prepare error. Please try again later.";
            }
        }
    } // End CSRF validation block

    // If form is re-displayed due to errors (CSRF or other), ensure a new token.
    if (!empty($general_err) || !empty($first_name_err) || !empty($last_name_err) || !empty($email_err) || !empty($password_err) || !empty($confirm_password_err) || !empty($profile_image_err)) {
        generate_csrf_token(true);
    }
    
} else {
    // This is a GET request, generate a token for the form.
    generate_csrf_token(true);
}

// Close connection if it was opened (i.e., during a POST request)
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <link rel="stylesheet" href="../assets/main_style.css"> 
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px 0; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .container h1 { text-align: center; margin-bottom: 25px; color: #007bff; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: bold; color: #495057; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; font-size: 1em;
        }
        .form-group input[type="file"] { padding: 5px; }
        .form-group .error { color: #dc3545; font-size: 0.85em; margin-top: 6px; }
        .form-group input[type="submit"] { 
            width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em; transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover { background-color: #218838; }
        .general-error { color: #dc3545; text-align: center; margin-bottom: 18px; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px;}
        .success-message { color: #155724; text-align: center; margin-bottom: 18px; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px;}
        .form-note { font-size: 0.85em; color: #6c757d; margin-top: 6px; }
        .login-link { text-align: center; margin-top: 20px; }
        .login-link a { color: #007bff; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Student Registration</h1>

        <?php 
        if(!empty($general_err)){ echo '<p class="general-error">' . htmlspecialchars($general_err) . '</p>'; }
        if(!empty($success_msg)){ echo '<p class="success-message">' . htmlspecialchars($success_msg) . '</p>'; }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <?php csrf_input_field(); ?>
            
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                <span class="error"><?php echo htmlspecialchars($first_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                <span class="error"><?php echo htmlspecialchars($last_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <span class="error"><?php echo htmlspecialchars($email_err); ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" required>
                <p class="form-note">Password must be at least 6 characters long.</p>
                <span class="error"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <span class="error"><?php echo htmlspecialchars($confirm_password_err); ?></span>
            </div>
            <div class="form-group">
                <label>Profile Image (Optional)</label>
                <input type="file" name="profile_image" id="profile_image_input_register" accept="image/png, image/jpeg, image/gif">
                <div id="image_preview_container_register"></div>
                <p class="form-note">Max file size: 2MB. Allowed types: JPG, JPEG, PNG, GIF.</p>
                <span class="error"><?php echo htmlspecialchars($profile_image_err); ?></span>
            </div>
            <div class="form-group">
                <input type="submit" value="Register">
            </div>
        </form>
        <div class="login-link">
            <p>Admin Login: <a href="../backend/admin_login.php">Click here</a></p>
        </div>
    </div>
    <script src="../assets/form_enhancements.js"></script>
</body>
</html>
