<?php
require_once __DIR__ . '/config.php';

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return true;
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function handle_file_upload($file_input_name) {
    if (!isset($_FILES[$file_input_name])) {
        return ['status' => 'error', 'message' => 'No file uploaded'];
    }

    $file = $_FILES[$file_input_name];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        return ['status' => 'error', 'message' => 'Only JPEG, PNG, GIF, and WEBP images are allowed'];
    }

    if ($file['size'] > $max_size) {
        return ['status' => 'error', 'message' => 'File size exceeds 5MB limit'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'message' => 'File upload error'];
    }

    // Server directory for uploads (absolute)
    $server_upload_dir = __DIR__ . '/../uploads/';
    // Web-accessible path prefix
    $web_upload_dir = 'uploads/';

    if (!is_dir($server_upload_dir)) {
        mkdir($server_upload_dir, 0755, true);
    }

    // Generate unique filename
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_ext;
    $server_file_path = $server_upload_dir . $file_name;
    $web_file_path = $web_upload_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $server_file_path)) {
        return ['status' => 'success', 'path' => $web_file_path];
    } else {
        return ['status' => 'error', 'message' => 'Failed to save file'];
    }
}
?>
