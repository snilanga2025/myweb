<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$conn = null;

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Invalid request method. Only POST is accepted for this endpoint.';
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
if (empty($input['item_id'])) {
    $errors[] = 'item_id is required.';
} elseif (!filter_var($input['item_id'], FILTER_VALIDATE_INT) || $input['item_id'] <= 0) {
    $errors[] = 'item_id must be a positive integer.';
} else {
    $input['item_id'] = (int)$input['item_id']; // Cast to int
}

// Validate other fields if present
$allowedFields = ['name' => 's', 'description' => 's', 'price' => 'd', 'category_id' => 'i', 'image_url' => 's', 'availability' => 'i'];
$updateFields = [];
$params = [];
$paramTypes = "";

foreach ($allowedFields as $field => $type) {
    if (isset($input[$field])) {
        // Specific validations
        if ($field === 'price' && (!is_numeric($input[$field]) || $input[$field] < 0)) {
            $errors[] = 'Price must be a non-negative number.';
            continue;
        }
        if ($field === 'category_id' && (!filter_var($input[$field], FILTER_VALIDATE_INT) || $input[$field] <= 0)) {
            $errors[] = 'Category ID must be a positive integer.';
            continue;
        }
        if ($field === 'availability' && !is_bool($input[$field]) && !in_array($input[$field], [0, 1, '0', '1', true, false], true) ) {
             $errors[] = 'Availability must be a boolean value (true/false, 1/0).';
             continue;
        }

        $updateFields[] = "$field = ?";
        if ($field === 'availability') {
            $params[] = $input[$field] ? 1 : 0; // Convert boolean to int for DB
        } else {
            $params[] = $input[$field];
        }
        $paramTypes .= $type;
    }
}

if (empty($updateFields) && empty($errors)) { // No fields to update and no prior errors
    $errors[] = 'No fields provided to update.';
}


if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity (or 400 Bad Request)
    $response['message'] = 'Input validation failed.';
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// --- Process Validated Data ---
$itemId = $input['item_id'];

try {
    $conn = connectDB();

    // First, check if the item exists
    $checkSql = "SELECT item_id FROM menu_items WHERE item_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt === false) {
        error_log("SQL prepare error (check item) in edit_item.php: " . $conn->error);
        http_response_code(500);
        $response['message'] = 'Error preparing database statement for item check.';
        echo json_encode($response);
        exit;
    }
    $checkStmt->bind_param("i", $itemId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows === 0) {
        http_response_code(404); // Not Found
        $response['message'] = "Menu item with ID {$itemId} not found.";
        $checkStmt->close();
        echo json_encode($response);
        exit;
    }
    $checkStmt->close();


    // Dynamically build the UPDATE statement
    $sql = "UPDATE menu_items SET " . implode(', ', $updateFields) . " WHERE item_id = ?";
    $params[] = $itemId; // Add item_id as the last parameter for the WHERE clause
    $paramTypes .= "i"; // Add type for item_id

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL prepare error (update item) in edit_item.php: " . $conn->error . " (Query: " . $sql . ")");
        http_response_code(500);
        $response['message'] = 'Error preparing database statement for update: ' . $conn->error;
        echo json_encode($response);
        exit;
    }

    // Dynamically bind parameters
    $stmt->bind_param($paramTypes, ...$params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            $response['success'] = true;
            $response['message'] = 'Menu item updated successfully.';
            $response['data'] = ['item_id' => $itemId];
        } else {
            // Query executed, but no rows were affected (e.g., data was the same)
            http_response_code(200); // OK
            $response['success'] = true; // Still a success in terms of execution
            $response['message'] = 'Menu item update executed, but no changes were made (data might be the same).';
            $response['data'] = ['item_id' => $itemId];
        }
    } else {
        error_log("SQL execute error in edit_item.php: " . $stmt->error);
        http_response_code(500);
        $response['message'] = 'Failed to update menu item: ' . $stmt->error;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Exception in edit_item.php: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
} finally {
    if ($conn) {
        closeDB($conn);
    }
}

echo json_encode($response);
?>
