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
        $stmt = $conn->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY end_year DESC, start_year DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $education = [];
        while ($row = $result->fetch_assoc()) {
            $education[] = $row;
        }
        $response['status'] = 'success';
        $response['data'] = $education;
        $stmt->close();
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $response['message'] = 'Education ID is required';
            break;
        }
        
        $stmt = $conn->prepare("SELECT * FROM education WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['status'] = 'success';
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Education record not found';
        }
        $stmt->close();
        break;
        
    case 'insert':
        $institution = trim($_POST['institution'] ?? '');
        $degree = trim($_POST['degree'] ?? '');
        $field = trim($_POST['field'] ?? '');
        $start_year = (int)($_POST['start_year'] ?? 0);
        $end_year = (int)($_POST['end_year'] ?? 0);
        $image_path = null;
        
        if (empty($institution) || empty($degree)) {
            $response['message'] = 'Institution and degree are required';
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
        
        $stmt = $conn->prepare("INSERT INTO education (user_id, institution, degree, field, start_year, end_year, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // types: i (user_id), s (institution), s (degree), s (field), i (start_year), i (end_year), s (image_path)
        $stmt->bind_param("isssiis", $user_id, $institution, $degree, $field, $start_year, $end_year, $image_path);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Education record added successfully';
            $response['id'] = $conn->insert_id;
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $institution = trim($_POST['institution'] ?? '');
        $degree = trim($_POST['degree'] ?? '');
        $field = trim($_POST['field'] ?? '');
        $start_year = (int)($_POST['start_year'] ?? 0);
        $end_year = (int)($_POST['end_year'] ?? 0);
        
        if ($id === 0 || empty($institution) || empty($degree)) {
            $response['message'] = 'Education ID, institution, and degree are required';
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
            $stmt = $conn->prepare("UPDATE education SET institution = ?, degree = ?, field = ?, start_year = ?, end_year = ?, image_path = ? WHERE id = ? AND user_id = ?");
            // types: s,s,s,i,i,s,i,i
            $stmt->bind_param("sssiisii", $institution, $degree, $field, $start_year, $end_year, $image_path, $id, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE education SET institution = ?, degree = ?, field = ?, start_year = ?, end_year = ? WHERE id = ? AND user_id = ?");
            // types: s,s,s,i,i,i,i (institution, degree, field, start_year, end_year, id, user_id)
            $stmt->bind_param("sssiiii", $institution, $degree, $field, $start_year, $end_year, $id, $user_id);
        }
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Education record updated successfully';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id === 0) {
            $response['message'] = 'Education ID is required';
            break;
        }
        
        $stmt = $conn->prepare("DELETE FROM education WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Education record deleted successfully';
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
