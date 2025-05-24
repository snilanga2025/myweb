<?php
session_start();

// Check if the admin is logged in, otherwise redirect to login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

// Include db_connect.php and any other necessary files (e.g., for fetching students - to be added later)
require_once "db_connect.php";

// Placeholder for student data
$students = []; // This will be populated from the database later

// --- Logic to fetch students from database will be added here ---
// Example:
// $sql = "SELECT id, first_name, last_name, email, profile_image_path, registration_date FROM students ORDER BY registration_date DESC";
// $result = $conn->query($sql);
// if ($result && $result->num_rows > 0) {
//     while($row = $result->fetch_assoc()) {
//         $students[] = $row;
//     }
// }
// $conn->close(); // Close connection if opened for this script specifically

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Students - Admin</title>
    <link rel="stylesheet" href="../assets/main_style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
        .dashboard-header { background-color: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-header .logo a { color: white; text-decoration: none; font-size: 1.5em; font-weight: bold;}
        .dashboard-header nav a { color: white; text-decoration: none; margin-left: 20px; }
        .dashboard-header nav a:hover { text-decoration: underline; }
        
        .container { padding: 20px; }
        .container h1 { color: #333; margin-bottom: 20px; }
        
        table.students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .students-table th, .students-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }
        .students-table thead th {
            background-color: #007bff; /* Blue header for the table */
            color: white;
            font-weight: bold;
        }
        .students-table tbody tr:nth-child(even) {
            background-color: #f9f9f9; /* Light grey for even rows */
        }
        .students-table tbody tr:hover {
            background-color: #f1f1f1; /* Slightly darker grey on hover */
        }
        .students-table img.profile-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #ddd;
        }
        .actions a {
            margin-right: 8px;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .actions a.edit { background-color: #ffc107; color: #333; } /* Yellow for Edit */
        .actions a.delete { background-color: #dc3545; color: white; } /* Red for Delete */
        .actions a.edit:hover { background-color: #e0a800; }
        .actions a.delete:hover { background-color: #c82333; }
        .no-students { text-align: center; padding: 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px; }
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
        <h1>Registered Students</h1>

        <?php if (empty($students)): ?>
            <p class="no-students">No students registered yet.</p>
        <?php else: ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profile Pic</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                        <td>
                            <?php if (!empty($student['profile_image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($student['profile_image_path']); ?>" alt="Profile" class="profile-thumb">
                            <?php else: ?>
                                <img src="../assets/default_avatar.png" alt="Default" class="profile-thumb"> <!-- Placeholder for default avatar -->
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($student['registration_date']))); ?></td>
                        <td class="actions">
                            <a href="edit_student.php?id=<?php echo htmlspecialchars($student['id']); ?>" class="edit">Edit</a>
                            <a href="delete_student.php?id=<?php echo htmlspecialchars($student['id']); ?>" class="delete" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><a href="create_student.php">Add New Student (Manual)</a></p>
    </div>

</body>
</html>
