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

switch ($action) {
    case 'get_all':
        $stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        $response['status'] = 'success';
        $response['data'] = $projects;
        $stmt->close();
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $response['message'] = 'Project ID is required';
            break;
        }
        
        $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['status'] = 'success';
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Project not found';
        }
        $stmt->close();
        break;
        
    case 'insert':
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $image_path = null;
        
        if (empty($title) || empty($description)) {
            $response['message'] = 'Title and description are required';
            break;
        }
        
        // Handle image upload
        if (isset($_FILES['image'])) {
            $upload = handle_file_upload('image');
            if ($upload['status'] === 'error') {
                $response['message'] = $upload['message'];
                break;
            }
            $image_path = $upload['path'];
        }
        
        $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, technologies, image_path, link) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $title, $description, $technologies, $image_path, $link);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Project added successfully';
            $response['id'] = $conn->insert_id;
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $link = trim($_POST['link'] ?? '');
        
        if ($id === 0 || empty($title) || empty($description)) {
            $response['message'] = 'Project ID, title, and description are required';
            break;
        }
        
        // Handle image upload
        if (isset($_FILES['image'])) {
            $upload = handle_file_upload('image');
            if ($upload['status'] === 'error') {
                $response['message'] = $upload['message'];
                break;
            }
            
            $image_path = $upload['path'];
            $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, technologies = ?, image_path = ?, link = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssssii", $title, $description, $technologies, $image_path, $link, $id, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, technologies = ?, link = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssssii", $title, $description, $technologies, $link, $id, $user_id);
        }
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Project updated successfully';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id === 0) {
            $response['message'] = 'Project ID is required';
            break;
        }
        
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Project deleted successfully';
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
