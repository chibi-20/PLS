<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$sectionName = trim($_POST['section_name'] ?? '');
$gradeLevel = trim($_POST['grade_level'] ?? '');
$schoolYear = trim($_POST['school_year'] ?? '') ?: '2025-2026';
$adminId = $_SESSION['user_id'];

$validGrades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

if (empty($sectionName) || empty($gradeLevel)) {
    echo json_encode(["success" => false, "message" => "Section name and grade level are required"]);
    exit;
}

if (!in_array($gradeLevel, $validGrades, true)) {
    echo json_encode(["success" => false, "message" => "Invalid grade level"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND grade_level = ? AND school_year = ?");
    $stmt->bind_param("sss", $sectionName, $gradeLevel, $schoolYear);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "This section already exists for that grade level and school year"]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO sections (section_name, grade_level, created_by, school_year) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $sectionName, $gradeLevel, $adminId, $schoolYear);
    $stmt->execute();
    $sectionId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Section added successfully",
        "section" => [
            "id" => $sectionId,
            "section_name" => $sectionName,
            "grade_level" => $gradeLevel
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error adding section: " . $e->getMessage()]);
}
?>
