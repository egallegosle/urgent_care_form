-- =====================================================
-- Admin Dashboard - Database Schema (PHP Compatible)
-- =====================================================
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- ADMIN USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('super_admin', 'admin', 'staff') DEFAULT 'admin',
    last_login DATETIME,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME,
    password_reset_token VARCHAR(100),
    password_reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADMIN SESSIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_sessions (
    session_id VARCHAR(100) PRIMARY KEY,
    admin_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CLINIC SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS clinic_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'email', 'url', 'color') DEFAULT 'text',
    category VARCHAR(50) NOT NULL,
    display_name VARCHAR(200),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADMIN AUDIT LOG TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    admin_username VARCHAR(50),
    action_type ENUM('VIEW', 'CREATE', 'UPDATE', 'DELETE', 'EXPORT', 'LOGIN', 'LOGOUT', 'FAILED_LOGIN') NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    description TEXT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    patient_id INT,
    phi_accessed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_created_at (created_at),
    INDEX idx_phi_accessed (phi_accessed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PATIENT STATUS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL UNIQUE,
    current_status ENUM('registered', 'checked_in', 'in_progress', 'completed', 'cancelled') DEFAULT 'registered',
    registered_at DATETIME,
    checked_in_at DATETIME,
    in_progress_at DATETIME,
    completed_at DATETIME,
    cancelled_at DATETIME,
    checked_in_by INT,
    completed_by INT,
    admin_notes TEXT,
    cancellation_reason TEXT,
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (checked_in_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_current_status (current_status),
    INDEX idx_priority (priority),
    INDEX idx_checked_in_at (checked_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FORM FIELD CUSTOMIZATION TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS form_field_settings (
    field_id INT AUTO_INCREMENT PRIMARY KEY,
    form_name VARCHAR(100) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(200),
    is_required BOOLEAN DEFAULT TRUE,
    is_visible BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    validation_rules TEXT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    UNIQUE KEY unique_form_field (form_name, field_name),
    INDEX idx_form_name (form_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATA EXPORT LOG TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS export_log (
    export_id INT AUTO_INCREMENT PRIMARY KEY,
    export_type ENUM('patient_list', 'patient_details', 'dashboard_metrics', 'audit_log') NOT NULL,
    export_format ENUM('csv', 'pdf', 'excel', 'json') NOT NULL,
    admin_id INT NOT NULL,
    filters_applied TEXT,
    record_count INT,
    file_name VARCHAR(255),
    date_from DATE,
    date_to DATE,
    contains_phi BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_export_type (export_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Clinic Settings
-- =====================================================
INSERT INTO clinic_settings (setting_key, setting_value, setting_type, category, display_name, description, is_public) VALUES
('clinic_name', 'PrimeHealth Urgent Care', 'text', 'branding', 'Clinic Name', 'Name displayed on forms and dashboard', TRUE),
('clinic_logo_url', '', 'url', 'branding', 'Logo URL', 'URL to clinic logo image', TRUE),
('primary_color', '#0066cc', 'color', 'branding', 'Primary Color', 'Main brand color', TRUE),
('secondary_color', '#004d99', 'color', 'branding', 'Secondary Color', 'Accent brand color', TRUE),
('clinic_phone', '(555) 123-4567', 'text', 'contact', 'Phone Number', 'Main clinic phone number', TRUE),
('clinic_email', 'info@primehealthurgentcare.com', 'email', 'contact', 'Email Address', 'Main clinic email', TRUE),
('clinic_address', '123 Main Street', 'text', 'contact', 'Street Address', 'Clinic street address', TRUE),
('clinic_city', 'Los Angeles', 'text', 'contact', 'City', 'City name', TRUE),
('clinic_state', 'CA', 'text', 'contact', 'State', 'State abbreviation', TRUE),
('clinic_zip', '90001', 'text', 'contact', 'ZIP Code', 'ZIP or postal code', TRUE),
('hours_monday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Monday Hours', 'Operating hours for Monday', TRUE),
('hours_tuesday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Tuesday Hours', 'Operating hours for Tuesday', TRUE),
('hours_wednesday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Wednesday Hours', 'Operating hours for Wednesday', TRUE),
('hours_thursday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Thursday Hours', 'Operating hours for Thursday', TRUE),
('hours_friday', '8:00 AM - 8:00 PM', 'text', 'operations', 'Friday Hours', 'Operating hours for Friday', TRUE),
('hours_saturday', '9:00 AM - 5:00 PM', 'text', 'operations', 'Saturday Hours', 'Operating hours for Saturday', TRUE),
('hours_sunday', '9:00 AM - 5:00 PM', 'text', 'operations', 'Sunday Hours', 'Operating hours for Sunday', TRUE),
('session_timeout', '30', 'number', 'system', 'Session Timeout (minutes)', 'Admin session timeout in minutes', FALSE),
('data_retention_days', '2555', 'number', 'system', 'Data Retention (days)', 'Number of days to retain patient data (7 years = 2555 days)', FALSE),
('enable_drchrono_sync', 'true', 'boolean', 'system', 'Enable DrChrono Sync', 'Automatically sync patients to DrChrono', FALSE),
('notify_new_patient', 'true', 'boolean', 'notifications', 'New Patient Notifications', 'Email notification for new patient registrations', FALSE),
('notify_email', 'admin@primehealthurgentcare.com', 'email', 'notifications', 'Notification Email', 'Email address for admin notifications', FALSE),
('notify_failed_sync', 'true', 'boolean', 'notifications', 'Failed Sync Notifications', 'Email notification for DrChrono sync failures', FALSE)
ON DUPLICATE KEY UPDATE setting_value=setting_value;

-- =====================================================
-- Default Admin User
-- Username: admin
-- Password: ChangeMe123!
-- =====================================================
INSERT INTO admin_users (username, password_hash, email, first_name, last_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@clinic.local', 'System', 'Administrator', 'super_admin')
ON DUPLICATE KEY UPDATE password_hash=password_hash;
