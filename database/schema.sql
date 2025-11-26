-- =====================================================
-- Urgent Care Form System - Database Schema
-- =====================================================
-- This script creates all necessary tables for the
-- patient intake form system with DrChrono integration
-- =====================================================

-- Set charset and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- PATIENTS TABLE
-- Main patient demographic and contact information
-- =====================================================
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    age INT,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    ssn VARCHAR(11),  -- Format: XXX-XX-XXXX

    -- Contact Information
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(50) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    home_phone VARCHAR(20),
    cell_phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),

    -- Emergency Contact
    emergency_contact_name VARCHAR(200) NOT NULL,
    emergency_contact_phone VARCHAR(20) NOT NULL,
    emergency_relationship VARCHAR(100) NOT NULL,

    -- Insurance Information
    insurance_provider VARCHAR(200),
    policy_number VARCHAR(100),
    group_number VARCHAR(100),
    policy_holder_name VARCHAR(200),
    policy_holder_dob DATE,

    -- Primary Care Physician
    pcp_name VARCHAR(200),
    pcp_phone VARCHAR(20),

    -- Visit Information
    reason_for_visit TEXT NOT NULL,
    allergies TEXT,
    current_medications TEXT,

    -- DrChrono Integration
    drchrono_patient_id VARCHAR(100),
    drchrono_sync_status ENUM('pending', 'synced', 'failed', 'not_synced') DEFAULT 'pending',
    drchrono_sync_date DATETIME,
    drchrono_error_message TEXT,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_last_name (last_name),
    INDEX idx_email (email),
    INDEX idx_cell_phone (cell_phone),
    INDEX idx_drchrono_id (drchrono_patient_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MEDICAL HISTORY TABLE
-- Patient medical history, conditions, and lifestyle
-- =====================================================
CREATE TABLE IF NOT EXISTS medical_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Lifestyle Information
    smokes ENUM('Yes', 'No') NOT NULL,
    smoking_frequency VARCHAR(100),
    drinks_alcohol ENUM('Yes', 'No') NOT NULL,
    alcohol_frequency ENUM('Rarely', 'Socially', 'Weekly', 'Daily'),

    -- Medical Conditions (stored as comma-separated values)
    medical_conditions TEXT,  -- Will store selected conditions
    other_conditions TEXT,

    -- Surgical History
    previous_surgeries ENUM('Yes', 'No'),
    surgery_details TEXT,

    -- Current Medications
    current_medications TEXT,

    -- Allergies
    has_allergies ENUM('Yes', 'No'),
    allergy_details TEXT,

    -- Family History
    family_history TEXT,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,

    -- Index
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PATIENT CONSENT TABLE
-- Treatment consent and authorizations
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_consents (
    consent_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Consent Acknowledgments
    read_and_understood ENUM('Yes', 'No') NOT NULL,
    questions_answered ENUM('Yes', 'No') NOT NULL,
    voluntary_consent ENUM('Yes', 'No') NOT NULL,

    -- Signature Information
    patient_signature_name VARCHAR(200) NOT NULL,
    patient_signature TEXT NOT NULL,  -- Will store typed signature or signature data
    signature_date DATE NOT NULL,
    signature_time TIME NOT NULL,

    -- Guardian Information (if applicable)
    guardian_name VARCHAR(200),
    guardian_relationship ENUM('Parent', 'Legal Guardian', 'Power of Attorney', 'Other'),
    guardian_signature TEXT,
    guardian_date DATE,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,

    -- Index
    INDEX idx_patient_id (patient_id),
    INDEX idx_consent_date (signature_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FINANCIAL AGREEMENTS TABLE
-- Financial responsibility and payment terms
-- =====================================================
CREATE TABLE IF NOT EXISTS financial_agreements (
    agreement_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Payment Information
    payment_method ENUM('Cash', 'Credit Card', 'Debit Card', 'Insurance') NOT NULL,

    -- Acknowledgments
    read_understood ENUM('Yes', 'No') NOT NULL,
    agree_to_terms ENUM('Yes', 'No') NOT NULL,
    authorize_insurance ENUM('Yes', 'No') NOT NULL,
    responsible_for_balance ENUM('Yes', 'No') NOT NULL,

    -- Signature Information
    signature_name VARCHAR(200) NOT NULL,
    signature TEXT NOT NULL,
    signature_date DATE NOT NULL,
    relationship_to_patient ENUM('Self', 'Spouse', 'Parent', 'Legal Guardian', 'Other') NOT NULL,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,

    -- Index
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADDITIONAL CONSENTS TABLE
-- HIPAA, communication preferences, and portal access
-- =====================================================
CREATE TABLE IF NOT EXISTS additional_consents (
    consent_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- HIPAA Acknowledgment
    hipaa_acknowledged ENUM('Yes', 'No') NOT NULL,

    -- Communication Preferences (stored as comma-separated values)
    communication_preferences TEXT,  -- Appointment Reminders, Test Results, etc.
    contact_methods TEXT,  -- Phone, Text Message, Email, Mail

    -- Voicemail Authorization
    voicemail_authorization ENUM('Yes', 'General Only', 'No'),

    -- Patient Portal
    portal_access ENUM('Yes', 'No'),
    portal_email VARCHAR(255),

    -- Authorized Person/Caregiver
    authorized_person_name VARCHAR(200),
    authorized_person_relation VARCHAR(100),
    authorized_person_phone VARCHAR(20),
    authorize_discussion ENUM('Yes', 'No'),

    -- Final Acknowledgments
    all_forms_complete ENUM('Yes', 'No') NOT NULL,
    consent_to_all ENUM('Yes', 'No') NOT NULL,

    -- Signature Information
    signature_name VARCHAR(200) NOT NULL,
    signature TEXT NOT NULL,
    signature_date DATE NOT NULL,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,

    -- Index
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FORM SUBMISSIONS TABLE
-- Track form completion status and workflow
-- =====================================================
CREATE TABLE IF NOT EXISTS form_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Form Completion Status
    registration_completed BOOLEAN DEFAULT FALSE,
    registration_completed_at DATETIME,

    medical_history_completed BOOLEAN DEFAULT FALSE,
    medical_history_completed_at DATETIME,

    consent_completed BOOLEAN DEFAULT FALSE,
    consent_completed_at DATETIME,

    financial_completed BOOLEAN DEFAULT FALSE,
    financial_completed_at DATETIME,

    additional_consents_completed BOOLEAN DEFAULT FALSE,
    additional_consents_completed_at DATETIME,

    -- Overall Status
    all_forms_completed BOOLEAN DEFAULT FALSE,
    completed_at DATETIME,

    -- Session Information
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_patient_id (patient_id),
    INDEX idx_all_completed (all_forms_completed),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DRCHRONO SYNC LOG TABLE
-- Track all API sync attempts and responses
-- =====================================================
CREATE TABLE IF NOT EXISTS drchrono_sync_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Sync Information
    sync_type ENUM('create', 'update', 'retrieve') NOT NULL,
    sync_status ENUM('success', 'failed', 'pending') NOT NULL,

    -- API Details
    api_endpoint VARCHAR(255),
    request_data TEXT,
    response_data TEXT,
    error_message TEXT,
    http_status_code INT,

    -- DrChrono Patient ID
    drchrono_patient_id VARCHAR(100),

    -- Metadata
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_patient_id (patient_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_synced_at (synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOG TABLE
-- Track all data changes for compliance and security
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Record Information
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,

    -- Change Details
    old_values TEXT,
    new_values TEXT,

    -- User Information (for future admin panel)
    user_id INT,
    user_ip VARCHAR(45),

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert sample data (optional - remove for production)
-- =====================================================
-- Uncomment below to insert a test patient
/*
INSERT INTO patients (
    first_name, last_name, date_of_birth, age, gender,
    address, city, state, zip_code, cell_phone, email,
    emergency_contact_name, emergency_contact_phone, emergency_relationship,
    reason_for_visit
) VALUES (
    'John', 'Doe', '1990-05-15', 34, 'Male',
    '123 Main Street', 'Los Angeles', 'CA', '90001', '(555) 123-4567', 'john.doe@email.com',
    'Jane Doe', '(555) 987-6543', 'Spouse',
    'Annual checkup'
);
*/

-- =====================================================
-- Views for easy data retrieval
-- =====================================================

-- Complete patient view with all form data
CREATE OR REPLACE VIEW vw_patient_complete AS
SELECT
    p.*,
    mh.smokes,
    mh.drinks_alcohol,
    mh.medical_conditions,
    pc.signature_date as consent_date,
    fa.payment_method,
    ac.portal_access,
    fs.all_forms_completed,
    fs.completed_at as all_forms_completed_at
FROM patients p
LEFT JOIN medical_history mh ON p.patient_id = mh.patient_id
LEFT JOIN patient_consents pc ON p.patient_id = pc.patient_id
LEFT JOIN financial_agreements fa ON p.patient_id = fa.patient_id
LEFT JOIN additional_consents ac ON p.patient_id = ac.patient_id
LEFT JOIN form_submissions fs ON p.patient_id = fs.patient_id;

-- Patients pending DrChrono sync
CREATE OR REPLACE VIEW vw_patients_pending_sync AS
SELECT
    patient_id,
    CONCAT(first_name, ' ', last_name) as full_name,
    email,
    cell_phone,
    drchrono_sync_status,
    created_at
FROM patients
WHERE drchrono_sync_status = 'pending'
ORDER BY created_at ASC;

-- Recent form submissions
CREATE OR REPLACE VIEW vw_recent_submissions AS
SELECT
    p.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as full_name,
    p.email,
    fs.all_forms_completed,
    fs.completed_at,
    p.drchrono_sync_status
FROM patients p
JOIN form_submissions fs ON p.patient_id = fs.patient_id
WHERE fs.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY fs.completed_at DESC;

-- =====================================================
-- Grant permissions (adjust username as needed)
-- =====================================================
-- GRANT ALL PRIVILEGES ON urgent_care_db.* TO 'your_username'@'localhost';
-- FLUSH PRIVILEGES;

-- =====================================================
-- Database creation complete
-- =====================================================
SELECT 'Database schema created successfully!' as status;
