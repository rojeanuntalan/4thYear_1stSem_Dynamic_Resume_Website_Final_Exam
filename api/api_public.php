<?php
require_once __DIR__ . '/../includes/config.php';

// Get the first user's (or specified user's) content for public display
$user_id = $_GET['user_id'] ?? 1; // Default to first user for public display

header('Content-Type: application/json');

if (isset($_GET['section'])) {
    $section = $_GET['section'];
    
    switch($section) {
        case 'about':
            $stmt = $conn->prepare("SELECT content, interests, bucket_list, motto FROM about WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            $stmt->close();
            break;
            
        case 'skills':
            $stmt = $conn->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $skills = [];
            while ($row = $result->fetch_assoc()) {
                $skills[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $skills]);
            $stmt->close();
            break;
            
        case 'projects':
            $stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $projects = [];
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $projects]);
            $stmt->close();
            break;
            
        case 'education':
            $stmt = $conn->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY end_year DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $education = [];
            while ($row = $result->fetch_assoc()) {
                $education[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $education]);
            $stmt->close();
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid section']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Section parameter required']);
}
?>
