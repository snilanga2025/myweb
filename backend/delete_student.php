<?php
session_start();
require_once "db_connect.php";

// Check if the admin is logged in, otherwise redirect to login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

$student_id = "";
$delete_err = $delete_success = "";

if(isset($_GET["id"]) && !empty(trim($_GET["id"])) && ctype_digit($_GET["id"])){
    $student_id = trim($_GET["id"]);

    // First, retrieve the profile image path to delete the file
    $sql_select_image = "SELECT profile_image_path FROM students WHERE id = ?";
    if($stmt_select = $conn->prepare($sql_select_image)){
        $stmt_select->bind_param("i", $student_id);
        if($stmt_select->execute()){
            $stmt_select->store_result();
            if($stmt_select->num_rows == 1){
                $stmt_select->bind_result($profile_image_to_delete);
                $stmt_select->fetch();

                // Now, delete the student record
                $sql_delete = "DELETE FROM students WHERE id = ?";
                if($stmt_delete = $conn->prepare($sql_delete)){
                    $stmt_delete->bind_param("i", $student_id);
                    if($stmt_delete->execute()){
                        // If record deletion is successful, try to delete the image file
                        if(!empty($profile_image_to_delete)){
                            $image_file_path = "../" . $profile_image_to_delete; // Path relative to backend directory
                            if(file_exists($image_file_path)){
                                if(unlink($image_file_path)){
                                    // Image deleted successfully
                                    $_SESSION['success_message'] = "Student record and profile image deleted successfully.";
                                } else {
                                    // Image deletion failed, but record was deleted
                                    $_SESSION['warning_message'] = "Student record deleted, but failed to delete profile image file. Please check permissions.";
                                }
                            } else {
                                // Image file not found, but record deleted
                                 $_SESSION['success_message'] = "Student record deleted successfully. Profile image file not found.";
                            }
                        } else {
                            // No profile image associated, record deleted
                            $_SESSION['success_message'] = "Student record deleted successfully.";
                        }
                        header("location: view_students.php");
                        exit();
                    } else {
                        $_SESSION['error_message'] = "Error deleting student record. Please try again.";
                    }
                    $stmt_delete->close();
                } else {
                     $_SESSION['error_message'] = "Database error (delete prepare). Please try again.";
                }
            } else {
                 $_SESSION['error_message'] = "Student not found or already deleted.";
            }
        } else {
            $_SESSION['error_message'] = "Error fetching student details for deletion. Please try again.";
        }
        $stmt_select->close();
    } else {
        $_SESSION['error_message'] = "Database error (select image prepare). Please try again.";
    }
    
    $conn->close();
    // Redirect back to student list if there was an error before exit
    header("location: view_students.php");
    exit();

} else {
    // If ID is not provided or invalid
    $_SESSION['error_message'] = "Invalid student ID for deletion.";
    header("location: view_students.php");
    exit;
}
?>
