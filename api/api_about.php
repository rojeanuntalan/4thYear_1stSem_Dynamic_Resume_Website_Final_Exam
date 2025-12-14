<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => ''];

if (!check_auth()) {
    http_response_code(401);
    $response['message'] = 'Unauthorized. Please log in.';
    echo json_encode($response);
    exit;
}

$user_id = get_user_id();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$colsCheck = $conn->query("SHOW COLUMNS FROM about LIKE 'interests'");
if ($colsCheck && $colsCheck->num_rows === 0) {
    $conn->query("ALTER TABLE about ADD COLUMN interests LONGTEXT NULL AFTER content");
}
$colsCheck = $conn->query("SHOW COLUMNS FROM about LIKE 'bucket_list'");
if ($colsCheck && $colsCheck->num_rows === 0) {
    $conn->query("ALTER TABLE about ADD COLUMN bucket_list LONGTEXT NULL AFTER interests");
}
$colsCheck = $conn->query("SHOW COLUMNS FROM about LIKE 'motto'");
if ($colsCheck && $colsCheck->num_rows === 0) {
    $conn->query("ALTER TABLE about ADD COLUMN motto VARCHAR(255) NULL AFTER bucket_list");
}

switch ($action) {
    case 'get':
        $stmt = $conn->prepare("SELECT * FROM about WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $about = $result->fetch_assoc();
        $response['status'] = 'success';
        $response['data'] = $about ?: null;
        $stmt->close();
        break;
        
    case 'insert':
    case 'update':
        $content = $_POST['content'] ?? '';
        $interests = $_POST['interests'] ?? '';
        $bucket_list = $_POST['bucket_list'] ?? '';
        $motto = $_POST['motto'] ?? '';

        if (empty($content)) {
            $response['message'] = 'About content is required';
            break;
        }

        // Check if record exists
        $check_stmt = $conn->prepare("SELECT id FROM about WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_result->num_rows > 0) {
            // Update existing (no image handling for About)
            $stmt = $conn->prepare("UPDATE about SET content = ?, interests = ?, bucket_list = ?, motto = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $content, $interests, $bucket_list, $motto, $user_id);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO about (user_id, content, interests, bucket_list, motto) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $content, $interests, $bucket_list, $motto);
        }
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'About section updated successfully';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'delete':
        $stmt = $conn->prepare("DELETE FROM about WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'About section deleted successfully';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    default:
        $response['message'] = 'Invalid action';
}

echo json_encode($response);
?>
