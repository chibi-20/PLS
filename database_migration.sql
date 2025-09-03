-- Database Migration Script: Add School Year Support
-- Run this script to add school year functionality to your existing database

USE proficiency_tracker;

-- Add school_year column to sections table
ALTER TABLE sections 
ADD COLUMN school_year VARCHAR(20) DEFAULT '2025-2026' AFTER created_by;

-- Add school_year column to grades table  
ALTER TABLE grades 
ADD COLUMN school_year VARCHAR(20) DEFAULT '2025-2026' AFTER created_by;

-- Update existing records with default school year
UPDATE sections SET school_year = '2025-2026' WHERE school_year IS NULL;
UPDATE grades SET school_year = '2025-2026' WHERE school_year IS NULL;

-- Add indexes for better performance
CREATE INDEX idx_sections_school_year ON sections(school_year);
CREATE INDEX idx_grades_school_year ON grades(school_year);
CREATE INDEX idx_grades_section_quarter_year ON grades(section_id, quarter, school_year);

SELECT 'School year columns added successfully!' as message;
SELECT 'All existing data has been assigned to school year 2025-2026' as info;
