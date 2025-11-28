-- =====================================================
-- Document Upload Feature - Database Schema
-- =====================================================
-- Creates tables for patient document management
-- (insurance cards, photo IDs, medical records)
-- =====================================================

-- Set charset and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- PATIENT DOCUMENTS TABLE
-- Stores metadata for uploaded documents
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Patient Reference
    patient_id INT NOT NULL,

    -- Document Information
    document_type ENUM(
        'insurance_card_front',
        'insurance_card_back',
        'photo_id_front',
        'photo_id_back',
        'medical_records',
        'prescription',
        'referral',
        'other'
    ) NOT NULL,

    document_category ENUM('insurance', 'identification', 'medical', 'other') NOT NULL DEFAULT 'other',

    -- File Details
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,  -- Size in bytes
    mime_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,

    -- Security & Compliance
    uploaded_by ENUM('patient', 'staff', 'admin') DEFAULT 'patient',
    uploaded_by_user_id INT NULL,  -- Reference to admin_users if uploaded by staff
    ip_address VARCHAR(45),  -- IPv4 or IPv6
    user_agent VARCHAR(255),

    -- Status & Verification
    status ENUM('pending', 'verified', 'rejected', 'archived') DEFAULT 'pending',
    verified_by INT NULL,  -- admin_users.user_id
    verified_date DATETIME NULL,
    rejection_reason TEXT NULL,

    -- Metadata
    description TEXT NULL,
    tags VARCHAR(500) NULL,  -- JSON array of tags

    -- Timestamps
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_accessed DATETIME NULL,
    access_count INT DEFAULT 0,

    -- Soft Delete
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_date DATETIME NULL,
    deleted_by INT NULL,

    -- Foreign Keys
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES admin_users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES admin_users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (deleted_by) REFERENCES admin_users(user_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_patient_id (patient_id),
    INDEX idx_document_type (document_type),
    INDEX idx_upload_date (upload_date),
    INDEX idx_status (status),
    INDEX idx_category (document_category),
    INDEX idx_not_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DOCUMENT ACCESS LOG
-- HIPAA compliance - track all document access
-- =====================================================
CREATE TABLE IF NOT EXISTS document_access_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Document Reference
    document_id INT NOT NULL,
    patient_id INT NOT NULL,

    -- Access Details
    action ENUM('upload', 'view', 'download', 'delete', 'verify', 'reject') NOT NULL,
    accessed_by ENUM('patient', 'staff', 'admin', 'system') NOT NULL,
    user_id INT NULL,  -- admin_users.user_id if staff/admin

    -- Context
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    access_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Additional Information
    notes TEXT NULL,

    -- Foreign Keys
    FOREIGN KEY (document_id) REFERENCES patient_documents(document_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admin_users(user_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_document_id (document_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_access_date (access_date),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DOCUMENT SETTINGS
-- Configuration for upload rules and limits
-- =====================================================
CREATE TABLE IF NOT EXISTS document_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Setting Details
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',

    -- Metadata
    description TEXT,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,

    -- Foreign Key
    FOREIGN KEY (updated_by) REFERENCES admin_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT SETTINGS
-- =====================================================
INSERT INTO document_settings (setting_key, setting_value, setting_type, description) VALUES
('max_file_size', '5242880', 'integer', 'Maximum file size in bytes (5MB)'),
('allowed_extensions', '["jpg","jpeg","png","pdf"]', 'json', 'Allowed file extensions'),
('allowed_mime_types', '["image/jpeg","image/png","application/pdf"]', 'json', 'Allowed MIME types'),
('storage_path', 'uploads/patient_documents/', 'string', 'Base path for document storage'),
('require_verification', 'true', 'boolean', 'Require admin verification of uploaded documents'),
('auto_delete_rejected', 'false', 'boolean', 'Automatically delete rejected documents after 30 days'),
('max_documents_per_patient', '20', 'integer', 'Maximum documents per patient')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Active documents per patient
CREATE OR REPLACE VIEW vw_patient_documents_summary AS
SELECT
    p.patient_id,
    p.first_name,
    p.last_name,
    p.email,
    COUNT(pd.document_id) AS total_documents,
    SUM(CASE WHEN pd.document_category = 'insurance' THEN 1 ELSE 0 END) AS insurance_docs,
    SUM(CASE WHEN pd.document_category = 'identification' THEN 1 ELSE 0 END) AS id_docs,
    SUM(CASE WHEN pd.document_category = 'medical' THEN 1 ELSE 0 END) AS medical_docs,
    SUM(CASE WHEN pd.status = 'pending' THEN 1 ELSE 0 END) AS pending_verification,
    MAX(pd.upload_date) AS last_upload_date
FROM patients p
LEFT JOIN patient_documents pd ON p.patient_id = pd.patient_id AND pd.is_deleted = FALSE
GROUP BY p.patient_id, p.first_name, p.last_name, p.email;

-- Documents pending verification
CREATE OR REPLACE VIEW vw_documents_pending_verification AS
SELECT
    pd.document_id,
    pd.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
    pd.document_type,
    pd.document_category,
    pd.original_filename,
    pd.file_size,
    pd.upload_date,
    pd.uploaded_by,
    DATEDIFF(NOW(), pd.upload_date) AS days_pending
FROM patient_documents pd
JOIN patients p ON pd.patient_id = p.patient_id
WHERE pd.status = 'pending'
  AND pd.is_deleted = FALSE
ORDER BY pd.upload_date ASC;

-- Document access statistics
CREATE OR REPLACE VIEW vw_document_access_stats AS
SELECT
    pd.document_id,
    pd.patient_id,
    pd.document_type,
    pd.original_filename,
    COUNT(dal.log_id) AS total_accesses,
    SUM(CASE WHEN dal.action = 'view' THEN 1 ELSE 0 END) AS views,
    SUM(CASE WHEN dal.action = 'download' THEN 1 ELSE 0 END) AS downloads,
    MAX(dal.access_date) AS last_access_date,
    MIN(dal.access_date) AS first_access_date
FROM patient_documents pd
LEFT JOIN document_access_log dal ON pd.document_id = dal.document_id
WHERE pd.is_deleted = FALSE
GROUP BY pd.document_id, pd.patient_id, pd.document_type, pd.original_filename;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Cleanup old rejected documents (run monthly)
DELIMITER //
CREATE PROCEDURE cleanup_rejected_documents()
BEGIN
    -- Soft delete rejected documents older than 30 days
    UPDATE patient_documents
    SET is_deleted = TRUE,
        deleted_date = NOW(),
        deleted_by = NULL
    WHERE status = 'rejected'
      AND is_deleted = FALSE
      AND DATEDIFF(NOW(), upload_date) > 30;

    SELECT ROW_COUNT() AS documents_archived;
END//
DELIMITER ;

-- Get patient documents with access count
DELIMITER //
CREATE PROCEDURE get_patient_documents(IN p_patient_id INT)
BEGIN
    SELECT
        pd.document_id,
        pd.document_type,
        pd.document_category,
        pd.original_filename,
        pd.file_size,
        pd.status,
        pd.upload_date,
        pd.uploaded_by,
        pd.access_count,
        pd.last_accessed,
        CONCAT(v.first_name, ' ', v.last_name) AS verified_by_name,
        pd.verified_date
    FROM patient_documents pd
    LEFT JOIN admin_users v ON pd.verified_by = v.user_id
    WHERE pd.patient_id = p_patient_id
      AND pd.is_deleted = FALSE
    ORDER BY pd.upload_date DESC;
END//
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Update access count and timestamp when document is accessed
DELIMITER //
CREATE TRIGGER after_document_access
AFTER INSERT ON document_access_log
FOR EACH ROW
BEGIN
    IF NEW.action IN ('view', 'download') THEN
        UPDATE patient_documents
        SET access_count = access_count + 1,
            last_accessed = NEW.access_date
        WHERE document_id = NEW.document_id;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
ALTER TABLE patient_documents ADD INDEX idx_patient_status (patient_id, status);
ALTER TABLE document_access_log ADD INDEX idx_patient_action (patient_id, action);

-- =====================================================
-- GRANT PERMISSIONS (adjust user as needed)
-- =====================================================
-- GRANT SELECT, INSERT, UPDATE ON patient_documents TO 'uc_form_user'@'localhost';
-- GRANT SELECT, INSERT ON document_access_log TO 'uc_form_user'@'localhost';
-- GRANT SELECT ON document_settings TO 'uc_form_user'@'localhost';

-- =====================================================
-- SCHEMA COMPLETE
-- =====================================================
