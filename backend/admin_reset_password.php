<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check for admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$teacherId = intval($input['teacher_id'] ?? 0);
$newPassword = trim($input['new_password'] ?? '');

if ($teacherId <= 0 || empty($newPassword)) {
    echo json_encode([
        "success" => false,
        "message" => "Teacher ID and new password are required"
    ]);
    exit;
}

// Validate password length
if (strlen($newPassword) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters long"
    ]);
    exit;
}

try {
    // Check if teacher exists and is actually a teacher
    $checkStmt = $conn->prepare("SELECT role, fullname, username FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $teacherId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Teacher not found"
        ]);
        $checkStmt->close();
        exit;
    }
    
    $teacher = $checkResult->fetch_assoc();
    if ($teacher['role'] !== 'teacher') {
        echo json_encode([
            "success" => false,
            "message" => "Cannot reset password for non-teacher accounts"
        ]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'teacher'");
    $updateStmt->bind_param("si", $hashedPassword, $teacherId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Password reset successfully",
                "teacher_info" => [
                    "fullname" => $teacher['fullname'],
                    "username" => $teacher['username']
                ]
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No changes were made"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error resetting password"
        ]);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>