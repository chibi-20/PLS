<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get total teachers (exclude admin users)
    $teachersQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'";
    $teachersResult = $conn->query($teachersQuery);
    $totalTeachers = $teachersResult->fetch_assoc()['total'];
    
    // Get total sections (created by teachers only)
    $sectionsQuery = "
        SELECT COUNT(*) as total 
        FROM sections s 
        JOIN users u ON s.created_by = u.id 
        WHERE u.role = 'teacher'
    ";
    $sectionsResult = $conn->query($sectionsQuery);
    $totalSections = $sectionsResult->fetch_assoc()['total'];
    
    // Get total grades (from teacher sections only)
    $gradesQuery = "
        SELECT COUNT(*) as total 
        FROM grades g 
        JOIN sections s ON g.section_id = s.id 
        JOIN users u ON s.created_by = u.id 
        WHERE u.role = 'teacher'
    ";
    $gradesResult = $conn->query($gradesQuery);
    $totalGrades = $gradesResult->fetch_assoc()['total'];
    
    // Get average performance (from teacher grades only)
    $avgQuery = "
        SELECT AVG(g.student_grade) as avg_grade 
        FROM grades g 
        JOIN sections s ON g.section_id = s.id 
        JOIN users u ON s.created_by = u.id 
        WHERE u.role = 'teacher'
    ";
    $avgResult = $conn->query($avgQuery);
    $avgRow = $avgResult->fetch_assoc();
    $averagePerformance = $avgRow['avg_grade'] ? round(floatval($avgRow['avg_grade']), 1) : 0;
    
    echo json_encode([
        "success" => true,
        "data" => [
            "total_teachers" => intval($totalTeachers),
            "total_sections" => intval($totalSections),
            "total_grades" => intval($totalGrades),
            "average_performance" => $averagePerformance
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving system stats: " . $e->getMessage()
    ]);
}
?>
