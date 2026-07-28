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
    $term = $data['term'] ?? '';
    $school_year = $data['school_year'] ?? '';

    if (empty($section_id) || empty($term)) {
        echo json_encode(["success" => false, "message" => "Section and term are required"]);
        exit;
    }

    // Verify that the section belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM sections WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ii", $section_id, $user_id);
    $stmt->execute();
    $section = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$section) {
        echo json_encode(["success" => false, "message" => "Section not found or access denied"]);
        exit;
    }

    // Delete grades for the specified section and term
    $stmt = $conn->prepare("DELETE FROM grades WHERE section_id = ? AND term = ?");
    $stmt->bind_param("ii", $section_id, $term);
    $stmt->execute();

    $deleted_count = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Successfully deleted $deleted_count grade records for Term $term",
        "deleted_count" => $deleted_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
