<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get filter parameters
    $schoolYearFilter = trim($_GET['school_year'] ?? '');
    $subjectFilter = trim($_GET['subject'] ?? '');
    $gradeLevelFilter = trim($_GET['grade_level'] ?? '');
    $quarterFilter = trim($_GET['quarter'] ?? '');
    
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
            SUM(CASE WHEN g.student_grade < 75 THEN 1 ELSE 0 END) as poor_count,
            -- Gender breakdown by proficiency level
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 98 THEN 1 ELSE 0 END) as excellent_male_count,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 95 AND g.student_grade < 98 THEN 1 ELSE 0 END) as very_good_male_count,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 90 AND g.student_grade < 95 THEN 1 ELSE 0 END) as good_male_count,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 85 AND g.student_grade < 90 THEN 1 ELSE 0 END) as satisfactory_male_count,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 80 AND g.student_grade < 85 THEN 1 ELSE 0 END) as fair_male_count,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 75 AND g.student_grade < 80 THEN 1 ELSE 0 END) as needs_improvement_male_count,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade < 75 THEN 1 ELSE 0 END) as poor_male_count,
            -- Female counts by proficiency level
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 98 THEN 1 ELSE 0 END) as excellent_female_count,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 95 AND g.student_grade < 98 THEN 1 ELSE 0 END) as very_good_female_count,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 90 AND g.student_grade < 95 THEN 1 ELSE 0 END) as good_female_count,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 85 AND g.student_grade < 90 THEN 1 ELSE 0 END) as satisfactory_female_count,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 80 AND g.student_grade < 85 THEN 1 ELSE 0 END) as fair_female_count,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 75 AND g.student_grade < 80 THEN 1 ELSE 0 END) as needs_improvement_female_count,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade < 75 THEN 1 ELSE 0 END) as poor_female_count,
            -- Total gender counts
            SUM(CASE WHEN g.gender = 'Male' THEN 1 ELSE 0 END) as total_male_count,
            SUM(CASE WHEN g.gender = 'Female' THEN 1 ELSE 0 END) as total_female_count
        FROM users u
        LEFT JOIN sections s ON u.id = s.created_by
        LEFT JOIN grades g ON s.id = g.section_id
        WHERE g.id IS NOT NULL AND u.role = 'teacher'
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
    
    if ($quarterFilter) {
        $query .= " AND g.quarter = ?";
        $params[] = $quarterFilter;
        $types .= "i";
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
            'poor_count' => intval($row['poor_count']),
            // Gender breakdown by proficiency level
            'excellent_male_count' => intval($row['excellent_male_count']),
            'very_good_male_count' => intval($row['very_good_male_count']),
            'good_male_count' => intval($row['good_male_count']),
            'satisfactory_male_count' => intval($row['satisfactory_male_count']),
            'fair_male_count' => intval($row['fair_male_count']),
            'needs_improvement_male_count' => intval($row['needs_improvement_male_count']),
            'poor_male_count' => intval($row['poor_male_count']),
            'excellent_female_count' => intval($row['excellent_female_count']),
            'very_good_female_count' => intval($row['very_good_female_count']),
            'good_female_count' => intval($row['good_female_count']),
            'satisfactory_female_count' => intval($row['satisfactory_female_count']),
            'fair_female_count' => intval($row['fair_female_count']),
            'needs_improvement_female_count' => intval($row['needs_improvement_female_count']),
            'poor_female_count' => intval($row['poor_female_count']),
            // Total gender counts
            'total_male_count' => intval($row['total_male_count']),
            'total_female_count' => intval($row['total_female_count'])
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
