<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check for admin access (for production, uncomment this)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(["success" => false, "message" => "Admin access required"]);
//     exit;
// }

try {
    // Get filter parameters
    $schoolYearFilter = trim($_GET['school_year'] ?? '');
    $subjectFilter = trim($_GET['subject'] ?? '');
    $gradeLevelFilter = trim($_GET['grade_level'] ?? '');
    $quarterFilter = intval($_GET['quarter'] ?? 0);
    
    // Build the query to get subject proficiency data
    $query = "
        SELECT 
            u.subject_taught,
            u.grade_level,
            g.quarter,
            COUNT(g.id) as total_students,
            COALESCE(AVG(g.student_grade), 0) as avg_grade,
            SUM(CASE WHEN g.student_grade >= 98 THEN 1 ELSE 0 END) as excellent_count,
            SUM(CASE WHEN g.student_grade >= 95 AND g.student_grade < 98 THEN 1 ELSE 0 END) as very_good_count,
            SUM(CASE WHEN g.student_grade >= 90 AND g.student_grade < 95 THEN 1 ELSE 0 END) as good_count,
            SUM(CASE WHEN g.student_grade >= 85 AND g.student_grade < 90 THEN 1 ELSE 0 END) as satisfactory_count,
            SUM(CASE WHEN g.student_grade >= 80 AND g.student_grade < 85 THEN 1 ELSE 0 END) as fair_count,
            SUM(CASE WHEN g.student_grade >= 75 AND g.student_grade < 80 THEN 1 ELSE 0 END) as needs_improvement_count,
            SUM(CASE WHEN g.student_grade < 75 THEN 1 ELSE 0 END) as poor_count
        FROM users u
        JOIN sections s ON u.id = s.created_by
        JOIN grades g ON s.id = g.section_id
        WHERE u.role = 'teacher'
    ";
    
    $params = [];
    $types = "";
    
    // Add filters
    if ($schoolYearFilter) {
        $query .= " AND g.school_year = ?";
        $params[] = $schoolYearFilter;
        $types .= "s";
    }
    
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
    
    if ($quarterFilter > 0) {
        $query .= " AND g.quarter = ?";
        $params[] = $quarterFilter;
        $types .= "i";
    }
    
    $query .= " GROUP BY u.subject_taught, u.grade_level, g.quarter ORDER BY u.subject_taught, u.grade_level, g.quarter";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $key = $row['subject_taught'] . '_' . $row['grade_level'] . '_Q' . $row['quarter'];
        $subjects[$key] = [
            'subject' => $row['subject_taught'],
            'grade_level' => $row['grade_level'],
            'quarter' => $row['quarter'],
            'total_students' => intval($row['total_students']),
            'avg_grade' => round(floatval($row['avg_grade']), 1),
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
        "data" => $subjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving subject proficiency data: " . $e->getMessage()
    ]);
}
?>
