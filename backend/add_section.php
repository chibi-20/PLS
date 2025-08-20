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

// Get form data
$sectionName = trim($_POST['section_name'] ?? '');

if (empty($sectionName)) {
    echo json_encode(["success" => false, "message" => "Section name is required"]);
    exit;
}

try {
    // Check if section already exists for this user
    $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND created_by = ?");
    $stmt->bind_param("si", $sectionName, $userId);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Section already exists"]);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Insert new section
    $stmt = $conn->prepare("INSERT INTO sections (section_name, created_by) VALUES (?, ?)");
    $stmt->bind_param("si", $sectionName, $userId);
    $stmt->execute();
    $sectionId = $stmt->insert_id;
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "message" => "Section added successfully",
        "section" => [
            "id" => $sectionId,
            "section_name" => $sectionName
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error adding section: " . $e->getMessage()
    ]);
}
?>