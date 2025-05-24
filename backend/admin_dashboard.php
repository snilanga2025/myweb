<?php
session_start();

// Check if the admin is logged in, otherwise redirect to login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/main_style.css"> <!-- Link to a future CSS file -->
     <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
        .dashboard-header { background-color: #007bff; color: white; padding: 15px 20px; text-align: right; }
        .dashboard-header a { color: white; text-decoration: none; margin-left: 15px; }
        .dashboard-header a:hover { text-decoration: underline; }
        .dashboard-container { padding: 20px; }
        .dashboard-container h1 { color: #333; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <span>Welcome, <?php echo isset($_SESSION["admin_username"]) ? htmlspecialchars($_SESSION["admin_username"]) : "Admin"; ?>!</span>
        <a href="admin_logout.php">Logout</a>
    </div>

    <div class="dashboard-container">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the student registration system admin panel.</p>
        <p>From here you can manage students, view registrations, and perform other administrative tasks.</p>
        
        <h2>Quick Links</h2>
        <ul>
            <li><a href="view_students.php">View Students</a></li>
            <li><a href="create_student.php">Add New Student (Manual)</a></li>
            <!-- More links will be added as features are developed -->
        </ul>
    </div>
</body>
</html>
