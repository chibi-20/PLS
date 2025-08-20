<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

try {
    $query = "
        SELECT g.student_grade 
        FROM grades g 
        JOIN sections s ON g.section_id = s.id 
        JOIN users u ON s.created_by = u.id 
        WHERE u.role = 'teacher'
    ";
    $result = $conn->query($query);
    
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = floatval($row['student_grade']);
    }
    
    // Categorize grades according to proficiency levels
    $distribution = [
        'excellent' => 0,        // 98-100
        'veryGood' => 0,         // 95-97
        'good' => 0,             // 90-94
        'satisfactory' => 0,     // 85-89
        'fair' => 0,             // 80-84
        'needsImprovement' => 0, // 75-79
        'poor' => 0              // Below 75
    ];
    
    foreach ($grades as $grade) {
        if ($grade >= 98) {
            $distribution['excellent']++;
        } elseif ($grade >= 95) {
            $distribution['veryGood']++;
        } elseif ($grade >= 90) {
            $distribution['good']++;
        } elseif ($grade >= 85) {
            $distribution['satisfactory']++;
        } elseif ($grade >= 80) {
            $distribution['fair']++;
        } elseif ($grade >= 75) {
            $distribution['needsImprovement']++;
        } else {
            $distribution['poor']++;
        }
    }
    
    echo json_encode([
        "success" => true,
        "data" => $distribution
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving grade distribution: " . $e->getMessage()
    ]);
}
?>
