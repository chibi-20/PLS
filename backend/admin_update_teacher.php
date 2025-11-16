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
$fullname = trim($input['fullname'] ?? '');
$username = trim($input['username'] ?? '');
$subjectTaught = trim($input['subject_taught'] ?? '');
$gradeLevel = trim($input['grade_level'] ?? '');

if ($teacherId <= 0 || empty($fullname) || empty($username) || empty($subjectTaught) || empty($gradeLevel)) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

try {
    // Check if teacher exists and is actually a teacher
    $checkStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
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
            "message" => "Cannot modify non-teacher accounts"
        ]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Check if username is already used by another user
    $usernameCheckStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $usernameCheckStmt->bind_param("si", $username, $teacherId);
    $usernameCheckStmt->execute();
    $usernameCheckResult = $usernameCheckStmt->get_result();
    
    if ($usernameCheckResult->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Username is already taken by another user"
        ]);
        $usernameCheckStmt->close();
        exit;
    }
    $usernameCheckStmt->close();
    
    // Update teacher information
    $updateStmt = $conn->prepare("
        UPDATE users 
        SET fullname = ?, 
            username = ?, 
            subject_taught = ?, 
            grade_level = ?
        WHERE id = ? AND role = 'teacher'
    ");
    
    $updateStmt->bind_param("ssssi", $fullname, $username, $subjectTaught, $gradeLevel, $teacherId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Teacher account updated successfully"
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "message" => "No changes were made"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error updating teacher account"
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