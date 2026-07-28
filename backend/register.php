<?php
header("Content-Type: application/json");
require_once 'db.php'; // This gives us $conn from MySQLi

// Collect form data (POST, not JSON)
$fullname = $_POST['fullname'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$subject = $_POST['subject'] ?? '';
$gradeLevel = $_POST['gradeLevel'] ?? '';
$sectionIds = $_POST['section_ids'] ?? [];

// Validation
if (empty($fullname) || empty($username) || empty($password) || empty($subject) || empty($gradeLevel) || empty($sectionIds)) {
    echo json_encode(["success" => false, "message" => "All fields are required, including at least one section"]);
    exit;
}

$sectionIds = array_values(array_unique(array_map('intval', $sectionIds)));

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

// Verify the submitted sections actually belong to the chosen grade level
$placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
$types = str_repeat('i', count($sectionIds));
$stmt = $conn->prepare("SELECT id FROM sections WHERE grade_level = ? AND id IN ($placeholders)");
$stmt->bind_param('s' . $types, $gradeLevel, ...$sectionIds);
$stmt->execute();
$result = $stmt->get_result();
$validIds = [];
while ($row = $result->fetch_assoc()) {
    $validIds[] = intval($row['id']);
}
$stmt->close();

if (count($validIds) !== count($sectionIds)) {
    echo json_encode(["success" => false, "message" => "One or more selected sections are invalid for this grade level"]);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();

try {
    // Insert user
    $role = 'teacher';
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, fullname, subject_taught, grade_level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $hashedPassword, $role, $fullname, $subject, $gradeLevel);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();

    // Link the teacher to their chosen sections
    $stmt = $conn->prepare("INSERT INTO teacher_sections (teacher_id, section_id) VALUES (?, ?)");
    foreach ($validIds as $sectionId) {
        $stmt->bind_param("ii", $userId, $sectionId);
        $stmt->execute();
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Registration successful"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error during registration: " . $e->getMessage()]);
}
?>
