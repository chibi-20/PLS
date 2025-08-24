<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

// Database connection
require_once 'db.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    $section_id = $data['section_id'] ?? '';
    $quarter = $data['quarter'] ?? '';
    
    if (empty($section_id) || empty($quarter)) {
        echo json_encode(["success" => false, "message" => "Section and quarter are required"]);
        exit;
    }
    
    // Verify that the section belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM sections WHERE id = ? AND created_by = ?");
    $stmt->execute([$section_id, $user_id]);
    $section = $stmt->fetch();
    
    if (!$section) {
        echo json_encode(["success" => false, "message" => "Section not found or access denied"]);
        exit;
    }
    
    // Delete grades for the specified section and quarter
    $stmt = $pdo->prepare("DELETE FROM grades WHERE section_id = ? AND quarter = ?");
    $stmt->execute([$section_id, $quarter]);
    
    $deleted_count = $stmt->rowCount();
    
    echo json_encode([
        "success" => true, 
        "message" => "Successfully deleted $deleted_count grade records for Quarter $quarter",
        "deleted_count" => $deleted_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
