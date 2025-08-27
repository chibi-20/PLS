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
    // Get filter parameters
    $schoolYear = trim($_GET['school_year'] ?? '');
    $subject = trim($_GET['subject'] ?? '');
    $gradeLevel = trim($_GET['grade_level'] ?? '');
    $quarter = intval($_GET['quarter'] ?? 0);
    
    // Validate required parameters
    if (empty($schoolYear) || empty($subject) || empty($gradeLevel) || $quarter < 1 || $quarter > 4) {
        echo json_encode([
            "success" => false, 
            "message" => "School year, subject, grade level, and quarter are required"
        ]);
        exit;
    }
    
    // Query to get student count and section breakdown for the specified criteria
    $query = "
        SELECT 
            s.section_name,
            COUNT(g.id) as student_count
        FROM users u
        JOIN sections s ON u.id = s.created_by
        JOIN grades g ON s.id = g.section_id
        WHERE u.role = 'teacher'
        AND u.subject_taught = ?
        AND u.grade_level = ?
        AND g.quarter = ?
        AND g.school_year = ?
        GROUP BY s.id, s.section_name
        ORDER BY s.section_name
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssis", $subject, $gradeLevel, $quarter, $schoolYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totalStudents = 0;
    $sectionBreakdown = [];
    
    while ($row = $result->fetch_assoc()) {
        $students = intval($row['student_count']);
        $totalStudents += $students;
        $sectionBreakdown[] = [
            'section_name' => $row['section_name'],
            'student_count' => $students
        ];
    }
    
    $stmt->close();
    
    // Additional query to get unique students (to avoid counting duplicates if a student has multiple grades)
    $uniqueQuery = "
        SELECT COUNT(DISTINCT CONCAT(g.section_id, '_', g.student_grade, '_', g.gender)) as unique_count
        FROM users u
        JOIN sections s ON u.id = s.created_by
        JOIN grades g ON s.id = g.section_id
        WHERE u.role = 'teacher'
        AND u.subject_taught = ?
        AND u.grade_level = ?
        AND g.quarter = ?
        AND g.school_year = ?
    ";
    
    $uniqueStmt = $conn->prepare($uniqueQuery);
    $uniqueStmt->bind_param("ssis", $subject, $gradeLevel, $quarter, $schoolYear);
    $uniqueStmt->execute();
    $uniqueResult = $uniqueStmt->get_result();
    $uniqueRow = $uniqueResult->fetch_assoc();
    $uniqueStudents = intval($uniqueRow['unique_count']);
    $uniqueStmt->close();
    
    echo json_encode([
        "success" => true,
        "student_count" => $uniqueStudents, // Use unique count to avoid duplicates
        "total_records" => $totalStudents, // Total grade records
        "section_breakdown" => $sectionBreakdown,
        "query_info" => [
            "school_year" => $schoolYear,
            "subject" => $subject,
            "grade_level" => $gradeLevel,
            "quarter" => $quarter
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving student count data: " . $e->getMessage()
    ]);
}
?>
