<?php
// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Important: Use a strong password in a production environment
define('DB_NAME', 'restaurant_db');

/**
 * Establishes a connection to the database using mysqli.
 * 
 * @return mysqli The database connection object on success.
 * @throws Exception If the connection fails, it terminates the script.
 */
function connectDB() {
    // Attempt to connect to the database
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // In a real application, you would typically log this error to a file
        // and display a user-friendly message, rather than exposing the raw error.
        // For development purposes, die() can be acceptable.
        error_log("Database connection failed: " . $conn->connect_error); // Log error
        die("Database connection failed. Please try again later."); // User-friendly message
    }

    // Set character set to utf8mb4 for better Unicode support
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
        // Continue without this, but log the error
    }

    return $conn;
}

/**
 * Closes the database connection.
 * 
 * @param mysqli $conn The database connection object to close.
 */
function closeDB($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Example usage (optional, can be commented out or removed)
/*
$connection = connectDB();
if ($connection) {
    echo "Successfully connected to the database.<br>";
    // Perform database operations here...
    closeDB($connection);
    echo "Database connection closed.";
} else {
    echo "Failed to connect to the database.";
}
*/
?>
