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
        $stmt = $conn->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $skills = [];
        while ($row = $result->fetch_assoc()) {
            $skills[] = $row;
        }
        $response['status'] = 'success';
        $response['data'] = $skills;
        $stmt->close();
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $response['message'] = 'Skill ID is required';
            break;
        }
        
        $stmt = $conn->prepare("SELECT * FROM skills WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['status'] = 'success';
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Skill not found';
        }
        $stmt->close();
        break;
        
    case 'insert':
        $skill_name = trim($_POST['skill_name'] ?? '');
        $proficiency = (int)($_POST['proficiency'] ?? 50);
        $category = trim($_POST['category'] ?? '');
        
        if (empty($skill_name)) {
            $response['message'] = 'Skill name is required';
            break;
        }
        
        $stmt = $conn->prepare("INSERT INTO skills (user_id, skill_name, proficiency, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $user_id, $skill_name, $proficiency, $category);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Skill added successfully';
            $response['id'] = $conn->insert_id;
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $skill_name = trim($_POST['skill_name'] ?? '');
        $proficiency = (int)($_POST['proficiency'] ?? 50);
        $category = trim($_POST['category'] ?? '');
        
        if ($id === 0 || empty($skill_name)) {
            $response['message'] = 'Skill ID and name are required';
            break;
        }
        
        $stmt = $conn->prepare("UPDATE skills SET skill_name = ?, proficiency = ?, category = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sisii", $skill_name, $proficiency, $category, $id, $user_id);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Skill updated successfully';
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();
        break;
        
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id === 0) {
            $response['message'] = 'Skill ID is required';
            break;
        }
        
        $stmt = $conn->prepare("DELETE FROM skills WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Skill deleted successfully';
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