<?php
// Database migration to add school_year field
// Run this script once to update your database structure

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "proficiency_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Add school_year column to sections table
    $sql1 = "ALTER TABLE sections ADD COLUMN school_year VARCHAR(10) DEFAULT '2025-2026'";
    if ($conn->query($sql1)) {
        echo "✅ Added school_year column to sections table\n";
    } else {
        echo "❌ Error adding school_year to sections: " . $conn->error . "\n";
    }

    // Add school_year column to grades table
    $sql2 = "ALTER TABLE grades ADD COLUMN school_year VARCHAR(10) DEFAULT '2025-2026'";
    if ($conn->query($sql2)) {
        echo "✅ Added school_year column to grades table\n";
    } else {
        echo "❌ Error adding school_year to grades: " . $conn->error . "\n";
    }

    // Update existing records to have current school year
    $currentYear = "2025-2026";
    
    $sql3 = "UPDATE sections SET school_year = '$currentYear' WHERE school_year IS NULL";
    if ($conn->query($sql3)) {
        echo "✅ Updated existing sections with school year $currentYear\n";
    }

    $sql4 = "UPDATE grades SET school_year = '$currentYear' WHERE school_year IS NULL";
    if ($conn->query($sql4)) {
        echo "✅ Updated existing grades with school year $currentYear\n";
    }

    echo "\n🎉 Database migration completed successfully!\n";
    echo "You can now use school year filtering in your application.\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
