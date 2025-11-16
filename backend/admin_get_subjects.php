<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

try {
    $query = "SELECT DISTINCT subject_taught FROM users WHERE subject_taught IS NOT NULL AND subject_taught != '' AND role = 'teacher' ORDER BY subject_taught";
    $result = $conn->query($query);
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_taught'];
    }
    
    echo json_encode([
        "success" => true,
        "subjects" => $subjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving subjects: " . $e->getMessage()
    ]);
}
?>
