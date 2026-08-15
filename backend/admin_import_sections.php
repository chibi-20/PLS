<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$gradeLevel  = trim($input['grade_level'] ?? '');
$schoolYear  = trim($input['school_year'] ?? '') ?: '2025-2026';
$sections    = $input['sections'] ?? [];
$adminId     = $_SESSION['user_id'];

$validGrades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

if (!in_array($gradeLevel, $validGrades, true)) {
    echo json_encode(["success" => false, "message" => "Invalid grade level"]);
    exit;
}

if (empty($sections) || !is_array($sections)) {
    echo json_encode(["success" => false, "message" => "No sections provided"]);
    exit;
}

$created = 0;
$updated = 0;

try {
    $conn->begin_transaction();

    foreach ($sections as $row) {
        $sectionName   = trim($row['section_name'] ?? '');
        $officialCount = (isset($row['official_count']) && $row['official_count'] !== null)
                         ? intval($row['official_count']) : null;

        if ($sectionName === '') continue;

        // Get or create section
        $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND grade_level = ? AND school_year = ?");
        $stmt->bind_param("sss", $sectionName, $gradeLevel, $schoolYear);
        $stmt->execute();
        $result    = $stmt->get_result();
        $existing  = $result->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $sectionId = $existing['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO sections (section_name, grade_level, created_by, school_year) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $sectionName, $gradeLevel, $adminId, $schoolYear);
            $stmt->execute();
            $sectionId = $stmt->insert_id;
            $stmt->close();
            $created++;
        }

        if ($officialCount !== null && $officialCount >= 0) {
            $stmt = $conn->prepare("
                INSERT INTO lis_student_counts (school_year, section_id, official_count, updated_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE official_count = VALUES(official_count), updated_by = VALUES(updated_by)
            ");
            $stmt->bind_param("siii", $schoolYear, $sectionId, $officialCount, $adminId);
            $stmt->execute();
            $stmt->close();
            $updated++;
        }
    }

    $conn->commit();
    echo json_encode([
        "success" => true,
        "message" => "Import successful",
        "created" => $created,
        "updated" => $updated
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Import error: " . $e->getMessage()]);
}
?>
