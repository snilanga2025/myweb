<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$conn = null;

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Invalid request method. Only POST is accepted.';
    echo json_encode($response);
    exit;
}

// Get JSON input from the request body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); // Convert JSON to associative array

// --- Input Validation ---
if (empty($input)) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Invalid JSON input or empty request.';
    echo json_encode($response);
    exit;
}

$errors = [];
if (empty($input['name'])) {
    $errors[] = 'Name is required.';
}
if (empty($input['price'])) {
    $errors[] = 'Price is required.';
} elseif (!is_numeric($input['price']) || $input['price'] < 0) {
    $errors[] = 'Price must be a non-negative number.';
}
if (empty($input['category_id'])) {
    $errors[] = 'Category ID is required.';
} elseif (!is_int($input['category_id'])) {
    // Allow string integers, but ensure they are valid integers
    if (!filter_var($input['category_id'], FILTER_VALIDATE_INT)) {
        $errors[] = 'Category ID must be a valid integer.';
    } else {
        $input['category_id'] = (int)$input['category_id']; // Cast to int
    }
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Input validation failed.';
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// --- Process Validated Data ---
$name = $input['name'];
$description = isset($input['description']) ? $input['description'] : null;
$price = (float)$input['price'];
$categoryId = (int)$input['category_id'];
$imageUrl = isset($input['image_url']) ? $input['image_url'] : null;
$availability = isset($input['availability']) ? (bool)$input['availability'] : true; // Default true

try {
    $conn = connectDB();

    $sql = "INSERT INTO menu_items (name, description, price, category_id, image_url, availability) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Log the error for server-side review
        error_log("SQL prepare error in add_item.php: " . $conn->error);
        http_response_code(500); // Internal Server Error
        $response['message'] = 'Error preparing database statement: ' . $conn->error;
        echo json_encode($response);
        exit;
    }

    // Bind parameters: s = string, d = double, i = integer, b = boolean (sent as int)
    $stmt->bind_param("ssdiis", 
        $name, 
        $description, 
        $price, 
        $categoryId, 
        $imageUrl, 
        $availability_int // Use an integer for boolean if your DB setup needs it
    );
    $availability_int = $availability ? 1 : 0; // Convert boolean to int for binding

    if ($stmt->execute()) {
        $newItemId = $stmt->insert_id;
        if ($newItemId > 0) {
            http_response_code(201); // Created
            $response['success'] = true;
            $response['message'] = 'Menu item added successfully.';
            $response['data'] = ['item_id' => $newItemId];
        } else {
            // This case might occur if insert_id is not available, but execute was true
            http_response_code(200); // OK, but without ID
            $response['success'] = true;
            $response['message'] = 'Menu item added, but could not retrieve ID.';
        }
    } else {
        // Log the error for server-side review
        error_log("SQL execute error in add_item.php: " . $stmt->error);
        http_response_code(500); // Internal Server Error
        $response['message'] = 'Failed to add menu item: ' . $stmt->error;
    }
    $stmt->close();

} catch (Exception $e) {
    // Log the exception
    error_log("Exception in add_item.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
} finally {
    if ($conn) {
        closeDB($conn);
    }
}

echo json_encode($response);
?>
