<?php
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
    // Verify that this section belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM sections WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ii", $sectionId, $userId);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Section not found or not authorized"]);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Delete the section
    $stmt = $conn->prepare("DELETE FROM sections WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ii", $sectionId, $userId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "message" => "Section deleted successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error deleting section: " . $e->getMessage()
    ]);
}
?>
