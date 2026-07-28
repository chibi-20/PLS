<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

try {
    $gradeLevel = trim($_GET['grade_level'] ?? '');
    $schoolYear = trim($_GET['school_year'] ?? '') ?: '2025-2026';

    $query = "
        SELECT
            s.id,
            s.section_name,
            s.grade_level,
            COUNT(DISTINCT ts.teacher_id) AS teacher_count,
            l.official_count
        FROM sections s
        LEFT JOIN teacher_sections ts ON ts.section_id = s.id
        LEFT JOIN lis_student_counts l ON l.section_id = s.id AND l.school_year = ?
        WHERE s.school_year = ?
    ";
    $params = [$schoolYear, $schoolYear];
    $types = "ss";

    if ($gradeLevel) {
        $query .= " AND s.grade_level = ?";
        $params[] = $gradeLevel;
        $types .= "s";
    }

    $query .= " GROUP BY s.id, s.section_name, s.grade_level, l.official_count ORDER BY s.grade_level, s.section_name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = [
            "id" => intval($row['id']),
            "section_name" => $row['section_name'],
            "grade_level" => $row['grade_level'],
            "teacher_count" => intval($row['teacher_count']),
            "official_count" => $row['official_count'] === null ? null : intval($row['official_count'])
        ];
    }
    $stmt->close();

    echo json_encode(["success" => true, "sections" => $sections]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error retrieving sections: " . $e->getMessage()]);
}
?>
