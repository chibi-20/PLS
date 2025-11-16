<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check for admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$teacherId = intval($input['teacher_id'] ?? 0);

if ($teacherId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Valid teacher ID is required"
    ]);
    exit;
}

try {
    // Check if teacher exists and is actually a teacher
    $checkStmt = $conn->prepare("SELECT role, fullname FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $teacherId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Teacher not found"
        ]);
        $checkStmt->close();
        exit;
    }
    
    $teacher = $checkResult->fetch_assoc();
    if ($teacher['role'] !== 'teacher') {
        echo json_encode([
            "success" => false,
            "message" => "Cannot delete non-teacher accounts"
        ]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get all section IDs created by this teacher
        $sectionStmt = $conn->prepare("SELECT id FROM sections WHERE created_by = ?");
        $sectionStmt->bind_param("i", $teacherId);
        $sectionStmt->execute();
        $sectionResult = $sectionStmt->get_result();
        
        $sectionIds = [];
        while ($row = $sectionResult->fetch_assoc()) {
            $sectionIds[] = $row['id'];
        }
        $sectionStmt->close();
        
        // Delete grades for all sections created by this teacher
        if (!empty($sectionIds)) {
            $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
            $deleteGradesStmt = $conn->prepare("DELETE FROM grades WHERE section_id IN ($placeholders)");
            $deleteGradesStmt->bind_param(str_repeat('i', count($sectionIds)), ...$sectionIds);
            $deleteGradesStmt->execute();
            $deletedGrades = $deleteGradesStmt->affected_rows;
            $deleteGradesStmt->close();
        } else {
            $deletedGrades = 0;
        }
        
        // Delete all sections created by this teacher
        $deleteSectionsStmt = $conn->prepare("DELETE FROM sections WHERE created_by = ?");
        $deleteSectionsStmt->bind_param("i", $teacherId);
        $deleteSectionsStmt->execute();
        $deletedSections = $deleteSectionsStmt->affected_rows;
        $deleteSectionsStmt->close();
        
        // Delete the teacher account
        $deleteTeacherStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $deleteTeacherStmt->bind_param("i", $teacherId);
        $deleteTeacherStmt->execute();
        $deletedTeacher = $deleteTeacherStmt->affected_rows;
        $deleteTeacherStmt->close();
        
        if ($deletedTeacher > 0) {
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                "success" => true,
                "message" => "Teacher account deleted successfully",
                "details" => [
                    "teacher_name" => $teacher['fullname'],
                    "sections_deleted" => $deletedSections,
                    "grades_deleted" => $deletedGrades
                ]
            ]);
        } else {
            // Rollback if teacher wasn't deleted
            $conn->rollback();
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete teacher account"
            ]);
        }
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>