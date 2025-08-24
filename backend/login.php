<?php
header('Content-Type: application/json');
require_once 'db.php'; // This gives us $conn from MySQLi

// Read and decode JSON request
$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Username and password are required"
    ]);
    exit;
}

// Check user in database
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Start session and store user info
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['grade_level'] = $user['grade_level'];
        
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "role" => $user['role'],
                "fullname" => $user['fullname'],
                "subject_taught" => $user['subject_taught'],
                "grade_level" => $user['grade_level']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
