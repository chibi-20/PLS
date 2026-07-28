-- Database Migration: Canonical grade-level sections + persisted LIS student counts
-- Run this after database_migration_final_grade.sql
--
-- What this changes:
--   1. Sections become canonical, grade-level-scoped entities managed by the admin
--      (instead of free-text rows owned 1:1 by the teacher who typed them at registration).
--   2. teacher_sections links teachers to the sections they teach (many-to-many),
--      replacing sections.created_by as the "who teaches this" relationship.
--   3. lis_student_counts stores the official DepEd LIS headcount per section/school year,
--      so it no longer has to be retyped on every LIS Comparison check.
--   4. school_student_totals stores the manually-entered whole-school total per school year.

USE proficiency_tracker;

-- 1. sections: add grade_level, backfilled from the creating teacher's grade level
ALTER TABLE sections
  ADD COLUMN grade_level ENUM('Grade 7', 'Grade 8', 'Grade 9', 'Grade 10') NULL AFTER section_name;

UPDATE sections s
JOIN users u ON s.created_by = u.id
SET s.grade_level = u.grade_level
WHERE s.grade_level IS NULL;

-- Any section that still has no grade_level (creator missing/unknown) defaults to Grade 7
-- so the NOT NULL constraint below can be applied; adjust manually afterward if needed.
UPDATE sections SET grade_level = 'Grade 7' WHERE grade_level IS NULL;

ALTER TABLE sections MODIFY grade_level ENUM('Grade 7', 'Grade 8', 'Grade 9', 'Grade 10') NOT NULL;

-- created_by now means "admin who added this section" (nullable, not an ownership link)
ALTER TABLE sections DROP FOREIGN KEY sections_ibfk_1;
ALTER TABLE sections MODIFY created_by INT NULL;
ALTER TABLE sections ADD CONSTRAINT sections_created_by_fk
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE sections ADD UNIQUE KEY uniq_section_grade_year (section_name, grade_level, school_year);

-- 2. teacher_sections: many-to-many between teachers and the sections they teach
CREATE TABLE teacher_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    section_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_teacher_section (teacher_id, section_id)
);

-- Backfill: every teacher who used to "own" a section is now linked to it
INSERT INTO teacher_sections (teacher_id, section_id)
SELECT created_by, id FROM sections WHERE created_by IS NOT NULL;

-- 3. lis_student_counts: official per-section LIS headcount, admin-editable
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

-- 4. school_student_totals: manually-entered whole-school total, per school year
CREATE TABLE school_student_totals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_year VARCHAR(20) NOT NULL UNIQUE,
    total_students INT NOT NULL CHECK (total_students >= 0),
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

SELECT 'Sections/LIS migration completed successfully!' as message;
