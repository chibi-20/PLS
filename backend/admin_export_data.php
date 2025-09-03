<?php
// Force immediate CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="teacher_analytics_export_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Clear any output buffer
while (ob_get_level()) {
    ob_end_clean();
}

try {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "proficiency_tracker");

    if ($conn->connect_error) {
        echo "Error,Database connection failed\n";
        exit;
    }

    // Query for teacher analytics data
    $query = "
        SELECT 
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
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 98 THEN 1 ELSE 0 END) as excellent_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 98 THEN 1 ELSE 0 END) as excellent_female,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 95 AND g.student_grade < 98 THEN 1 ELSE 0 END) as very_good_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 95 AND g.student_grade < 98 THEN 1 ELSE 0 END) as very_good_female,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 90 AND g.student_grade < 95 THEN 1 ELSE 0 END) as good_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 90 AND g.student_grade < 95 THEN 1 ELSE 0 END) as good_female,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 85 AND g.student_grade < 90 THEN 1 ELSE 0 END) as satisfactory_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 85 AND g.student_grade < 90 THEN 1 ELSE 0 END) as satisfactory_female,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 80 AND g.student_grade < 85 THEN 1 ELSE 0 END) as fair_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 80 AND g.student_grade < 85 THEN 1 ELSE 0 END) as fair_female,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade >= 75 AND g.student_grade < 80 THEN 1 ELSE 0 END) as needs_improvement_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade >= 75 AND g.student_grade < 80 THEN 1 ELSE 0 END) as needs_improvement_female,
            SUM(CASE WHEN g.gender = 'Male' AND g.student_grade < 75 THEN 1 ELSE 0 END) as poor_male,
            SUM(CASE WHEN g.gender = 'Female' AND g.student_grade < 75 THEN 1 ELSE 0 END) as poor_female,
            SUM(CASE WHEN g.gender = 'Male' THEN 1 ELSE 0 END) as total_male,
            SUM(CASE WHEN g.gender = 'Female' THEN 1 ELSE 0 END) as total_female
        FROM users u
        LEFT JOIN sections s ON u.id = s.created_by
        LEFT JOIN grades g ON s.id = g.section_id
        WHERE g.id IS NOT NULL AND u.role = 'teacher'
        GROUP BY u.id 
        ORDER BY u.fullname
    ";
    
    $result = $conn->query($query);
    
    // Output CSV headers
    echo "Teacher Name,Subject,Grade Level,Total Students,Average Performance,Gender Split (M/F),";
    echo "Excellent (98-100),Excellent Male,Excellent Female,";
    echo "Very Good (95-97),Very Good Male,Very Good Female,";
    echo "Good (90-94),Good Male,Good Female,";
    echo "Satisfactory (85-89),Satisfactory Male,Satisfactory Female,";
    echo "Fair (80-84),Fair Male,Fair Female,";
    echo "Needs Improvement (75-79),Needs Improvement Male,Needs Improvement Female,";
    echo "Poor (Below 75),Poor Male,Poor Female\n";
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Escape quotes in strings
            $name = '"' . str_replace('"', '""', $row['fullname']) . '"';
            $subject = '"' . str_replace('"', '""', $row['subject_taught']) . '"';
            $grade = '"' . str_replace('"', '""', $row['grade_level']) . '"';
            
            echo $name . ',' . $subject . ',' . $grade . ',';
            echo $row['total_students'] . ',';
            echo number_format($row['avg_performance'], 2) . '%,';
            echo $row['total_male'] . '/' . $row['total_female'] . ',';
            echo $row['excellent_count'] . ',' . $row['excellent_male'] . ',' . $row['excellent_female'] . ',';
            echo $row['very_good_count'] . ',' . $row['very_good_male'] . ',' . $row['very_good_female'] . ',';
            echo $row['good_count'] . ',' . $row['good_male'] . ',' . $row['good_female'] . ',';
            echo $row['satisfactory_count'] . ',' . $row['satisfactory_male'] . ',' . $row['satisfactory_female'] . ',';
            echo $row['fair_count'] . ',' . $row['fair_male'] . ',' . $row['fair_female'] . ',';
            echo $row['needs_improvement_count'] . ',' . $row['needs_improvement_male'] . ',' . $row['needs_improvement_female'] . ',';
            echo $row['poor_count'] . ',' . $row['poor_male'] . ',' . $row['poor_female'] . "\n";
        }
    } else {
        echo "No teacher analytics data found\n";
    }
    
    $conn->close();

} catch (Exception $e) {
    echo "Error,Export failed: " . $e->getMessage() . "\n";
}
?>
