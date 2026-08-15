<?php
// Public endpoint: lists the canonical sections for a grade level (used by the
// registration page, before login). If a teacher is logged in and no grade_level
// is supplied, their own grade level is used, and each section is flagged with
// whether they are already linked to it (used by the teacher dashboard's
// "Manage Sections" tab).
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$gradeLevel      = trim($_GET['grade_level'] ?? '');
$schoolYearParam = trim($_GET['school_year'] ?? '');
$teacherId       = null;

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'teacher') {
    $teacherId = intval($_SESSION['user_id']);
    if (empty($gradeLevel)) {
        $stmt = $conn->prepare("SELECT grade_level FROM users WHERE id = ?");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $gradeLevel = $row['grade_level'] ?? '';
    }
}

if (empty($gradeLevel)) {
    echo json_encode(["success" => false, "message" => "Grade level is required"]);
    exit;
}

try {
    if ($schoolYearParam !== '') {
        // Explicit school year requested (admin dashboard, LIS comparison, etc.)
        $schoolYear = $schoolYearParam;
        if ($teacherId) {
            $stmt = $conn->prepare("
                SELECT s.id, s.section_name,
                       (ts.id IS NOT NULL) AS assigned
                FROM sections s
                LEFT JOIN teacher_sections ts ON ts.section_id = s.id AND ts.teacher_id = ?
                WHERE s.grade_level = ? AND s.school_year = ?
                ORDER BY s.section_name
            ");
            $stmt->bind_param("iss", $teacherId, $gradeLevel, $schoolYear);
        } else {
            $stmt = $conn->prepare("
                SELECT s.id, s.section_name, 0 AS assigned
                FROM sections s
                WHERE s.grade_level = ? AND s.school_year = ?
                ORDER BY s.section_name
            ");
            $stmt->bind_param("ss", $gradeLevel, $schoolYear);
        }
    } elseif ($teacherId) {
        // Teacher dashboard: use most recent school year so new sections appear
        $sy_stmt = $conn->prepare("SELECT school_year FROM sections WHERE grade_level = ? ORDER BY school_year DESC LIMIT 1");
        $sy_stmt->bind_param("s", $gradeLevel);
        $sy_stmt->execute();
        $sy_row = $sy_stmt->get_result()->fetch_assoc();
        $sy_stmt->close();
        $schoolYear = $sy_row['school_year'] ?? '2025-2026';
        $stmt = $conn->prepare("
            SELECT s.id, s.section_name,
                   (ts.id IS NOT NULL) AS assigned
            FROM sections s
            LEFT JOIN teacher_sections ts ON ts.section_id = s.id AND ts.teacher_id = ?
            WHERE s.grade_level = ? AND s.school_year = ?
            ORDER BY s.section_name
        ");
        $stmt->bind_param("iss", $teacherId, $gradeLevel, $schoolYear);
    } else {
        // Public registration: show all sections for this grade regardless of school year,
        // deduplicated by name (take the highest id so new imports always surface).
        $stmt = $conn->prepare("
            SELECT MAX(s.id) AS id, s.section_name, 0 AS assigned
            FROM sections s
            WHERE s.grade_level = ?
            GROUP BY s.section_name
            ORDER BY s.section_name
        ");
        $stmt->bind_param("s", $gradeLevel);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = [
            "id" => intval($row['id']),
            "section_name" => $row['section_name'],
            "assigned" => (bool) $row['assigned']
        ];
    }
    $stmt->close();

    echo json_encode(["success" => true, "sections" => $sections]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error fetching sections: " . $e->getMessage()]);
}
?>
