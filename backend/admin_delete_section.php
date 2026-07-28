<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sectionId = intval($input['section_id'] ?? 0);

if ($sectionId <= 0) {
    echo json_encode(["success" => false, "message" => "Valid section ID is required"]);
    exit;
}

try {
    // Deleting cascades to teacher_sections, grades, and lis_student_counts via FK
    $stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
    $stmt->bind_param("i", $sectionId);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted === 0) {
        echo json_encode(["success" => false, "message" => "Section not found"]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Section deleted successfully"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error deleting section: " . $e->getMessage()]);
}
?>
