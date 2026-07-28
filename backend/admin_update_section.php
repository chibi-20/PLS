<?php
// Renames a section and/or upserts its official LIS student count for a school year.
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$sectionId = intval($input['section_id'] ?? 0);
$sectionName = isset($input['section_name']) ? trim($input['section_name']) : null;
$schoolYear = trim($input['school_year'] ?? '') ?: '2025-2026';
$officialCount = array_key_exists('official_count', $input) ? $input['official_count'] : null;
$adminId = $_SESSION['user_id'];

if ($sectionId <= 0) {
    echo json_encode(["success" => false, "message" => "Valid section ID is required"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT grade_level, school_year FROM sections WHERE id = ?");
    $stmt->bind_param("i", $sectionId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Section not found"]);
        $stmt->close();
        exit;
    }
    $section = $result->fetch_assoc();
    $stmt->close();

    $conn->begin_transaction();

    if ($sectionName !== null && $sectionName !== '') {
        // Guard the (section_name, grade_level, school_year) unique key with a
        // friendly message instead of a raw DB error
        $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND grade_level = ? AND school_year = ? AND id != ?");
        $stmt->bind_param("sssi", $sectionName, $section['grade_level'], $section['school_year'], $sectionId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            throw new Exception("Another section with that name already exists for {$section['grade_level']}");
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE sections SET section_name = ? WHERE id = ?");
        $stmt->bind_param("si", $sectionName, $sectionId);
        $stmt->execute();
        $stmt->close();
    }

    if ($officialCount !== null && $officialCount !== '') {
        $officialCount = intval($officialCount);
        if ($officialCount < 0) {
            throw new Exception("Official count cannot be negative");
        }
        $stmt = $conn->prepare("
            INSERT INTO lis_student_counts (school_year, section_id, official_count, updated_by)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE official_count = VALUES(official_count), updated_by = VALUES(updated_by)
        ");
        $stmt->bind_param("siii", $schoolYear, $sectionId, $officialCount, $adminId);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Section updated successfully"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error updating section: " . $e->getMessage()]);
}
?>
