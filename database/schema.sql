-- QECH Queue Management System Database Schema
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS qech_queue_system;
USE qech_queue_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('patient', 'staff', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Queue tokens table
CREATE TABLE IF NOT EXISTS queue_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_number VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT,
    patient_phone VARCHAR(20),
    patient_id_number VARCHAR(50),
    patient_address TEXT,
    service_type VARCHAR(100),
    department_id INT NOT NULL,
    priority_type ENUM('no', 'emergency', 'elderly', 'pregnant', 'disability') DEFAULT 'no',
    status ENUM('waiting', 'serving', 'completed', 'cancelled') DEFAULT 'waiting',
    queue_position INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Staff assignments table
CREATE TABLE IF NOT EXISTS staff_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    department_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (staff_id, department_id)
);

-- Queue history table
CREATE TABLE IF NOT EXISTS queue_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NULL,
    action ENUM('created', 'called', 'completed', 'cancelled', 'queue_paused', 'queue_resumed', 'reassigned', 'attended', 'queued') NOT NULL,
    performed_by INT,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (token_id) REFERENCES queue_tokens(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_number VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT,
    patient_phone VARCHAR(20) NOT NULL,
    patient_id_number VARCHAR(50),
    patient_email VARCHAR(100),
    department_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    service_type VARCHAR(100),
    reason TEXT,
    status ENUM('pending', 'confirmed', 'queued', 'completed', 'cancelled') DEFAULT 'pending',
    priority_type ENUM('no', 'emergency', 'elderly', 'pregnant', 'disability') DEFAULT 'no',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Appointment history table
CREATE TABLE IF NOT EXISTS appointment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    action ENUM('created', 'confirmed', 'rescheduled', 'queued', 'completed', 'cancelled') NOT NULL,
    performed_by INT,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default departments
INSERT INTO departments (code, name, description) VALUES
('opd', 'Outpatient Department (OPD)', 'General outpatient services'),
('maternity', 'Maternity', 'Maternity and prenatal care'),
('emergency', 'Emergency', 'Emergency medical services'),
('pediatrics', 'Pediatrics', 'Children healthcare services');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Create indexes for better performance
CREATE INDEX idx_token_status ON queue_tokens(status);
CREATE INDEX idx_token_department ON queue_tokens(department_id);
CREATE INDEX idx_token_created ON queue_tokens(created_at);
CREATE INDEX idx_user_role ON users(role);

-- Appointments indexes
CREATE INDEX idx_appointment_date ON appointments(appointment_date);
CREATE INDEX idx_appointment_status ON appointments(status);
CREATE INDEX idx_appointment_department ON appointments(department_id);
CREATE INDEX idx_appointment_patient ON appointments(patient_id);
