<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Debug: Log the session info
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = $_SESSION['user_id'];
error_log("User ID: " . $userId); // Debug log

// Get query parameters
$sectionName = trim($_GET['section'] ?? '');
$quarter = intval($_GET['quarter'] ?? 0);

try {
    if ($sectionName && $quarter > 0) {
        // Get specific section and quarter data
        $stmt = $conn->prepare("
            SELECT g.student_grade, g.gender 
            FROM grades g 
            JOIN sections s ON g.section_id = s.id 
            WHERE s.section_name = ? AND g.quarter = ? AND g.created_by = ?
        ");
        $stmt->bind_param("sii", $sectionName, $quarter, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $boys = [];
        $girls = [];
        
        while ($row = $result->fetch_assoc()) {
            if ($row['gender'] === 'Male') {
                $boys[] = floatval($row['student_grade']);
            } else {
                $girls[] = floatval($row['student_grade']);
            }
        }
        
        $stmt->close();
        
        echo json_encode([
            "success" => true,
            "section" => $sectionName,
            "quarter" => $quarter,
            "boys" => $boys,
            "girls" => $girls
        ]);
        
    } else {
        // Get all data for the current user
        error_log("Getting all data for user: " . $userId); // Debug log
        
        $stmt = $conn->prepare("
            SELECT s.section_name, g.quarter, g.student_grade, g.gender 
            FROM grades g 
            JOIN sections s ON g.section_id = s.id 
            WHERE g.created_by = ?
            ORDER BY s.section_name, g.quarter
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        $rowCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $rowCount++;
            $key = $row['section_name'] . '_Q' . $row['quarter'];
            
            if (!isset($data[$key])) {
                $data[$key] = [
                    'section' => $row['section_name'],
                    'quarter' => $row['quarter'],
                    'boys' => [],
                    'girls' => []
                ];
            }
            
            if ($row['gender'] === 'Male') {
                $data[$key]['boys'][] = floatval($row['student_grade']);
            } else {
                $data[$key]['girls'][] = floatval($row['student_grade']);
            }
        }
        
        error_log("Found " . $rowCount . " rows, grouped into " . count($data) . " sections/quarters"); // Debug log
        
        $stmt->close();
        
        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving data: " . $e->getMessage()
    ]);
}
?>