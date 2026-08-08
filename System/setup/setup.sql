-- ============================================================
-- RFID Equipment Tracking & PM Management System
-- Database: rfid-system
-- ============================================================

CREATE DATABASE IF NOT EXISTS `rfid-system`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `rfid-system`;

-- ── Roles ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
  role_id     INT AUTO_INCREMENT PRIMARY KEY,
  role_name   VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  user_id   INT AUTO_INCREMENT PRIMARY KEY,
  username  VARCHAR(100) NOT NULL UNIQUE,
  email     VARCHAR(150) NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,
  role      VARCHAR(100) DEFAULT 'engineer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Departments ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS departments (
  department_id INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(200) NOT NULL UNIQUE,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Categories ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL UNIQUE,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Equipment (master registry) ────────────────────────────
-- ── Equipment (master registry) ────────────────────────────
CREATE TABLE IF NOT EXISTS equipment (
  equipment_id        INT AUTO_INCREMENT PRIMARY KEY,
  rfid_tag_id         VARCHAR(100) UNIQUE,
  name                VARCHAR(200) NOT NULL,
  serial_number       VARCHAR(100),
  model               VARCHAR(150),
  description         TEXT NULL,
  category            VARCHAR(200) DEFAULT 'Other',
  status              ENUM('OPERATIONAL','PM_DUE','IN_REPAIR','NEEDS_ATTENTION','DECOMMISSIONED') DEFAULT 'OPERATIONAL',
  current_location    VARCHAR(200),
  pm_interval_days    INT DEFAULT 90,
  last_maintenance_date DATE,
  next_pm_date        DATE,
  procurement_date    DATE,
  notes               TEXT,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Location logs (movement history) ──────────────────────
CREATE TABLE IF NOT EXISTS location_logs (
  log_id        INT AUTO_INCREMENT PRIMARY KEY,
  equipment_id  INT,
  reader_id     VARCHAR(100),
  location      VARCHAR(200),
  scanned_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Maintenance logs ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS maintenance_logs (
  maintenance_id  INT AUTO_INCREMENT PRIMARY KEY,
  equipment_id    INT,
  type            ENUM('PM','REPAIR') DEFAULT 'PM',
  service_date    DATE,
  technician      VARCHAR(200),
  remarks         TEXT,
  next_pm_date    DATE,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Issue tickets ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS issues_tickets (
  ticket_id           INT AUTO_INCREMENT PRIMARY KEY,
  equipment_id        INT,
  title               VARCHAR(255) NULL,
  reported_by         INT,
  reporter_name       VARCHAR(150) NULL,
  issue_description   TEXT,
  status              ENUM('OPEN','IN_PROGRESS','RESOLVED') DEFAULT 'OPEN',
  resolution_notes    TEXT NULL,
  repair_method       TEXT NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
  FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Repair Intake & Return Logs ────────────────────────────
CREATE TABLE IF NOT EXISTS repair_logs (
  repair_id            INT AUTO_INCREMENT PRIMARY KEY,
  equipment_id         INT NULL,
  machine_name         VARCHAR(200) NOT NULL,
  serial_number        VARCHAR(100) NOT NULL,
  department           VARCHAR(200) NOT NULL,
  brought_by           VARCHAR(200) NOT NULL,
  intake_date          DATE NOT NULL,
  machine_problem      TEXT NOT NULL,
  status               ENUM('IN_REPAIR','RETURNED') DEFAULT 'IN_REPAIR',
  return_date          DATE NULL,
  received_by          VARCHAR(200) NULL,
  delivery_department  VARCHAR(200) NULL,
  delivery_remarks     TEXT NULL,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Replaced Parts Log ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS part_replacements (
  part_replacement_id INT AUTO_INCREMENT PRIMARY KEY,
  repair_id           INT NULL,
  ticket_id           INT NULL,
  equipment_id        INT NULL,
  department          VARCHAR(200) NULL,
  part_serial_number  VARCHAR(100) NOT NULL,
  part_name           VARCHAR(200) NOT NULL,
  description         TEXT NULL,
  replacement_date    DATE NOT NULL,
  notes               TEXT NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (repair_id) REFERENCES repair_logs(repair_id) ON DELETE SET NULL,
  FOREIGN KEY (ticket_id) REFERENCES issues_tickets(ticket_id) ON DELETE SET NULL,
  FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Seed: Default Roles
-- ============================================================
INSERT IGNORE INTO roles (role_name, description) VALUES
('admin', 'Full system access and user management'),
('engineer', 'Biomedical Engineer - Equipment maintenance & monitoring'),
('technician', 'Field Technician - Equipment scanning & inspection');

-- ============================================================
-- Seed: Admin account (plain text password)
-- ============================================================
INSERT IGNORE INTO users (username, email, password, role) VALUES
('admin', 'admin@hospital.local', 'admin123', 'admin');

INSERT IGNORE INTO users (username, email, password, role) VALUES
('biomedeng', 'engineer@hospital.local', 'eng123', 'engineer');

-- ============================================================
-- Seed: Sample equipment
-- ============================================================
INSERT IGNORE INTO equipment
  (rfid_tag_id, name, serial_number, model, category, status, current_location, pm_interval_days, last_maintenance_date, next_pm_date, procurement_date)
VALUES
  ('TAG-001', 'Ventilator Pro X200',   'VNT-2023-001', 'Pro X200',      'Life Support', 'OPERATIONAL',     'ICU',       90,  '2026-05-10', '2026-08-07', '2023-01-15'),
  ('TAG-002', 'ECG Monitor Elite',     'ECG-2022-045', 'Elite 12-Lead', 'Monitoring',   'PM_DUE',          'Emergency', 180, '2025-12-01', '2026-05-30', '2022-06-20'),
  ('TAG-003', 'Digital X-Ray Unit',    'XRY-2021-012', 'DR-800',        'Imaging',      'OPERATIONAL',     'Radiology', 365, '2026-01-20', '2027-01-20', '2021-03-10'),
  ('TAG-004', 'Infusion Pump V3',      'INF-2023-089', 'Smart Pump V3', 'Life Support', 'IN_REPAIR',       'Ward A',    90,  '2026-03-15', '2026-06-13', '2023-05-01'),
  ('TAG-005', 'Pulse Oximeter OxyPro', 'POX-2024-003', 'OxyPro 500',   'Monitoring',   'OPERATIONAL',     'OPD',       180, '2026-04-01', '2026-09-28', '2024-01-01'),
  ('TAG-006', 'Ultrasound Scanner',    'ULT-2020-007', 'SonoVision 4D','Imaging',      'NEEDS_ATTENTION', 'Radiology', 365, '2025-06-10', '2026-06-10', '2020-07-15'),
  ('TAG-007', 'Defibrillator AED+',   'DEF-2022-034', 'AED Plus',      'Life Support', 'OPERATIONAL',     'Emergency', 90,  '2026-06-01', '2026-08-30', '2022-09-05'),
  ('TAG-008', 'Blood Analyzer BA-200', 'BLD-2019-011', 'BA-200',        'Lab',          'DECOMMISSIONED',  'Storage',   180, '2024-01-01', NULL,          '2019-11-22');

-- Sample location logs
INSERT IGNORE INTO location_logs (equipment_id, reader_id, location) VALUES
(1, 'READER-ICU-01', 'ICU'),
(2, 'READER-ER-01',  'Emergency'),
(3, 'READER-RAD-01', 'Radiology'),
(1, 'READER-OPD-01', 'OPD'),
(1, 'READER-ICU-01', 'ICU');

-- Sample maintenance logs
INSERT IGNORE INTO maintenance_logs (equipment_id, type, service_date, technician, remarks, next_pm_date) VALUES
(1, 'PM',     '2026-05-10', 'John Santos',   'Full calibration, filter replacement',      '2026-08-07'),
(2, 'PM',     '2025-12-01', 'Maria Cruz',    'Lead checks, software update',              '2026-05-30'),
(4, 'REPAIR', '2026-07-01', 'Carlos Reyes',  'Occlusion alarm fault — pump head replaced', NULL),
(3, 'PM',     '2026-01-20', 'Ana Gonzales',  'Annual calibration done',                  '2027-01-20');

-- Sample repair logs
INSERT IGNORE INTO repair_logs (repair_id, equipment_id, machine_name, serial_number, department, brought_by, intake_date, machine_problem, status, return_date, received_by, delivery_department, delivery_remarks) VALUES
(1, 4, 'Infusion Pump V3', 'INF-2023-089', 'Ward A', 'Nurse Sarah', '2026-07-28', 'Occlusion alarm fault and keypad sticky', 'IN_REPAIR', NULL, NULL, NULL, NULL),
(2, 2, 'ECG Monitor Elite', 'ECG-2022-045', 'Emergency', 'Dr. Smith', '2026-06-10', 'Display flickering & lead line fault', 'RETURNED', '2026-06-14', 'Tech David', 'Emergency', 'Machine tested and calibrated before return');

-- Sample part replacements
INSERT IGNORE INTO part_replacements (part_replacement_id, repair_id, ticket_id, equipment_id, department, part_serial_number, part_name, description, replacement_date, notes) VALUES
(1, 1, NULL, 4, 'Ward A', 'PRT-HEAD-908', 'Pump Head Assembly', 'Replacement pump head for occlusion alarm fix', '2026-07-29', 'Replaced faulty occlusion pump head'),
(2, 2, NULL, 2, 'Emergency', 'PRT-DSP-112', '12-Lead Display Panel', 'OEM display panel for ECG monitor', '2026-06-12', 'Flickering screen replaced with original OEM part');

