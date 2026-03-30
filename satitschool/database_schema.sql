CREATE DATABASE IF NOT EXISTS satitschool DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE satitschool;

-- 1. ระบบ Login และ RBAC
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 2. ข้อมูลพื้นฐาน
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    line_id VARCHAR(50),
    profile_pic VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    dob DATE,
    gender ENUM('M', 'F', 'Other'),
    address TEXT,
    parent_phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year YEAR NOT NULL,
    term TINYINT NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    UNIQUE(year, term)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_id INT NOT NULL,
    room_name VARCHAR(50) NOT NULL,
    advisor_id INT NULL,
    academic_year_id INT NOT NULL,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE RESTRICT,
    FOREIGN KEY (advisor_id) REFERENCES teachers(id) ON DELETE SET NULL,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_classrooms (
    student_id INT NOT NULL,
    classroom_id INT NOT NULL,
    roll_number INT,
    PRIMARY KEY (student_id, classroom_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    credit DECIMAL(3,1) NOT NULL DEFAULT 1.0,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- 3. ตารางเรียน
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. บันทึกผลการเรียน
CREATE TABLE IF NOT EXISTS academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    midterm_score DECIMAL(5,2) DEFAULT 0.00,
    final_score DECIMAL(5,2) DEFAULT 0.00,
    total_score DECIMAL(5,2) GENERATED ALWAYS AS (midterm_score + final_score) STORED,
    grade VARCHAR(5),
    recorded_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (student_id, subject_id, academic_year_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. บันทึกเวลาเรียน
CREATE TABLE IF NOT EXISTS attendances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    schedule_id INT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'leave') NOT NULL,
    remarks VARCHAR(255),
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE (student_id, schedule_id, date)
) ENGINE=InnoDB;

-- 6. Dashboard Indexes
-- สร้างถ้าไม่มี index (ลด error ถ้าเคยรันไปแล้ว)
SELECT COUNT(1) INTO @index_exists FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'satitschool' AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_username';
SET @query = IF(@index_exists = 0, 'CREATE INDEX idx_users_username ON users(username)', 'DO 0'); PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(1) INTO @index_exists FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'satitschool' AND TABLE_NAME = 'students' AND INDEX_NAME = 'idx_students_code';
SET @query = IF(@index_exists = 0, 'CREATE INDEX idx_students_code ON students(student_code)', 'DO 0'); PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(1) INTO @index_exists FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'satitschool' AND TABLE_NAME = 'attendances' AND INDEX_NAME = 'idx_attendances_date';
SET @query = IF(@index_exists = 0, 'CREATE INDEX idx_attendances_date ON attendances(date)', 'DO 0'); PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(1) INTO @index_exists FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'satitschool' AND TABLE_NAME = 'schedules' AND INDEX_NAME = 'idx_schedules_class';
SET @query = IF(@index_exists = 0, 'CREATE INDEX idx_schedules_class ON schedules(classroom_id, day_of_week)', 'DO 0'); PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;
