<?php
// Generate password hash for the admin account
$password = 'ilovejacobo';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo "Hashed password: " . $hashedPassword . "\n";

// Database connection
$host = 'localhost';
$db   = 'proficiency_tracker';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute(['307901']);
    
    if ($checkStmt->fetch()) {
        echo "User 307901 already exists. Updating password...\n";
        
        // Update existing user
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin', fullname = 'Administrator' WHERE username = ?");
        $updateStmt->execute([$hashedPassword, '307901']);
        echo "User 307901 updated successfully!\n";
    } else {
        echo "Creating new admin user...\n";
        
        // Insert new user
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, role, fullname, subject_taught, grade_level) VALUES (?, ?, 'admin', 'Administrator', 'Administration', 'Grade 7')");
        $insertStmt->execute(['307901', $hashedPassword]);
        echo "Admin user 307901 created successfully!\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
