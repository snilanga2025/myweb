<?php
// session_start(); // REMOVE THIS LINE
require_once "security_utils.php"; // ADD THIS LINE (handles session_start)
require_once "db_connect.php";

// Check if the admin is logged in, otherwise redirect to login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    // Before redirecting, ensure a fresh token if login page is also protected
    // generate_csrf_token(true); // Optional: if login page expects it fresh
    header("location: admin_login.php");
    exit;
}

$first_name = $last_name = $email = $password = "";
$first_name_err = $last_name_err = $email_err = $password_err = $general_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // 1. Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $general_err = "CSRF token validation failed. Please try submitting the form again.";
    } else {
        // Regenerate token after successful validation for this POST request.
        generate_csrf_token(true);

        // Validate first name
        if(empty(trim($_POST["first_name"]))){
            $first_name_err = "Please enter first name.";
        } else{
            $first_name = trim($_POST["first_name"]);
        }

        // Validate last name
        if(empty(trim($_POST["last_name"]))){
            $last_name_err = "Please enter last name.";
        } else{
            $last_name = trim($_POST["last_name"]);
        }

        // Validate email
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter email.";
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
            $email_err = "Invalid email format.";
        } else{
            // Check if email already exists
            $sql_check_email = "SELECT id FROM students WHERE email = ?";
            if($stmt_check = $conn->prepare($sql_check_email)){
                $stmt_check->bind_param("s", $param_email_check);
                $param_email_check = trim($_POST["email"]);
                if($stmt_check->execute()){
                    $stmt_check->store_result();
                    if($stmt_check->num_rows > 0){
                        $email_err = "This email is already registered.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    $general_err = "Error checking email. Please try again.";
                }
                $stmt_check->close();
            } else {
                $general_err = "Database error (email check). Please try again.";
            }
        }

        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }

        // Check input errors before inserting in database (and if CSRF was ok)
        if(empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($general_err)){
            
            $sql = "INSERT INTO students (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
             
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("ssss", $param_first_name, $param_last_name, $param_email, $param_password);
                
                $param_first_name = $first_name;
                $param_last_name = $last_name;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                
                if($stmt->execute()){
                    $_SESSION['success_message'] = "Student registered successfully!";
                    // Regenerate token before redirecting if the target page uses it.
                    // generate_csrf_token(true); // view_students.php doesn't have forms needing this immediately.
                    header("location: view_students.php"); 
                    exit();
                } else{
                    $general_err = "Something went wrong. Please try again later.";
                }
                $stmt->close();
            } else {
                 $general_err = "Database prepare error. Please try again later.";
            }
        }
    } // End CSRF validation block

    // If form is re-displayed due to errors (CSRF or other validation errors), ensure a new token is available.
    if (!empty($general_err) || !empty($first_name_err) || !empty($last_name_err) || !empty($email_err) || !empty($password_err)) {
        generate_csrf_token(true);
    }
    $conn->close(); // This was outside the POST block before, ensure it's correctly placed if used by GET too.
                   // For this script, $conn is only used in POST.
} else {
    // Generate a token when the form is initially displayed (GET request)
    generate_csrf_token(true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Student - Admin</title>
    <link rel="stylesheet" href="../assets/main_style.css">
    <!-- Inline styles from previous version are assumed to be here or in main_style.css -->
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
        .dashboard-header { background-color: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-header .logo a { color: white; text-decoration: none; font-size: 1.5em; font-weight: bold;}
        .dashboard-header nav a { color: white; text-decoration: none; margin-left: 20px; }
        .dashboard-header nav a:hover { text-decoration: underline; }

        .container { padding: 20px; max-width: 600px; margin: 20px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .container h1 { color: #333; margin-bottom: 20px; text-align: center; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group .error { color: red; font-size: 0.9em; margin-top: 5px; }
        .form-group input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #28a745; /* Green for create */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover { background-color: #218838; }
        .general-error { color: red; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <div class="logo"><a href="admin_dashboard.php">Admin Panel</a></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="view_students.php">View Students</a>
            <a href="create_student.php">Add Student</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <h1>Add New Student (Manual)</h1>

        <?php 
        if(!empty($general_err)){
            echo '<p class="general-error">' . htmlspecialchars($general_err) . '</p>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <?php csrf_input_field(); ?> {/* ADD THIS LINE */}
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                <span class="error"><?php echo htmlspecialchars($first_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                <span class="error"><?php echo htmlspecialchars($last_name_err); ?></span>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="error"><?php echo htmlspecialchars($email_err); ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="error"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            <div class="form-group">
                <input type="submit" value="Add Student">
            </div>
        </form>
        <p style="text-align:center; margin-top:15px;"><a href="view_students.php">Back to Student List</a></p>
    </div>

</body>
</html>
