<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_db_user'); // Replace with your DB username
define('DB_PASSWORD', 'your_db_password'); // Replace with your DB password
define('DB_NAME', 'student_registration_system'); // Replace with your DB name

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . $conn->connect_error);
}

// Optional: Set character set to utf8mb4 for full Unicode support
if (!$conn->set_charset("utf8mb4")) {
    // printf("Error loading character set utf8mb4: %s
", $conn->error);
    // For now, we'll just proceed, but in a real app, this might be a critical error.
}

// The connection $conn will be used by other PHP scripts.
?>
