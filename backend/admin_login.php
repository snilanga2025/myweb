<?php
// admin_login.php
// session_start(); // session_start() is now in security_utils.php, which will be required.
require_once "security_utils.php"; // Includes session_start()
require_once "db_connect.php"; 

// If admin is already logged in, redirect to dashboard
if(isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true){
    header("location: admin_dashboard.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $login_err = "CSRF token validation failed. Please try again.";
    } else {
        // Regenerate token after successful validation to prevent reuse for this specific form submission
        generate_csrf_token(true); 

        // Check if username is empty
        if(empty(trim($_POST["username"]))){
            $username_err = "Please enter username.";
        } else{
            $username = trim($_POST["username"]);
        }
        
        // Check if password is empty
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter your password.";
        } else{
            $password = trim($_POST["password"]);
        }
        
        // Validate credentials only if CSRF is okay and no other errors yet
        if(empty($username_err) && empty($password_err) && empty($login_err)){
            $sql = "SELECT id, username, password FROM admins WHERE username = ?";
            
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("s", $param_username);
                $param_username = $username;
                
                if($stmt->execute()){
                    $stmt->store_result();
                    
                    if($stmt->num_rows == 1){                    
                        $stmt->bind_result($id, $db_username, $hashed_password);
                        if($stmt->fetch()){
                            if(password_verify($password, $hashed_password)){
                                // Password is correct, so start a new session
                                // session_start(); // Already started
                                
                                // Store data in session variables
                                $_SESSION["admin_loggedin"] = true;
                                $_SESSION["admin_id"] = $id;
                                $_SESSION["admin_username"] = $db_username;                            
                                
                                // Regenerate CSRF token upon successful login for the new session state
                                generate_csrf_token(true);

                                header("location: admin_dashboard.php");
                                exit; 
                            } else{
                                $login_err = "Invalid username or password.";
                            }
                        }
                    } else{
                        $login_err = "Invalid username or password.";
                    }
                } else{
                    $login_err = "Oops! Something went wrong. Please try again later.";
                }
                $stmt->close();
            } else {
                $login_err = "Database query preparation error. Please try again later.";
            }
        }
    } // End CSRF validation block
    
    if (!empty($login_err) || !empty($username_err) || !empty($password_err)) {
         // Ensure a new token is available if the form is re-displayed due to errors
        generate_csrf_token(true); 
    }
    $conn->close();
} else {
    // Generate a token when the form is initially displayed (GET request)
    generate_csrf_token(true); // Force new token on initial load for login page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/main_style.css">
    <!-- Inline styles are kept for brevity, assuming main_style.css might not cover everything or for overrides -->
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; }
        .login-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        .login-container h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; }
        .form-group input[type="text"], .form-group input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group .error { color: red; font-size: 0.9em; }
        .form-group input[type="submit"] { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .form-group input[type="submit"]:hover { background-color: #0056b3; }
        .login-err { color: red; text-align: center; margin-bottom:15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php 
        if(!empty($login_err)){
            echo '<div class="login-err">' . htmlspecialchars($login_err) . '</div>';
        }        
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <?php csrf_input_field(); ?> {/* Call the function to output CSRF hidden input */}
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
                <span class="error"><?php echo htmlspecialchars($username_err); ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="error"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            <div class="form-group">
                <input type="submit" value="Login">
            </div>
        </form>
    </div>
</body>
</html>
