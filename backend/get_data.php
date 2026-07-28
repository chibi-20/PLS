<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Debug: Log the session info
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$userId = $_SESSION['user_id'];
error_log("User ID: " . $userId); // Debug log

// Get query parameters
$schoolYear = trim($_GET['school_year'] ?? '');
$sectionName = trim($_GET['section'] ?? '');
$term = intval($_GET['term'] ?? 0);

try {
    if ($sectionName && $term > 0) {
        // Get specific section and term data
        $query = "
            SELECT g.student_grade, g.gender
            FROM grades g
            JOIN sections s ON g.section_id = s.id
            WHERE s.section_name = ? AND g.term = ? AND g.created_by = ?
        ";
        $params = [$sectionName, $term, $userId];
        $types = "sii";
        
        if ($schoolYear) {
            $query .= " AND g.school_year = ?";
            $params[] = $schoolYear;
            $types .= "s";
        }        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $boys = [];
        $girls = [];
        
        while ($row = $result->fetch_assoc()) {
            if ($row['gender'] === 'Male') {
                $boys[] = floatval($row['student_grade']);
            } else {
                $girls[] = floatval($row['student_grade']);
            }
        }
        
        $stmt->close();
        
        echo json_encode([
            "success" => true,
            "section" => $sectionName,
            "term" => $term,
            "boys" => $boys,
            "girls" => $girls
        ]);

    } else {
        // Get all data for the current user
        error_log("Getting all data for user: " . $userId); // Debug log

        $query = "
            SELECT s.section_name, g.term, g.student_grade, g.gender
            FROM grades g
            JOIN sections s ON g.section_id = s.id
            WHERE g.created_by = ?
        ";
        $params = [$userId];
        $types = "i";
        
        if ($schoolYear) {
            $query .= " AND g.school_year = ?";
            $params[] = $schoolYear;
            $types .= "s";
        }
        
        $query .= " ORDER BY s.section_name, g.term";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        $rowCount = 0;

        while ($row = $result->fetch_assoc()) {
            $rowCount++;
            $key = $row['section_name'] . '_T' . $row['term'];

            if (!isset($data[$key])) {
                $data[$key] = [
                    'section' => $row['section_name'],
                    'term' => $row['term'],
                    'boys' => [],
                    'girls' => []
                ];
            }

            if ($row['gender'] === 'Male') {
                $data[$key]['boys'][] = floatval($row['student_grade']);
            } else {
                $data[$key]['girls'][] = floatval($row['student_grade']);
            }
        }

        error_log("Found " . $rowCount . " rows, grouped into " . count($data) . " sections/terms"); // Debug log
        
        $stmt->close();
        
        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving data: " . $e->getMessage()
    ]);
}
?>