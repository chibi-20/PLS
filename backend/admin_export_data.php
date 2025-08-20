<?php
session_start();
require_once 'db.php';

// Check for admin access (for production, uncomment this)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(["success" => false, "message" => "Admin access required"]);
//     exit;
// }

try {
    // Get filter parameters
    $exportType = trim($_GET['type'] ?? 'all'); // 'all', 'subject', 'grade'
    $subjectFilter = trim($_GET['subject'] ?? '');
    $gradeLevelFilter = trim($_GET['grade_level'] ?? '');
    $format = trim($_GET['format'] ?? 'csv'); // 'csv' or 'json'
    
    // Build the comprehensive export query
    $query = "
        SELECT 
            u.fullname as teacher_name,
            u.subject_taught,
            u.grade_level,
            s.section_name,
            g.quarter,
            g.student_grade,
            g.gender,
            g.created_at,
            CASE 
                WHEN g.student_grade >= 98 THEN 'Excellent'
                WHEN g.student_grade >= 95 THEN 'Very Good'
                WHEN g.student_grade >= 90 THEN 'Good'
                WHEN g.student_grade >= 85 THEN 'Satisfactory'
                WHEN g.student_grade >= 80 THEN 'Fair'
                WHEN g.student_grade >= 75 THEN 'Needs Improvement'
                ELSE 'Poor'
            END as proficiency_level
        FROM users u
        JOIN sections s ON u.id = s.created_by
        JOIN grades g ON s.id = g.section_id
        WHERE u.role = 'teacher'
    ";
    
    $params = [];
    $types = "";
    
    // Add filters based on export type
    if ($exportType === 'subject' && $subjectFilter) {
        $query .= " AND u.subject_taught = ?";
        $params[] = $subjectFilter;
        $types .= "s";
    }
    
    if ($exportType === 'grade' && $gradeLevelFilter) {
        $query .= " AND u.grade_level = ?";
        $params[] = $gradeLevelFilter;
        $types .= "s";
    }
    
    if ($exportType === 'subject_grade' && $subjectFilter && $gradeLevelFilter) {
        $query .= " AND u.subject_taught = ? AND u.grade_level = ?";
        $params[] = $subjectFilter;
        $params[] = $gradeLevelFilter;
        $types .= "ss";
    }
    
    $query .= " ORDER BY u.subject_taught, u.grade_level, s.section_name, g.quarter, g.created_at";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    
    // Generate filename
    $filename = 'proficiency_data_' . date('Y-m-d_H-i-s');
    if ($exportType === 'subject' && $subjectFilter) {
        $filename = 'proficiency_' . str_replace(' ', '_', $subjectFilter) . '_' . date('Y-m-d_H-i-s');
    } elseif ($exportType === 'grade' && $gradeLevelFilter) {
        $filename = 'proficiency_' . str_replace(' ', '_', $gradeLevelFilter) . '_' . date('Y-m-d_H-i-s');
    }
    
    if ($format === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        
    } else {
        // JSON format
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        
        echo json_encode([
            "export_info" => [
                "generated_at" => date('Y-m-d H:i:s'),
                "export_type" => $exportType,
                "filters" => [
                    "subject" => $subjectFilter,
                    "grade_level" => $gradeLevelFilter
                ],
                "total_records" => count($data)
            ],
            "data" => $data
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => "Error exporting data: " . $e->getMessage()
    ]);
}
?>
