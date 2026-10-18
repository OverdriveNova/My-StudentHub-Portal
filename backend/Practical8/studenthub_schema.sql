-- ============================================================
--  StudentHub Portal - Database Schema
--  Practical 8: MySQL Schema Design, ER Model, PDO Connectivity
--  Student: RUDRA MODI (25DCS058) | Course: ITUE203
-- ============================================================

CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;

-- TABLE: users
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)    NOT NULL,
    email        VARCHAR(150)    NOT NULL UNIQUE,
    password     VARCHAR(255)    NOT NULL,
    role         ENUM('student','admin') NOT NULL DEFAULT 'student',
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- TABLE: students
CREATE TABLE IF NOT EXISTS students (
    id             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED   NOT NULL,
    enrollment_no  VARCHAR(20)    NOT NULL UNIQUE,
    course         VARCHAR(10)    NOT NULL,
    semester       TINYINT        NOT NULL,
    year           TINYINT        NOT NULL,
    mobile         VARCHAR(15)    NOT NULL,
    cgpa           DECIMAL(3,2)   NOT NULL DEFAULT 0.00,
    dob            DATE,
    created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- TABLE: events
CREATE TABLE IF NOT EXISTS events (
    id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200)    NOT NULL,
    description  TEXT,
    category     VARCHAR(50)     NOT NULL,
    venue        VARCHAR(200),
    event_date   DATE            NOT NULL,
    status       ENUM('Open','Limited Seats','Coming Soon','Closed') NOT NULL DEFAULT 'Open',
    poster_path  VARCHAR(300),
    created_by   INT UNSIGNED    NOT NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- TABLE: registrations
CREATE TABLE IF NOT EXISTS registrations (
    id             INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    student_id     INT UNSIGNED    NOT NULL,
    event_id       INT UNSIGNED    NOT NULL,
    registered_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(id)   ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_student_event (student_id, event_id)
) ENGINE=InnoDB;

-- TABLE: audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED,
    action       VARCHAR(100)    NOT NULL,
    description  TEXT,
    ip_address   VARCHAR(45),
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;
