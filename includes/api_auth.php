<?php
require_once __DIR__ . '/config.php';

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $response['message'] = 'All fields are required';
        } elseif ($password !== $confirm_password) {
            $response['message'] = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $response['message'] = 'Password must be at least 6 characters';
        } else {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $response['message'] = 'Username or email already exists';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($insert_stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Registration successful! Please log in.';
                } else {
                    $response['message'] = 'Registration failed: ' . $conn->error;
                }
                $insert_stmt->close();
            }
            $stmt->close();
        }
    }
    elseif ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $response['message'] = 'Username and password are required';
        } else {
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $username;
                    $response['status'] = 'success';
                    $response['message'] = 'Login successful!';
                } else {
                    $response['message'] = 'Invalid password';
                }
            } else {
                $response['message'] = 'Username not found';
            }
            $stmt->close();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
