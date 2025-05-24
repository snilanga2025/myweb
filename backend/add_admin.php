<?php
require_once 'db_connect.php'; // For $conn

// --- Configuration for the new admin ---
$new_admin_username = 'admin';
$new_admin_password = 'securepassword123'; // Choose a strong password

// Validate connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Hash the password
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);

// SQL to insert admin
$sql = "INSERT INTO admins (username, password) VALUES (?, ?)";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ss", $new_admin_username, $hashed_password);
    
    if ($stmt->execute()) {
        echo "Admin user '" . htmlspecialchars($new_admin_username) . "' created successfully!<br>";
        echo "Please DELETE this file (add_admin.php) immediately for security reasons.<br>";
    } else {
        if ($stmt->errno === 1062) { // Error code for duplicate entry
             echo "Error: Admin user '" . htmlspecialchars($new_admin_username) . "' already exists.<br>";
        } else {
             echo "Error creating admin user: " . htmlspecialchars($stmt->error) . "<br>";
        }
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . htmlspecialchars($conn->error) . "<br>";
}

$conn->close();
?>
