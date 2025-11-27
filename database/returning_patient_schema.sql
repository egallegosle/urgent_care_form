-- =====================================================
-- RETURNING PATIENT FEATURE - Database Schema
-- =====================================================
-- This script creates tables for the returning patient
-- feature including visit tracking and audit logging
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- PATIENT VISITS TABLE
-- Track all patient visits and form submissions
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Visit Information
    visit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    visit_type ENUM('new', 'returning') DEFAULT 'returning',
    reason_for_visit TEXT,

    -- Status Tracking
    check_in_status ENUM('registered', 'in_progress', 'completed', 'cancelled') DEFAULT 'registered',

    -- Change Tracking
    -- JSON format: {"updated": ["address", "phone"], "unchanged": 18, "new_values": {...}}
    updated_fields TEXT,
    fields_changed_count INT DEFAULT 0,

    -- Form Completion
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
    INDEX idx_visit_date (visit_date),
    INDEX idx_visit_type (visit_type),
    INDEX idx_check_in_status (check_in_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT PATIENT LOOKUP TABLE
-- Security and compliance logging for all lookup attempts
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_patient_lookup (
    lookup_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Lookup Attempt Information
    lookup_email VARCHAR(255) NOT NULL,
    lookup_dob DATE NOT NULL,

    -- Result Information
    patient_found BOOLEAN NOT NULL,
    patient_id INT,
    patient_name VARCHAR(255),  -- Store name for audit trail

    -- Security Information
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    session_id VARCHAR(100),

    -- Rate Limiting Support
    attempt_count INT DEFAULT 1,

    -- Additional Security
    blocked BOOLEAN DEFAULT FALSE,
    block_reason VARCHAR(255),

    -- Metadata
    lookup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_ip_address (ip_address),
    INDEX idx_lookup_timestamp (lookup_timestamp),
    INDEX idx_patient_id (patient_id),
    INDEX idx_email (lookup_email),
    INDEX idx_blocked (blocked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PATIENT SESSIONS TABLE
-- Track active patient sessions for security
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_sessions (
    session_id VARCHAR(100) PRIMARY KEY,
    patient_id INT NOT NULL,

    -- Session Information
    session_data TEXT,
    visit_id INT,

    -- Security
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Expiration
    expires_at DATETIME NOT NULL,
    active BOOLEAN DEFAULT TRUE,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES patient_visits(visit_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_patient_id (patient_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RATE LIMITING TABLE
-- Track and enforce rate limits for lookups
-- =====================================================
CREATE TABLE IF NOT EXISTS rate_limit_tracking (
    limit_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identifier (IP address)
    identifier VARCHAR(45) NOT NULL,

    -- Rate Limit Tracking
    attempt_count INT DEFAULT 1,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,

    -- Blocking
    blocked_until DATETIME,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_identifier (identifier),
    INDEX idx_blocked_until (blocked_until),
    UNIQUE KEY unique_identifier (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VIEWS FOR RETURNING PATIENT FEATURE
-- =====================================================

-- Patient Visit History View
CREATE OR REPLACE VIEW vw_patient_visit_history AS
SELECT
    pv.visit_id,
    pv.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    p.email,
    p.cell_phone,
    pv.visit_date,
    pv.visit_type,
    pv.check_in_status,
    pv.fields_changed_count,
    pv.all_forms_completed,
    pv.completed_at,
    pv.reason_for_visit,
    pv.created_at
FROM patient_visits pv
JOIN patients p ON pv.patient_id = p.patient_id
ORDER BY pv.visit_date DESC;

-- Returning Patients Summary View
CREATE OR REPLACE VIEW vw_returning_patients_summary AS
SELECT
    p.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    p.email,
    p.cell_phone,
    COUNT(pv.visit_id) as total_visits,
    MAX(pv.visit_date) as last_visit_date,
    MIN(pv.visit_date) as first_visit_date,
    SUM(CASE WHEN pv.all_forms_completed = TRUE THEN 1 ELSE 0 END) as completed_visits
FROM patients p
LEFT JOIN patient_visits pv ON p.patient_id = pv.patient_id
GROUP BY p.patient_id
HAVING total_visits > 0
ORDER BY last_visit_date DESC;

-- Recent Lookup Attempts View (Security Monitoring)
CREATE OR REPLACE VIEW vw_recent_lookup_attempts AS
SELECT
    apl.lookup_id,
    apl.lookup_email,
    apl.patient_found,
    apl.ip_address,
    apl.blocked,
    apl.block_reason,
    apl.lookup_timestamp,
    COUNT(*) OVER (PARTITION BY apl.ip_address) as attempts_from_ip
FROM audit_patient_lookup apl
WHERE apl.lookup_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY apl.lookup_timestamp DESC;

-- Failed Lookup Attempts (Security Alert)
CREATE OR REPLACE VIEW vw_failed_lookup_attempts AS
SELECT
    ip_address,
    COUNT(*) as failed_attempts,
    MAX(lookup_timestamp) as last_attempt,
    GROUP_CONCAT(DISTINCT lookup_email SEPARATOR ', ') as attempted_emails
FROM audit_patient_lookup
WHERE patient_found = FALSE
    AND lookup_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address
HAVING failed_attempts >= 3
ORDER BY failed_attempts DESC;

-- =====================================================
-- ADD COLUMNS TO EXISTING TABLES
-- =====================================================

-- Add visit tracking to form_submissions table
ALTER TABLE form_submissions
ADD COLUMN IF NOT EXISTS visit_id INT AFTER patient_id,
ADD COLUMN IF NOT EXISTS is_update BOOLEAN DEFAULT FALSE AFTER visit_id,
ADD INDEX idx_visit_id (visit_id);

-- Add visit relationship (if patient_visits table exists)
-- Note: This will fail silently if foreign key already exists
SET @sql = 'ALTER TABLE form_submissions
    ADD CONSTRAINT fk_visit_id
    FOREIGN KEY (visit_id) REFERENCES patient_visits(visit_id)
    ON DELETE SET NULL';

SET @stmt = IF((SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'form_submissions'
    AND CONSTRAINT_NAME = 'fk_visit_id') = 0, @sql, 'SELECT 1');

PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- =====================================================

-- Procedure to clean up expired sessions
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS cleanup_expired_sessions()
BEGIN
    DELETE FROM patient_sessions
    WHERE expires_at < NOW() OR active = FALSE;

    SELECT ROW_COUNT() as sessions_cleaned;
END //
DELIMITER ;

-- Procedure to check and enforce rate limits
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS check_rate_limit(
    IN p_ip_address VARCHAR(45),
    IN p_max_attempts INT,
    IN p_time_window_minutes INT,
    OUT p_allowed BOOLEAN,
    OUT p_remaining_attempts INT
)
BEGIN
    DECLARE v_attempt_count INT DEFAULT 0;
    DECLARE v_first_attempt DATETIME;
    DECLARE v_blocked_until DATETIME;

    -- Check if IP is currently blocked
    SELECT blocked_until INTO v_blocked_until
    FROM rate_limit_tracking
    WHERE identifier = p_ip_address
    AND blocked_until > NOW()
    LIMIT 1;

    IF v_blocked_until IS NOT NULL THEN
        SET p_allowed = FALSE;
        SET p_remaining_attempts = 0;
    ELSE
        -- Get current attempt count within time window
        SELECT attempt_count, first_attempt_at INTO v_attempt_count, v_first_attempt
        FROM rate_limit_tracking
        WHERE identifier = p_ip_address
        AND first_attempt_at >= DATE_SUB(NOW(), INTERVAL p_time_window_minutes MINUTE);

        IF v_attempt_count IS NULL THEN
            -- First attempt in time window
            INSERT INTO rate_limit_tracking
                (identifier, attempt_count, first_attempt_at, last_attempt_at)
            VALUES
                (p_ip_address, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                attempt_count = 1,
                first_attempt_at = NOW(),
                last_attempt_at = NOW(),
                blocked_until = NULL;

            SET p_allowed = TRUE;
            SET p_remaining_attempts = p_max_attempts - 1;
        ELSE
            IF v_attempt_count >= p_max_attempts THEN
                -- Exceeded rate limit
                UPDATE rate_limit_tracking
                SET blocked_until = DATE_ADD(NOW(), INTERVAL p_time_window_minutes MINUTE)
                WHERE identifier = p_ip_address;

                SET p_allowed = FALSE;
                SET p_remaining_attempts = 0;
            ELSE
                -- Increment attempt count
                UPDATE rate_limit_tracking
                SET attempt_count = attempt_count + 1,
                    last_attempt_at = NOW()
                WHERE identifier = p_ip_address;

                SET p_allowed = TRUE;
                SET p_remaining_attempts = p_max_attempts - v_attempt_count - 1;
            END IF;
        END IF;
    END IF;
END //
DELIMITER ;

-- =====================================================
-- SAMPLE DATA FOR TESTING (Optional - Comment out for production)
-- =====================================================
/*
-- Insert a test patient with visit history
INSERT INTO patient_visits (patient_id, visit_date, visit_type, reason_for_visit, check_in_status, all_forms_completed, fields_changed_count)
SELECT
    patient_id,
    DATE_SUB(NOW(), INTERVAL 30 DAY),
    'new',
    'Initial visit - flu symptoms',
    'completed',
    TRUE,
    0
FROM patients
LIMIT 1;

INSERT INTO patient_visits (patient_id, visit_date, visit_type, reason_for_visit, check_in_status, all_forms_completed, fields_changed_count)
SELECT
    patient_id,
    DATE_SUB(NOW(), INTERVAL 7 DAY),
    'returning',
    'Follow-up visit',
    'completed',
    TRUE,
    2
FROM patients
LIMIT 1;
*/

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Composite indexes for common queries
ALTER TABLE patient_visits
ADD INDEX idx_patient_visit_date (patient_id, visit_date DESC);

ALTER TABLE audit_patient_lookup
ADD INDEX idx_email_timestamp (lookup_email, lookup_timestamp DESC);

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
SELECT 'Returning Patient Feature - Database Schema Created Successfully!' as status;

-- Display table information
SELECT
    TABLE_NAME as 'Table',
    TABLE_ROWS as 'Estimated Rows',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as 'Size (MB)'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('patient_visits', 'audit_patient_lookup', 'patient_sessions', 'rate_limit_tracking')
ORDER BY TABLE_NAME;
