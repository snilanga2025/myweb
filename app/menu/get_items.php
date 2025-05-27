<?php
// Set content type to application/json for the response
header('Content-Type: application/json');

// Include the database connection script
require_once __DIR__ . '/../../config/database.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$conn = null; // Initialize $conn to null

try {
    // Establish database connection
    $conn = connectDB();

    // SQL query to fetch menu items with their category names
    // Fetches all columns from menu_items (m.*) and the category name (c.name AS category_name)
    $sql = "SELECT m.*, c.name AS category_name 
            FROM menu_items m 
            LEFT JOIN categories c ON m.category_id = c.category_id 
            ORDER BY m.item_id ASC";

    $result = $conn->query($sql);

    if ($result) {
        $menuItems = [];
        // Fetch associative array
        while ($row = $result->fetch_assoc()) {
            // Convert numeric types from string to actual numbers for JSON
            $row['item_id'] = (int)$row['item_id'];
            $row['price'] = (float)$row['price'];
            if ($row['category_id'] !== null) {
                $row['category_id'] = (int)$row['category_id'];
            }
            $row['availability'] = (bool)$row['availability'];
            $menuItems[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $menuItems;
        $result->free(); // Free result set
    } else {
        // Query execution failed
        $response['message'] = "Error fetching menu items: " . $conn->error;
        // Log the error for server-side review
        error_log("SQL Error in get_items.php: " . $conn->error . " (Query: " . $sql . ")");
    }

} catch (Exception $e) {
    // Catch any other exceptions (e.g., connection issues from connectDB)
    $response['message'] = "An error occurred: " . $e->getMessage();
    // Log the exception
    error_log("Exception in get_items.php: " . $e->getMessage());
} finally {
    // Always close the database connection if it was opened
    if ($conn) {
        closeDB($conn);
    }
}

// Output the JSON response
echo json_encode($response);
?>
