-- Migration: Rename quarter to term, reduce grading periods from 4 to 3
-- Run this script against an existing database that still has the "quarter" column
USE proficiency_tracker;

-- Drop the old index that references the quarter column
DROP INDEX idx_grades_section_quarter_year ON grades;

-- Rename the column and update its CHECK constraint (MySQL 8.0.16+ enforces CHECK;
-- older MySQL/MariaDB, including many XAMPP bundles, parses but silently ignores it -
-- application-level validation in the PHP backend is the real enforcement mechanism)
ALTER TABLE grades CHANGE COLUMN quarter term INT NOT NULL CHECK (term BETWEEN 1 AND 3);

-- Recreate the index under the new name
CREATE INDEX idx_grades_section_term_year ON grades(section_id, term, school_year);

-- Note: any existing term = 4 rows now fall outside the new valid range. They are left
-- in place by this migration; review manually and delete if no longer needed:
--   DELETE FROM grades WHERE term = 4;

SELECT 'Term column migration completed. Review any term=4 legacy rows manually.' as message;
