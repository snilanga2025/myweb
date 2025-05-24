<?php
// Initialize the session
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
if (session_destroy()) {
    // Redirect to login page
    header("location: admin_login.php");
    exit();
} else {
    // If session_destroy fails, output an error message or log it.
    // For simplicity, we'll just redirect, but in a real app, handle this more robustly.
    header("location: admin_login.php?logout_error=1");
    exit();
}
?>
