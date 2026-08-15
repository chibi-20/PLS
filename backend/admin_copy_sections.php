<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$adminId = $_SESSION['user_id'];

// --- Preview mode (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $fromYear = trim($_GET['from_year'] ?? '');
    $toYear   = trim($_GET['to_year']   ?? '');

    if (empty($fromYear) || empty($toYear) || $fromYear === $toYear) {
        echo json_encode(["success" => false, "message" => "Invalid school years"]);
        exit;
    }

    // Count sections in source year
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM sections WHERE school_year = ?");
    $stmt->bind_param("s", $fromYear);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total == 0) {
        echo json_encode(["success" => false, "message" => "No sections found in {$fromYear}."]);
        exit;
    }

    // Count how many already exist in destination
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS existing
        FROM sections src
        JOIN sections dst
          ON dst.section_name = src.section_name
         AND dst.grade_level  = src.grade_level
         AND dst.school_year  = ?
        WHERE src.school_year = ?
    ");
    $stmt->bind_param("ss", $toYear, $fromYear);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc()['existing'];
    $stmt->close();

    echo json_encode([
        "success"  => true,
        "total"    => intval($total),
        "existing" => intval($existing),
        "new"      => intval($total) - intval($existing),
    ]);
    exit;
}

// --- Copy mode (POST) ---
$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$fromYear = trim($input['from_year'] ?? '');
$toYear   = trim($input['to_year']   ?? '');

if (empty($fromYear) || empty($toYear) || $fromYear === $toYear) {
    echo json_encode(["success" => false, "message" => "Invalid school years"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT section_name, grade_level FROM sections WHERE school_year = ?");
    $stmt->bind_param("s", $fromYear);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
        echo json_encode(["success" => false, "message" => "No sections found in {$fromYear}."]);
        exit;
    }

    $conn->begin_transaction();

    $checkStmt  = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND grade_level = ? AND school_year = ?");
    $insertStmt = $conn->prepare("INSERT INTO sections (section_name, grade_level, created_by, school_year) VALUES (?, ?, ?, ?)");
    $copied  = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $checkStmt->bind_param("sss", $row['section_name'], $row['grade_level'], $toYear);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $skipped++;
            continue;
        }

        $insertStmt->bind_param("ssis", $row['section_name'], $row['grade_level'], $adminId, $toYear);
        $insertStmt->execute();
        $copied++;
    }

    $checkStmt->close();
    $insertStmt->close();
    $conn->commit();

    echo json_encode([
        "success" => true,
        "copied"  => $copied,
        "skipped" => $skipped,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
