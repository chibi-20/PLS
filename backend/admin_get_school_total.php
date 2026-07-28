<?php
// Whole-school student total for a school year, computed as the sum of each
// section's official LIS count (not manually entered - see database_migration_sections_lis.sql
// for the older school_student_totals table this replaces).
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

try {
    $schoolYear = trim($_GET['school_year'] ?? '') ?: '2025-2026';

    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(l.official_count), 0) AS total_students,
            COUNT(l.id) AS sections_with_count,
            (SELECT COUNT(*) FROM sections WHERE school_year = ?) AS sections_total
        FROM lis_student_counts l
        JOIN sections s ON s.id = l.section_id
        WHERE l.school_year = ?
    ");
    $stmt->bind_param("ss", $schoolYear, $schoolYear);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        "success" => true,
        "school_year" => $schoolYear,
        "total_students" => intval($row['total_students']),
        "sections_with_count" => intval($row['sections_with_count']),
        "sections_total" => intval($row['sections_total'])
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error retrieving school total: " . $e->getMessage()]);
}
?>
