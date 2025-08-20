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

try {
    // Get sections created by this user
    $stmt = $conn->prepare("SELECT id, section_name FROM sections WHERE created_by = ? ORDER BY section_name");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "sections" => $sections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error fetching sections: " . $e->getMessage()
    ]);
}
?>