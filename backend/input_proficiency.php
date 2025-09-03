<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = $_SESSION['user_id'];

// Get form data
$schoolYear = trim($_POST['school_year'] ?? '');
$sectionName = trim($_POST['section_name'] ?? '');
$quarter = intval($_POST['quarter'] ?? 0);
$boysGrades = $_POST['boys_grades'] ?? [];
$girlsGrades = $_POST['girls_grades'] ?? [];

if (empty($schoolYear) || empty($sectionName) || $quarter < 1 || $quarter > 4) {
    echo json_encode(["success" => false, "message" => "School year, section name and valid quarter are required"]);
    exit;
}

if (empty($boysGrades) && empty($girlsGrades)) {
    echo json_encode(["success" => false, "message" => "At least some grades must be provided"]);
    exit;
}

try {
    // Verify that this section belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND created_by = ?");
    $stmt->bind_param("si", $sectionName, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Section not found or not authorized"]);
        $stmt->close();
        exit;
    }
    
    $sectionRow = $result->fetch_assoc();
    $sectionId = $sectionRow['id'];
    $stmt->close();
    
    // Delete existing grades for this section, quarter, and school year
    $stmt = $conn->prepare("DELETE FROM grades WHERE section_id = ? AND quarter = ? AND created_by = ? AND school_year = ?");
    $stmt->bind_param("iiis", $sectionId, $quarter, $userId, $schoolYear);
    $stmt->execute();
    $stmt->close();
    
    // Insert new grades with school year
    $stmt = $conn->prepare("INSERT INTO grades (section_id, quarter, student_grade, gender, created_by, school_year) VALUES (?, ?, ?, ?, ?, ?)");
    
    $totalInserted = 0;
    
    // Insert boys grades
    foreach ($boysGrades as $grade) {
        $grade = floatval($grade);
        if ($grade > 0 && $grade <= 100) {
            $gender = 'Male';
            $stmt->bind_param("iidsis", $sectionId, $quarter, $grade, $gender, $userId, $schoolYear);
            $stmt->execute();
            $totalInserted++;
        }
    }
    
    // Insert girls grades
    foreach ($girlsGrades as $grade) {
        $grade = floatval($grade);
        if ($grade > 0 && $grade <= 100) {
            $gender = 'Female';
            $stmt->bind_param("iidsis", $sectionId, $quarter, $grade, $gender, $userId, $schoolYear);
            $stmt->execute();
            $totalInserted++;
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "message" => "Grades saved successfully",
        "grades_inserted" => $totalInserted
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error saving grades: " . $e->getMessage()
    ]);
}
?>