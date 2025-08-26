-- Database Setup for Proficiency Learning System (PLS)
-- Execute this script in phpMyAdmin or MySQL command line

-- Create database
CREATE DATABASE IF NOT EXISTS proficiency_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE proficiency_tracker;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    subject_taught VARCHAR(50) NOT NULL,
    grade_level ENUM('Grade 7', 'Grade 8', 'Grade 9', 'Grade 10') NOT NULL
);

-- Create sections table
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_name VARCHAR(100) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create grades table
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    quarter INT NOT NULL CHECK (quarter BETWEEN 1 AND 4),
    student_grade DECIMAL(5,2) NOT NULL CHECK (student_grade BETWEEN 0 AND 100),
    gender ENUM('Male', 'Female') NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: ilovejacobo)
-- You can change the password after first login
INSERT INTO users (username, password, role, fullname, subject_taught, grade_level) 
VALUES ('307901', '$2y$10$NyOYCdtDdo2p3ZmwHVKgmenIq0WnEpXwf5hOVdL8wbCxTO44qWBjC', 'admin', 'Administrator', 'Administration', 'Grade 7');

-- Display success message
SELECT 'Database setup completed successfully!' as message;
SELECT 'Default admin credentials:' as info;
SELECT 'Username: 307901' as username;
SELECT 'Password: ilovejacobo' as password;
SELECT 'Please change the admin password after first login!' as warning;
