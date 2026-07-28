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
        // Delete this teacher's own grade entries (sections themselves are
        // admin-owned and may still be assigned to other teachers, so they stay)
        $deleteGradesStmt = $conn->prepare("DELETE FROM grades WHERE created_by = ?");
        $deleteGradesStmt->bind_param("i", $teacherId);
        $deleteGradesStmt->execute();
        $deletedGrades = $deleteGradesStmt->affected_rows;
        $deleteGradesStmt->close();

        // Unlink this teacher from their assigned sections
        $deleteLinksStmt = $conn->prepare("DELETE FROM teacher_sections WHERE teacher_id = ?");
        $deleteLinksStmt->bind_param("i", $teacherId);
        $deleteLinksStmt->execute();
        $unlinkedSections = $deleteLinksStmt->affected_rows;
        $deleteLinksStmt->close();

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
                    "sections_unlinked" => $unlinkedSections,
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