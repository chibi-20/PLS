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
-- Sections are canonical and admin-managed, scoped to a grade level and school year.
-- created_by records which admin created the section (not an ownership link -
-- see teacher_sections for the many-to-many link between teachers and sections).
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_name VARCHAR(100) NOT NULL,
    grade_level ENUM('Grade 7', 'Grade 8', 'Grade 9', 'Grade 10') NOT NULL,
    created_by INT NULL,
    school_year VARCHAR(20) DEFAULT '2025-2026',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_section_grade_year (section_name, grade_level, school_year)
);

-- Links teachers to the sections they teach (many teachers per section, many
-- sections per teacher)
CREATE TABLE teacher_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    section_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_teacher_section (teacher_id, section_id)
);

-- Create grades table
-- term: 1-3 = Term 1/2/3, 4 = Final Grade (entered directly by the teacher, not computed)
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    term INT NOT NULL CHECK (term BETWEEN 1 AND 4),
    student_grade DECIMAL(5,2) NOT NULL CHECK (student_grade BETWEEN 0 AND 100),
    gender ENUM('Male', 'Female') NOT NULL,
    created_by INT NOT NULL,
    school_year VARCHAR(20) DEFAULT '2025-2026',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Official DepEd LIS student count per section, per school year (admin-entered)
CREATE TABLE lis_student_counts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_year VARCHAR(20) NOT NULL,
    section_id INT NOT NULL,
    official_count INT NOT NULL CHECK (official_count >= 0),
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_year_section (school_year, section_id)
);

-- Manually-entered whole-school student total, per school year (admin-entered)
CREATE TABLE school_student_totals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_year VARCHAR(20) NOT NULL UNIQUE,
    total_students INT NOT NULL CHECK (total_students >= 0),
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
