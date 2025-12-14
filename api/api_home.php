<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'check_auth') {
    // Check if user is authenticated
    $is_authenticated = check_auth();
    echo json_encode(['authenticated' => $is_authenticated]);
    exit;
}

// Check authentication for other actions
if (!check_auth()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = get_user_id();

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $upload = handle_file_upload('image');
    
    if ($upload['status'] === 'error') {
        echo json_encode(['status' => 'error', 'message' => $upload['message']]);
        exit;
    }
    
    // Store the image path in the about table or a new profile table
    // For now, we'll just return the path
    echo json_encode(['status' => 'success', 'path' => $upload['path'], 'message' => 'Image uploaded successfully']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>
