<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get filter parameters
    $subjectFilter = trim($_GET['subject'] ?? '');
    $gradeLevelFilter = trim($_GET['grade_level'] ?? '');
    
    // Build query for detailed analytics - exclude admin users
    $query = "
        SELECT 
            u.id as user_id,
            u.fullname,
            u.subject_taught,
            u.grade_level,
            COUNT(g.id) as total_students,
            COALESCE(AVG(g.student_grade), 0) as avg_performance,
            SUM(CASE WHEN g.student_grade >= 98 THEN 1 ELSE 0 END) as excellent_count,
            SUM(CASE WHEN g.student_grade >= 95 AND g.student_grade < 98 THEN 1 ELSE 0 END) as very_good_count,
            SUM(CASE WHEN g.student_grade >= 90 AND g.student_grade < 95 THEN 1 ELSE 0 END) as good_count,
            SUM(CASE WHEN g.student_grade >= 85 AND g.student_grade < 90 THEN 1 ELSE 0 END) as satisfactory_count,
            SUM(CASE WHEN g.student_grade >= 80 AND g.student_grade < 85 THEN 1 ELSE 0 END) as fair_count,
            SUM(CASE WHEN g.student_grade >= 75 AND g.student_grade < 80 THEN 1 ELSE 0 END) as needs_improvement_count,
            SUM(CASE WHEN g.student_grade < 75 THEN 1 ELSE 0 END) as poor_count
        FROM users u
        LEFT JOIN sections s ON u.id = s.created_by
        LEFT JOIN grades g ON s.id = g.section_id
        WHERE g.id IS NOT NULL AND u.role = 'teacher'
    ";
    
    $params = [];
    $types = "";
    
    // Add filters
    if ($subjectFilter) {
        $query .= " AND u.subject_taught = ?";
        $params[] = $subjectFilter;
        $types .= "s";
    }
    
    if ($gradeLevelFilter) {
        $query .= " AND u.grade_level = ?";
        $params[] = $gradeLevelFilter;
        $types .= "s";
    }
    
    $query .= " GROUP BY u.id HAVING total_students > 0 ORDER BY avg_performance DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $analytics = [];
    while ($row = $result->fetch_assoc()) {
        $analytics[] = [
            'user_id' => $row['user_id'],
            'fullname' => $row['fullname'],
            'subject_taught' => $row['subject_taught'],
            'grade_level' => $row['grade_level'],
            'total_students' => intval($row['total_students']),
            'avg_performance' => round(floatval($row['avg_performance']), 1),
            'excellent_count' => intval($row['excellent_count']),
            'very_good_count' => intval($row['very_good_count']),
            'good_count' => intval($row['good_count']),
            'satisfactory_count' => intval($row['satisfactory_count']),
            'fair_count' => intval($row['fair_count']),
            'needs_improvement_count' => intval($row['needs_improvement_count']),
            'poor_count' => intval($row['poor_count'])
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "data" => $analytics
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving analytics: " . $e->getMessage()
    ]);
}
?>
