-- =====================================================
-- Admin Dashboard - Database Schema
-- =====================================================
-- This script creates all necessary tables for the
-- admin dashboard including authentication, settings,
-- audit logging, and patient status management
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- ADMIN USERS TABLE
-- Authentication and access control
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Authentication
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,

    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    -- Status and Permissions
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('super_admin', 'admin', 'staff') DEFAULT 'admin',

    -- Security
    last_login DATETIME,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME,
    password_reset_token VARCHAR(100),
    password_reset_expires DATETIME,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADMIN SESSIONS TABLE
-- Track active admin sessions for security
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_sessions (
    session_id VARCHAR(100) PRIMARY KEY,
    admin_id INT NOT NULL,

    -- Session Information
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Security
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_admin_id (admin_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CLINIC SETTINGS TABLE
-- Key-value store for clinic configuration
-- =====================================================
CREATE TABLE IF NOT EXISTS clinic_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Setting Information
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'email', 'url', 'color') DEFAULT 'text',

    -- Organization
    category VARCHAR(50) NOT NULL,  -- 'branding', 'contact', 'operations', 'notifications', etc.
    display_name VARCHAR(200),
    description TEXT,

    -- Metadata
    is_public BOOLEAN DEFAULT FALSE,  -- Whether setting can be displayed on public forms
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_category (category),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADMIN AUDIT LOG TABLE
-- Track all admin actions for HIPAA compliance
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Admin Information
    admin_id INT,
    admin_username VARCHAR(50),

    -- Action Details
    action_type ENUM('VIEW', 'CREATE', 'UPDATE', 'DELETE', 'EXPORT', 'LOGIN', 'LOGOUT', 'FAILED_LOGIN') NOT NULL,
    table_name VARCHAR(100),
    record_id INT,

    -- Details
    description TEXT,
    old_values TEXT,  -- JSON format
    new_values TEXT,  -- JSON format

    -- Request Information
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Patient PHI Access (for HIPAA compliance)
    patient_id INT,
    phi_accessed BOOLEAN DEFAULT FALSE,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_created_at (created_at),
    INDEX idx_phi_accessed (phi_accessed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PATIENT STATUS TABLE
-- Track patient workflow status (check-in, in-progress, completed)
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL UNIQUE,

    -- Workflow Status
    current_status ENUM('registered', 'checked_in', 'in_progress', 'completed', 'cancelled') DEFAULT 'registered',

    -- Timestamps
    registered_at DATETIME,
    checked_in_at DATETIME,
    in_progress_at DATETIME,
    completed_at DATETIME,
    cancelled_at DATETIME,

    -- Admin Actions
    checked_in_by INT,
    completed_by INT,

    -- Notes
    admin_notes TEXT,
    cancellation_reason TEXT,

    -- Priority
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (checked_in_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_patient_id (patient_id),
    INDEX idx_current_status (current_status),
    INDEX idx_priority (priority),
    INDEX idx_checked_in_at (checked_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FORM FIELD CUSTOMIZATION TABLE
-- Customize which form fields are required/optional
-- =====================================================
CREATE TABLE IF NOT EXISTS form_field_settings (
    field_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Field Information
    form_name VARCHAR(100) NOT NULL,  -- 'patient_registration', 'medical_history', etc.
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(200),

    -- Field Settings
    is_required BOOLEAN DEFAULT TRUE,
    is_visible BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,

    -- Validation
    validation_rules TEXT,  -- JSON format for custom validation

    -- Metadata
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,

    -- Unique Constraint
    UNIQUE KEY unique_form_field (form_name, field_name),

    -- Indexes
    INDEX idx_form_name (form_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATA EXPORT LOG TABLE
-- Track all data exports for compliance
-- =====================================================
CREATE TABLE IF NOT EXISTS export_log (
    export_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Export Information
    export_type ENUM('patient_list', 'patient_details', 'dashboard_metrics', 'audit_log') NOT NULL,
    export_format ENUM('csv', 'pdf', 'excel', 'json') NOT NULL,

    -- Admin Information
    admin_id INT NOT NULL,

    -- Export Details
    filters_applied TEXT,  -- JSON format
    record_count INT,
    file_name VARCHAR(255),

    -- Date Range
    date_from DATE,
    date_to DATE,

    -- Patient PHI
    contains_phi BOOLEAN DEFAULT TRUE,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_admin_id (admin_id),
    INDEX idx_export_type (export_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Clinic Settings
-- =====================================================
INSERT INTO clinic_settings (setting_key, setting_value, setting_type, category, display_name, description, is_public) VALUES
-- Branding
('clinic_name', 'PrimeHealth Urgent Care', 'text', 'branding', 'Clinic Name', 'Name displayed on forms and dashboard', TRUE),
('clinic_logo_url', '', 'url', 'branding', 'Logo URL', 'URL to clinic logo image', TRUE),
('primary_color', '#0066cc', 'color', 'branding', 'Primary Color', 'Main brand color', TRUE),
('secondary_color', '#004d99', 'color', 'branding', 'Secondary Color', 'Accent brand color', TRUE),

-- Contact Information
('clinic_phone', '(555) 123-4567', 'text', 'contact', 'Phone Number', 'Main clinic phone number', TRUE),
('clinic_email', 'info@primehealthurgentcare.com', 'email', 'contact', 'Email Address', 'Main clinic email', TRUE),
('clinic_address', '123 Main Street', 'text', 'contact', 'Street Address', 'Clinic street address', TRUE),
('clinic_city', 'Los Angeles', 'text', 'contact', 'City', 'City name', TRUE),
('clinic_state', 'CA', 'text', 'contact', 'State', 'State abbreviation', TRUE),
('clinic_zip', '90001', 'text', 'contact', 'ZIP Code', 'ZIP or postal code', TRUE),

-- Operating Hours
('hours_monday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Monday Hours', 'Operating hours for Monday', TRUE),
('hours_tuesday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Tuesday Hours', 'Operating hours for Tuesday', TRUE),
('hours_wednesday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Wednesday Hours', 'Operating hours for Wednesday', TRUE),
('hours_thursday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Thursday Hours', 'Operating hours for Thursday', TRUE),
('hours_friday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Friday Hours', 'Operating hours for Friday', TRUE),
('hours_saturday', '9:00 AM - 5:00 PM', 'text', 'operations', 'Saturday Hours', 'Operating hours for Saturday', TRUE),
('hours_sunday', '9:00 AM - 5:00 PM', 'text', 'operations', 'Sunday Hours', 'Operating hours for Sunday', TRUE),

-- System Settings
('session_timeout', '30', 'number', 'system', 'Session Timeout (minutes)', 'Admin session timeout in minutes', FALSE),
('data_retention_days', '2555', 'number', 'system', 'Data Retention (days)', 'Number of days to retain patient data (7 years = 2555 days)', FALSE),
('enable_drchrono_sync', 'true', 'boolean', 'system', 'Enable DrChrono Sync', 'Automatically sync patients to DrChrono', FALSE),

-- Notifications
('notify_new_patient', 'true', 'boolean', 'notifications', 'New Patient Notifications', 'Email notification for new patient registrations', FALSE),
('notify_email', 'admin@primehealthurgentcare.com', 'email', 'notifications', 'Notification Email', 'Email address for admin notifications', FALSE),
('notify_failed_sync', 'true', 'boolean', 'notifications', 'Failed Sync Notifications', 'Email notification for DrChrono sync failures', FALSE);

-- =====================================================
-- Default Admin User
-- Username: admin
-- Password: ChangeMe123! (MUST be changed on first login)
-- =====================================================
-- Password hash for 'ChangeMe123!' using bcrypt
INSERT INTO admin_users (username, password_hash, email, first_name, last_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@clinic.local', 'System', 'Administrator', 'super_admin');

-- =====================================================
-- Enhanced Views for Admin Dashboard
-- =====================================================

-- Complete patient view with status
CREATE OR REPLACE VIEW vw_admin_patients AS
SELECT
    p.patient_id,
    p.first_name,
    p.last_name,
    CONCAT(p.first_name, ' ', p.last_name) as full_name,
    p.date_of_birth,
    p.age,
    p.gender,
    p.email,
    p.cell_phone,
    p.reason_for_visit,
    p.insurance_provider,
    p.drchrono_patient_id,
    p.drchrono_sync_status,
    p.drchrono_sync_date,
    p.created_at as registration_date,
    fs.all_forms_completed,
    fs.completed_at as forms_completed_date,
    ps.current_status,
    ps.priority,
    ps.checked_in_at,
    ps.completed_at as visit_completed_at,
    ps.admin_notes,
    fa.payment_method
FROM patients p
LEFT JOIN form_submissions fs ON p.patient_id = fs.patient_id
LEFT JOIN patient_status ps ON p.patient_id = ps.patient_id
LEFT JOIN financial_agreements fa ON p.patient_id = fa.patient_id;

-- Daily statistics
CREATE OR REPLACE VIEW vw_daily_stats AS
SELECT
    DATE(p.created_at) as stat_date,
    COUNT(DISTINCT p.patient_id) as total_registrations,
    COUNT(DISTINCT CASE WHEN fs.all_forms_completed = TRUE THEN p.patient_id END) as completed_forms,
    COUNT(DISTINCT CASE WHEN ps.current_status = 'checked_in' THEN p.patient_id END) as checked_in_count,
    COUNT(DISTINCT CASE WHEN ps.current_status = 'completed' THEN p.patient_id END) as completed_visits,
    COUNT(DISTINCT CASE WHEN p.insurance_provider IS NOT NULL AND p.insurance_provider != '' THEN p.patient_id END) as insurance_patients,
    COUNT(DISTINCT CASE WHEN p.insurance_provider IS NULL OR p.insurance_provider = '' THEN p.patient_id END) as self_pay_patients,
    AVG(TIMESTAMPDIFF(MINUTE, p.created_at, fs.completed_at)) as avg_form_completion_minutes
FROM patients p
LEFT JOIN form_submissions fs ON p.patient_id = fs.patient_id
LEFT JOIN patient_status ps ON p.patient_id = ps.patient_id
WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(p.created_at)
ORDER BY stat_date DESC;

-- DrChrono sync status summary
CREATE OR REPLACE VIEW vw_sync_status_summary AS
SELECT
    drchrono_sync_status as status,
    COUNT(*) as count,
    MAX(created_at) as last_updated
FROM patients
GROUP BY drchrono_sync_status;

-- Patients needing attention (incomplete forms, failed sync, etc.)
CREATE OR REPLACE VIEW vw_patients_needing_attention AS
SELECT
    p.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as full_name,
    p.email,
    p.cell_phone,
    p.created_at,
    CASE
        WHEN fs.all_forms_completed = FALSE THEN 'Incomplete Forms'
        WHEN p.drchrono_sync_status = 'failed' THEN 'Sync Failed'
        WHEN p.drchrono_sync_status = 'pending' AND TIMESTAMPDIFF(HOUR, p.created_at, NOW()) > 1 THEN 'Sync Pending > 1 hour'
        ELSE 'Unknown'
    END as issue_type,
    CASE
        WHEN fs.all_forms_completed = FALSE THEN 'high'
        WHEN p.drchrono_sync_status = 'failed' THEN 'medium'
        ELSE 'low'
    END as priority_level
FROM patients p
LEFT JOIN form_submissions fs ON p.patient_id = fs.patient_id
WHERE
    fs.all_forms_completed = FALSE
    OR p.drchrono_sync_status = 'failed'
    OR (p.drchrono_sync_status = 'pending' AND TIMESTAMPDIFF(HOUR, p.created_at, NOW()) > 1)
ORDER BY
    FIELD(priority_level, 'high', 'medium', 'low'),
    p.created_at DESC;

-- =====================================================
-- Triggers for Automatic Patient Status Creation
-- =====================================================
DELIMITER //

CREATE TRIGGER after_patient_insert
AFTER INSERT ON patients
FOR EACH ROW
BEGIN
    -- Create patient status record
    INSERT INTO patient_status (patient_id, current_status, registered_at)
    VALUES (NEW.patient_id, 'registered', NEW.created_at);
END//

DELIMITER ;

-- =====================================================
-- Database Schema Creation Complete
-- =====================================================
SELECT 'Admin dashboard schema created successfully!' as status;
SELECT 'Default admin user created - Username: admin, Password: ChangeMe123!' as admin_credentials;
SELECT 'IMPORTANT: Change the default admin password immediately!' as security_warning;
