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
    // Get sections assigned to this teacher
    $stmt = $conn->prepare("
        SELECT s.id, s.section_name
        FROM sections s
        JOIN teacher_sections ts ON ts.section_id = s.id
        WHERE ts.teacher_id = ?
        ORDER BY s.section_name
    ");
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