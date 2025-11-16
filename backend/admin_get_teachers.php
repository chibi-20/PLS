<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check for admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

try {
    // Check if requesting simple list for account management
    $simple = isset($_GET['simple']) && $_GET['simple'] === 'true';
    
    if ($simple) {
        // Simple list for account management
        $query = "
            SELECT 
                id,
                fullname,
                username,
                subject_taught,
                grade_level
            FROM users
            WHERE role = 'teacher'
            ORDER BY fullname ASC
        ";
        
        $result = $conn->query($query);
        
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = [
                'id' => intval($row['id']),
                'fullname' => $row['fullname'],
                'username' => $row['username'],
                'subject_taught' => $row['subject_taught'],
                'grade_level' => $row['grade_level'],
                'created_at' => null // No timestamp in this table
            ];
        }
        
        echo json_encode([
            "success" => true,
            "teachers" => $teachers,
            "total_count" => count($teachers)
        ]);
        
        $conn->close();
        exit;
    }
    
    // Get filter parameters for analytics
    $subjectFilter = trim($_GET['subject'] ?? '');
    $gradeLevelFilter = trim($_GET['grade_level'] ?? '');
    $quarterFilter = intval($_GET['quarter'] ?? 0);
    
    // Build the base query for analytics - exclude admin users
    $query = "
        SELECT 
            u.id as user_id,
            u.fullname,
            u.subject_taught,
            u.grade_level,
            COUNT(DISTINCT s.id) as section_count,
            COUNT(DISTINCT g.id) as grade_count,
            COALESCE(AVG(g.student_grade), 0) as avg_grade,
            SUM(g.student_grade) as total_grades,
            MAX(g.created_at) as recent_activity
        FROM users u
        LEFT JOIN sections s ON u.id = s.created_by
        LEFT JOIN grades g ON s.id = g.section_id
        WHERE u.role = 'teacher'
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
    
    if ($quarterFilter > 0) {
        $query .= " AND g.quarter = ?";
        $params[] = $quarterFilter;
        $types .= "i";
    }
    
    $query .= " GROUP BY u.id ORDER BY u.fullname";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'user_id' => $row['user_id'],
            'fullname' => $row['fullname'],
            'subject_taught' => $row['subject_taught'],
            'grade_level' => $row['grade_level'],
            'section_count' => intval($row['section_count']),
            'grade_count' => intval($row['grade_count']),
            'avg_grade' => round(floatval($row['avg_grade']), 1),
            'total_grades' => floatval($row['total_grades']),
            'recent_activity' => $row['recent_activity'] ? date('M j, Y', strtotime($row['recent_activity'])) : null
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "data" => $teachers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving teacher data: " . $e->getMessage()
    ]);
}
?>
