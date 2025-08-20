<?php
// db.php
$host = "localhost";
$user = "root"; // default XAMPP MySQL username
$pass = "";     // default XAMPP MySQL password (blank)
$dbname = "proficiency_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
