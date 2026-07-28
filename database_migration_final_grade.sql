-- Migration: Allow a 4th grading period value on grades.term to represent "Final Grade"
-- Run this against a database that already applied database_migration_term_rename.sql
-- (i.e. grades.term currently has CHECK (term BETWEEN 1 AND 3))
--
-- Final Grade is entered directly by the teacher (same input flow as Term 1/2/3),
-- not computed by the system. It is stored as term = 4.
--
-- Note: on MariaDB, the inline column-level CHECK created by the term-rename migration
-- cannot be reliably dropped in place with ALTER TABLE ... DROP CONSTRAINT (it silently
-- targets the wrong constraint object or errors depending on version). This migration
-- rebuilds the table instead, which is safe and preserves all existing rows.
USE proficiency_tracker;

RENAME TABLE grades TO grades_old_term_check;

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

INSERT INTO grades (id, section_id, term, student_grade, gender, created_by, school_year, created_at)
SELECT id, section_id, term, student_grade, gender, created_by, school_year, created_at FROM grades_old_term_check;

CREATE INDEX idx_grades_school_year ON grades(school_year);
CREATE INDEX idx_grades_section_term_year ON grades(section_id, term, school_year);

DROP TABLE grades_old_term_check;

SELECT 'Final Grade support added (term value 4 = Final Grade).' as message;
