-- =====================================================
-- MEDICORE AI — DATABASE SCHEMA
-- File: database/medicore_db.sql
-- Run this in phpMyAdmin or MySQL CLI
-- =====================================================

CREATE DATABASE IF NOT EXISTS medicore_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE medicore_db;

-- ── DOCTORS TABLE ────────────────────────────────────
CREATE TABLE IF NOT EXISTS doctors (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    specialization  VARCHAR(100)    NOT NULL,
    qualification   VARCHAR(200),
    experience_yrs  INT             DEFAULT 0,
    rating          DECIMAL(3,1)    DEFAULT 4.5,
    total_patients  INT             DEFAULT 0,
    availability    ENUM('available','busy','offline') DEFAULT 'available',
    working_days    VARCHAR(100),
    working_hours   VARCHAR(50),
    consult_fee     DECIMAL(8,2),
    bio             TEXT,
    phone           VARCHAR(20),
    email           VARCHAR(150),
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PATIENTS TABLE ───────────────────────────────────
CREATE TABLE IF NOT EXISTS patients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    age             INT,
    gender          ENUM('male','female','other'),
    phone           VARCHAR(20),
    email           VARCHAR(150),
    address         TEXT,
    blood_group     VARCHAR(5),
    allergies       TEXT,
    medical_history TEXT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── APPOINTMENTS TABLE ───────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_name    VARCHAR(150)    NOT NULL,
    patient_id      INT,
    doctor_id       INT             NOT NULL,
    doctor_name     VARCHAR(150),
    department      VARCHAR(100),
    appt_date       DATE            NOT NULL,
    time_slot       VARCHAR(20)     NOT NULL,
    reason          TEXT,
    status          ENUM('pending','confirmed','completed','cancelled') DEFAULT 'confirmed',
    notes           TEXT,
    queue_number    INT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id)  REFERENCES doctors(id)  ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── QUEUE TABLE ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS queue (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    department      VARCHAR(100)    NOT NULL,
    patient_name    VARCHAR(150)    NOT NULL,
    patient_id      INT,
    token_number    INT             NOT NULL,
    status          ENUM('waiting','called','in-progress','done') DEFAULT 'waiting',
    estimated_wait  INT             DEFAULT 0,
    joined_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    called_at       TIMESTAMP       NULL,
    completed_at    TIMESTAMP       NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── MEDICINE REMINDERS TABLE ─────────────────────────
CREATE TABLE IF NOT EXISTS medicine_reminders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_name    VARCHAR(150)    NOT NULL,
    patient_id      INT,
    medicine_name   VARCHAR(200)    NOT NULL,
    dosage          VARCHAR(100),
    frequency       ENUM('once','twice','thrice','custom') DEFAULT 'once',
    reminder_times  VARCHAR(200),
    start_date      DATE,
    end_date        DATE,
    food_instruction ENUM('before','after','any') DEFAULT 'any',
    notes           TEXT,
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── MEDICINE DOSE LOG TABLE ──────────────────────────
CREATE TABLE IF NOT EXISTS medicine_dose_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reminder_id     INT             NOT NULL,
    dose_date       DATE            NOT NULL,
    dose_time       VARCHAR(10)     NOT NULL,
    status          ENUM('taken','skipped','snoozed') DEFAULT 'taken',
    logged_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reminder_id) REFERENCES medicine_reminders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── BEDS TABLE ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS beds (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    bed_number      VARCHAR(20)     NOT NULL UNIQUE,
    ward            VARCHAR(100)    NOT NULL,
    bed_type        ENUM('general','semi-private','private','ICU','emergency') DEFAULT 'general',
    status          ENUM('available','occupied','maintenance') DEFAULT 'available',
    patient_id      INT,
    patient_name    VARCHAR(150),
    admitted_at     TIMESTAMP       NULL,
    notes           TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CHAT HISTORY TABLE ───────────────────────────────
CREATE TABLE IF NOT EXISTS chat_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(100)    NOT NULL,
    patient_name    VARCHAR(150),
    role            ENUM('user','assistant') NOT NULL,
    message         TEXT            NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEED DATA — DOCTORS
-- =====================================================
INSERT INTO doctors (name, specialization, qualification, experience_yrs, rating, total_patients, availability, working_days, working_hours, consult_fee, bio) VALUES
('Dr. Priya Sharma',    'Cardiology',      'MD, DM Cardiology',     14, 4.9, 1240, 'available', 'Mon-Sat', '9AM-5PM',  700, 'Senior Cardiologist with expertise in interventional cardiology and heart failure management.'),
('Dr. Rajan Patel',     'Neurology',       'MD, DM Neurology',      11, 4.8, 980,  'available', 'Mon-Fri', '10AM-6PM', 650, 'Specialist in epilepsy, stroke, and neuro-degenerative conditions.'),
('Dr. Lakshmi Rao',     'Pediatrics',      'MD Pediatrics, PGDCH',  9,  4.9, 2100, 'available', 'Mon-Sat', '8AM-4PM',  500, 'Child health specialist focusing on developmental pediatrics and infections.'),
('Dr. Mohan Krishnan',  'Orthopedics',     'MS Orthopedics',        16, 4.7, 1560, 'busy',      'Tue-Sat', '11AM-7PM', 700, 'Orthopedic surgeon specializing in joint replacement and sports injuries.'),
('Dr. Ananya Singh',    'Dermatology',     'MD Dermatology',        8,  4.8, 870,  'available', 'Mon-Fri', '9AM-5PM',  600, 'Expert in clinical and cosmetic dermatology including psoriasis and acne.'),
('Dr. Suresh Nair',     'General Medicine','MD General Medicine',   20, 4.9, 3400, 'available', 'Mon-Sat', '8AM-8PM',  400, 'Comprehensive general practitioner with deep experience in chronic disease management.'),
('Dr. Deepa Menon',     'Gynecology',      'MD, MS Gynecology',     13, 4.9, 1890, 'offline',   'Mon-Fri', '9AM-3PM',  700, 'OB/GYN with focus on high-risk pregnancies and laparoscopic surgery.'),
('Dr. Vijay Kumar',     'Oncology',        'MD, DM Oncology',       18, 4.8, 720,  'available', 'Mon-Thu', '10AM-4PM', 900, 'Medical oncologist specializing in chemotherapy and targeted therapy.'),
('Dr. Nisha Reddy',     'Neurology',       'MD, DM Neurology',      7,  4.7, 540,  'busy',      'Wed-Sun', '9AM-5PM',  600, 'Neurologist with expertise in headache disorders and multiple sclerosis.'),
('Dr. Arjun Das',       'Cardiology',      'MD, DM Cardiology',     10, 4.8, 890,  'available', 'Mon-Sat', '8AM-6PM',  700, 'Cardiologist specializing in non-invasive cardiac imaging and preventive cardiology.'),
('Dr. Kavitha Iyer',    'Pediatrics',      'MD Pediatrics',         6,  4.6, 1100, 'available', 'Tue-Sat', '9AM-5PM',  500, 'Pediatrician specializing in neonatal care and childhood nutrition.'),
('Dr. Senthil Murugan', 'Orthopedics',     'MS Orthopedics, DNB',   12, 4.7, 1020, 'available', 'Mon-Fri', '9AM-5PM',  650, 'Orthopedic specialist in physiotherapy integration and minimally invasive surgery.');

-- =====================================================
-- SEED DATA — BEDS
-- =====================================================
INSERT INTO beds (bed_number, ward, bed_type, status) VALUES
('G-101','General Ward','general','available'), ('G-102','General Ward','general','available'),
('G-103','General Ward','general','occupied'),  ('G-104','General Ward','general','available'),
('G-105','General Ward','general','maintenance'),
('SP-201','Semi-Private','semi-private','available'), ('SP-202','Semi-Private','semi-private','occupied'),
('PR-301','Private Ward','private','available'), ('PR-302','Private Ward','private','available'),
('ICU-01','ICU','ICU','occupied'), ('ICU-02','ICU','ICU','available'), ('ICU-03','ICU','ICU','occupied'),
('ER-01','Emergency','emergency','available'), ('ER-02','Emergency','emergency','occupied');

-- =====================================================
-- SEED DATA — SAMPLE QUEUE
-- =====================================================
INSERT INTO queue (department, patient_name, token_number, status, estimated_wait) VALUES
('General Medicine', 'Arun Kumar',   1, 'in-progress', 0),
('General Medicine', 'Meena Devi',   2, 'called',      12),
('General Medicine', 'Karthik S.',   3, 'waiting',     24),
('Cardiology',       'Senthil M.',   1, 'in-progress', 0),
('Cardiology',       'Lakshmi R.',   2, 'waiting',     15),
('Neurology',        'Deepa N.',     1, 'called',      8),
('Orthopedics',      'Suresh P.',    1, 'in-progress', 0),
('Orthopedics',      'Anbu K.',      2, 'waiting',     18),
('Pediatrics',       'Baby Arjun',   1, 'in-progress', 0),
('Emergency',        'URGENT-001',   1, 'in-progress', 0);