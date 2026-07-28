<?php
// Unlinks the logged-in teacher from one of their assigned sections (used by the
// "Manage Sections" checkbox list). The canonical section itself is not deleted -
// it's admin-owned and may still be assigned to other teachers.
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = $_SESSION['user_id'];

// Get section ID from POST data
$sectionId = intval($_POST['section_id'] ?? 0);

if ($sectionId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid section ID"]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM teacher_sections WHERE teacher_id = ? AND section_id = ?");
    $stmt->bind_param("ii", $userId, $sectionId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(["success" => false, "message" => "Section not found or not assigned to you"]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Section removed successfully"
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error removing section: " . $e->getMessage()]);
}
?>
