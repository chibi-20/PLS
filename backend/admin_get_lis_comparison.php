<?php
// Compares each section's persisted official LIS student count against the
// actual number of students with proficiency data entered for a given
// subject/term/school year, section by section.
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
    $term = intval($_GET['term'] ?? 0);

    // Validate required parameters
    if (empty($schoolYear) || empty($subject) || empty($gradeLevel) || $term < 1 || $term > 4) {
        echo json_encode([
            "success" => false,
            "message" => "School year, subject, grade level, and term are required"
        ]);
        exit;
    }

    // Per section: the persisted official LIS count (if any) vs. the deduped
    // count of students with proficiency data entered for this subject/term/year.
    $query = "
        SELECT
            s.id AS section_id,
            s.section_name,
            l.official_count,
            COUNT(DISTINCT CONCAT(g.student_grade, '_', g.gender)) AS system_count
        FROM sections s
        LEFT JOIN lis_student_counts l ON l.section_id = s.id AND l.school_year = ?
        LEFT JOIN grades g ON g.section_id = s.id
            AND g.term = ?
            AND g.school_year = ?
            AND g.created_by IN (SELECT id FROM users WHERE role = 'teacher' AND subject_taught = ?)
        WHERE s.grade_level = ? AND s.school_year = ?
        GROUP BY s.id, s.section_name, l.official_count
        ORDER BY s.section_name
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sissss", $schoolYear, $term, $schoolYear, $subject, $gradeLevel, $schoolYear);
    $stmt->execute();
    $result = $stmt->get_result();

    $sections = [];
    $totalLis = 0;
    $totalSystem = 0;
    $hasAnyLisCount = false;

    while ($row = $result->fetch_assoc()) {
        $lisCount = $row['official_count'] !== null ? intval($row['official_count']) : null;
        $systemCount = intval($row['system_count']);

        if ($lisCount !== null) {
            $hasAnyLisCount = true;
            $totalLis += $lisCount;
        }
        $totalSystem += $systemCount;

        $sections[] = [
            'section_id' => intval($row['section_id']),
            'section_name' => $row['section_name'],
            'lis_count' => $lisCount,
            'system_count' => $systemCount,
            'difference' => $lisCount !== null ? ($systemCount - $lisCount) : null
        ];
    }
    $stmt->close();

    echo json_encode([
        "success" => true,
        "sections" => $sections,
        "totals" => [
            "lis_count" => $hasAnyLisCount ? $totalLis : null,
            "system_count" => $totalSystem,
            "difference" => $hasAnyLisCount ? ($totalSystem - $totalLis) : null
        ],
        "query_info" => [
            "school_year" => $schoolYear,
            "subject" => $subject,
            "grade_level" => $gradeLevel,
            "term" => $term
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving student count data: " . $e->getMessage()
    ]);
}
?>
