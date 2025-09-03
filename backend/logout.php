<?php
session_start();

// Destroy the session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Check if this is an AJAX request or direct browser access
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode(["success" => true, "message" => "Logged out successfully"]);
} else {
    // Redirect to login page for direct browser access
    header('Location: ../index.html');
    exit;
}
?>
