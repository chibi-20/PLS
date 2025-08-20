<?php
header("Content-Type: application/json");
require_once 'db.php'; // This gives us $conn from MySQLi

// Collect form data (POST, not JSON)
$fullname = $_POST['fullname'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$subject = $_POST['subject'] ?? '';
$gradeLevel = $_POST['gradeLevel'] ?? '';
$sections = $_POST['sections'] ?? [];

// Validation
if (empty($fullname) || empty($username) || empty($password) || empty($subject) || empty($gradeLevel) || empty($sections)) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

// Check if username exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already taken"]);
    $stmt->close();
    exit;
}
$stmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$role = 'teacher'; // or whatever role you want to assign
$stmt = $conn->prepare("INSERT INTO users (username, password, role, fullname, subject_taught, grade_level) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $username, $hashedPassword, $role, $fullname, $subject, $gradeLevel);
$stmt->execute();
$userId = $stmt->insert_id;
$stmt->close();

// Insert sections
$stmt = $conn->prepare("INSERT INTO sections (section_name, created_by) VALUES (?, ?)");
foreach ($sections as $section) {
    $stmt->bind_param("si", $section, $userId);
    $stmt->execute();
}
$stmt->close();

echo json_encode(["success" => true, "message" => "Registration successful"]);
?>
