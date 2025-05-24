<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; margin: 0; text-align: center; }
        h1 { color: #333; margin-bottom: 30px; }
        .links a {
            display: inline-block;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            margin: 10px;
            border-radius: 5px;
            font-size: 1.2em;
            transition: background-color 0.3s ease;
        }
        .links a:hover { background-color: #0056b3; }
        .admin-link { background-color: #6c757d; }
        .admin-link:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <h1>Welcome to the Student Registration System</h1>
    <div class="links">
        <a href="frontend/register.php">Student Registration</a>
        <a href="backend/admin_login.php" class="admin-link">Admin Panel Login</a>
        <!-- Add link to student login if/when created -->
    </div>
</body>
</html>
