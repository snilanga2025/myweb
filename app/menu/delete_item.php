<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$response = ['success' => false, 'message' => ''];
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
}

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity (or 400 Bad Request)
    $response['message'] = 'Input validation failed.';
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// --- Process Validated Data ---
$itemId = (int)$input['item_id'];

try {
    $conn = connectDB();

    // First, check if the item exists
    $checkSql = "SELECT item_id FROM menu_items WHERE item_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt === false) {
        error_log("SQL prepare error (check item) in delete_item.php: " . $conn->error);
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

    // Prepare the DELETE statement
    $sql = "DELETE FROM menu_items WHERE item_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL prepare error (delete item) in delete_item.php: " . $conn->error);
        http_response_code(500);
        $response['message'] = 'Error preparing database statement for delete: ' . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("i", $itemId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            $response['success'] = true;
            $response['message'] = "Menu item with ID {$itemId} deleted successfully.";
        } else {
            // This case should ideally not be reached if the check above works correctly
            // and no race condition occurs. If it is reached, it means the item was found
            // but not deleted (e.g., deleted by another process just before this command).
            http_response_code(404); // Or 200 with a specific message
            $response['message'] = "Menu item with ID {$itemId} was found but not deleted. It might have been deleted by another process.";
        }
    } else {
        error_log("SQL execute error in delete_item.php: " . $stmt->error);
        http_response_code(500); // Internal Server Error
        $response['message'] = 'Failed to delete menu item: ' . $stmt->error;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Exception in delete_item.php: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
} finally {
    if ($conn) {
        closeDB($conn);
    }
}

echo json_encode($response);
?>
