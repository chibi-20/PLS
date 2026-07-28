<?php
// Links the logged-in teacher to one of the canonical sections for their grade
// level (used by the "Manage Sections" checkbox list). Sections themselves are
// created by the admin, not teachers.
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = $_SESSION['user_id'];
$sectionId = intval($_POST['section_id'] ?? 0);

if ($sectionId <= 0) {
    echo json_encode(["success" => false, "message" => "Section is required"]);
    exit;
}

try {
    // Verify the section belongs to the teacher's own grade level
    $stmt = $conn->prepare("
        SELECT s.id, s.section_name
        FROM sections s
        JOIN users u ON u.grade_level = s.grade_level
        WHERE s.id = ? AND u.id = ?
    ");
    $stmt->bind_param("ii", $sectionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Section not found for your grade level"]);
        $stmt->close();
        exit;
    }
    $section = $result->fetch_assoc();
    $stmt->close();

    // Check if already assigned
    $stmt = $conn->prepare("SELECT id FROM teacher_sections WHERE teacher_id = ? AND section_id = ?");
    $stmt->bind_param("ii", $userId, $sectionId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Section already assigned"]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO teacher_sections (teacher_id, section_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $sectionId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Section added successfully",
        "section" => [
            "id" => $sectionId,
            "section_name" => $section['section_name']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error adding section: " . $e->getMessage()]);
}
?>
