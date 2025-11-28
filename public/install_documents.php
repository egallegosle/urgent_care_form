<?php
/**
 * Document Upload Feature - Database Installer
 *
 * Run this file once to create the necessary database tables
 * Access via: https://your-domain.com/install_documents.php
 *
 * IMPORTANT: Delete this file after successful installation for security!
 */

// Prevent running multiple times
$ALLOW_REINSTALL = false; // Set to true if you want to run again

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Upload Feature - Database Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .step {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .step h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .step p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .success {
            background: #c6f6d5;
            border-left-color: #48bb78;
            color: #22543d;
        }
        .error {
            background: #fed7d7;
            border-left-color: #f56565;
            color: #742a2a;
        }
        .warning {
            background: #feebc8;
            border-left-color: #f59e0b;
            color: #7c2d12;
        }
        .info {
            background: #bee3f8;
            border-left-color: #4299e1;
            color: #2c5282;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        button:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        button:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
        }
        .result {
            margin-top: 30px;
        }
        .table-list {
            list-style: none;
            margin-top: 10px;
        }
        .table-list li {
            padding: 8px 12px;
            background: white;
            margin-bottom: 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-list li:before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            font-size: 18px;
        }
        code {
            background: #2d3748;
            color: #68d391;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Document Upload Feature Installer</h1>
        <p class="subtitle">Database schema installation for insurance card & photo ID uploads</p>

        <?php
        // Check if already installed
        require_once '../config/database.php';
        $conn = getDBConnection();

        $tables_exist = false;
        $existing_tables = [];

        $check_tables = ['patient_documents', 'document_access_log', 'document_settings'];
        foreach ($check_tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (mysqli_num_rows($result) > 0) {
                $tables_exist = true;
                $existing_tables[] = $table;
            }
        }

        if ($tables_exist && !$ALLOW_REINSTALL && !isset($_POST['install'])):
        ?>
            <div class="step warning">
                <h3>⚠️ Already Installed</h3>
                <p>The following tables already exist in your database:</p>
                <ul class="table-list">
                    <?php foreach ($existing_tables as $table): ?>
                    <li><?php echo htmlspecialchars($table); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p style="margin-top: 15px;">
                    If you want to reinstall, edit this file and set <code>$ALLOW_REINSTALL = true</code> at the top.
                </p>
                <p style="margin-top: 10px; font-weight: bold;">
                    ⚠️ Warning: Reinstalling will DROP existing tables and all data will be lost!
                </p>
            </div>

            <div class="step info">
                <h3>✅ Installation Complete</h3>
                <p>The document upload feature database schema is already installed.</p>
                <p style="margin-top: 10px;">
                    <strong>Important:</strong> Delete this file (<code>install_documents.php</code>) for security!
                </p>
            </div>

        <?php elseif (isset($_POST['install'])): ?>

            <div class="result">
                <div class="step info">
                    <h3><span class="loading"></span>Installing Database Schema...</h3>
                    <p>Please wait while the database tables are created...</p>
                </div>

                <?php
                $errors = [];
                $success_count = 0;

                // Start transaction
                mysqli_begin_transaction($conn);

                try {
                    // Drop existing tables if reinstalling
                    if ($ALLOW_REINSTALL) {
                        mysqli_query($conn, "DROP TABLE IF EXISTS document_access_log");
                        mysqli_query($conn, "DROP TABLE IF EXISTS patient_documents");
                        mysqli_query($conn, "DROP TABLE IF EXISTS document_settings");
                        mysqli_query($conn, "DROP VIEW IF EXISTS vw_patient_documents_summary");
                        mysqli_query($conn, "DROP VIEW IF EXISTS vw_documents_pending_verification");
                        mysqli_query($conn, "DROP VIEW IF EXISTS vw_document_access_stats");
                    }

                    // Create patient_documents table
                    $sql = "CREATE TABLE IF NOT EXISTS patient_documents (
                        document_id INT AUTO_INCREMENT PRIMARY KEY,
                        patient_id INT NOT NULL,
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
                        original_filename VARCHAR(255) NOT NULL,
                        stored_filename VARCHAR(255) NOT NULL UNIQUE,
                        file_path VARCHAR(500) NOT NULL,
                        file_size INT NOT NULL,
                        mime_type VARCHAR(100) NOT NULL,
                        file_extension VARCHAR(10) NOT NULL,
                        uploaded_by ENUM('patient', 'staff', 'admin') DEFAULT 'patient',
                        uploaded_by_user_id INT NULL,
                        ip_address VARCHAR(45),
                        user_agent VARCHAR(255),
                        status ENUM('pending', 'verified', 'rejected', 'archived') DEFAULT 'pending',
                        verified_by INT NULL,
                        verified_date DATETIME NULL,
                        rejection_reason TEXT NULL,
                        description TEXT NULL,
                        tags VARCHAR(500) NULL,
                        upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                        last_accessed DATETIME NULL,
                        access_count INT DEFAULT 0,
                        is_deleted BOOLEAN DEFAULT FALSE,
                        deleted_date DATETIME NULL,
                        deleted_by INT NULL,
                        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
                        INDEX idx_patient_id (patient_id),
                        INDEX idx_document_type (document_type),
                        INDEX idx_upload_date (upload_date),
                        INDEX idx_status (status),
                        INDEX idx_category (document_category),
                        INDEX idx_not_deleted (is_deleted),
                        INDEX idx_patient_status (patient_id, status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                    if (mysqli_query($conn, $sql)) {
                        $success_count++;
                        echo '<div class="step success"><h3>✓ Created table: patient_documents</h3></div>';
                    } else {
                        throw new Exception("Failed to create patient_documents: " . mysqli_error($conn));
                    }

                    // Create document_access_log table
                    $sql = "CREATE TABLE IF NOT EXISTS document_access_log (
                        log_id INT AUTO_INCREMENT PRIMARY KEY,
                        document_id INT NOT NULL,
                        patient_id INT NOT NULL,
                        action ENUM('upload', 'view', 'download', 'delete', 'verify', 'reject') NOT NULL,
                        accessed_by ENUM('patient', 'staff', 'admin', 'system') NOT NULL,
                        user_id INT NULL,
                        ip_address VARCHAR(45),
                        user_agent VARCHAR(255),
                        access_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                        notes TEXT NULL,
                        FOREIGN KEY (document_id) REFERENCES patient_documents(document_id) ON DELETE CASCADE,
                        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
                        INDEX idx_document_id (document_id),
                        INDEX idx_patient_id (patient_id),
                        INDEX idx_access_date (access_date),
                        INDEX idx_action (action),
                        INDEX idx_patient_action (patient_id, action)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                    if (mysqli_query($conn, $sql)) {
                        $success_count++;
                        echo '<div class="step success"><h3>✓ Created table: document_access_log</h3></div>';
                    } else {
                        throw new Exception("Failed to create document_access_log: " . mysqli_error($conn));
                    }

                    // Create document_settings table
                    $sql = "CREATE TABLE IF NOT EXISTS document_settings (
                        setting_id INT AUTO_INCREMENT PRIMARY KEY,
                        setting_key VARCHAR(100) NOT NULL UNIQUE,
                        setting_value TEXT NOT NULL,
                        setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
                        description TEXT,
                        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        updated_by INT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                    if (mysqli_query($conn, $sql)) {
                        $success_count++;
                        echo '<div class="step success"><h3>✓ Created table: document_settings</h3></div>';
                    } else {
                        throw new Exception("Failed to create document_settings: " . mysqli_error($conn));
                    }

                    // Insert default settings
                    $settings = [
                        ['max_file_size', '5242880', 'integer', 'Maximum file size in bytes (5MB)'],
                        ['allowed_extensions', '["jpg","jpeg","png","pdf"]', 'json', 'Allowed file extensions'],
                        ['allowed_mime_types', '["image/jpeg","image/png","application/pdf"]', 'json', 'Allowed MIME types'],
                        ['storage_path', 'uploads/patient_documents/', 'string', 'Base path for document storage'],
                        ['require_verification', 'true', 'boolean', 'Require admin verification of uploaded documents'],
                        ['auto_delete_rejected', 'false', 'boolean', 'Automatically delete rejected documents after 30 days'],
                        ['max_documents_per_patient', '20', 'integer', 'Maximum documents per patient']
                    ];

                    $stmt = mysqli_prepare($conn, "INSERT INTO document_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");

                    foreach ($settings as $setting) {
                        mysqli_stmt_bind_param($stmt, "ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
                        mysqli_stmt_execute($stmt);
                    }
                    echo '<div class="step success"><h3>✓ Inserted default settings (7 rows)</h3></div>';

                    // Create views
                    $sql = "CREATE OR REPLACE VIEW vw_patient_documents_summary AS
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
                        GROUP BY p.patient_id, p.first_name, p.last_name, p.email";

                    if (mysqli_query($conn, $sql)) {
                        echo '<div class="step success"><h3>✓ Created view: vw_patient_documents_summary</h3></div>';
                    }

                    $sql = "CREATE OR REPLACE VIEW vw_documents_pending_verification AS
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
                        ORDER BY pd.upload_date ASC";

                    if (mysqli_query($conn, $sql)) {
                        echo '<div class="step success"><h3>✓ Created view: vw_documents_pending_verification</h3></div>';
                    }

                    $sql = "CREATE OR REPLACE VIEW vw_document_access_stats AS
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
                        GROUP BY pd.document_id, pd.patient_id, pd.document_type, pd.original_filename";

                    if (mysqli_query($conn, $sql)) {
                        echo '<div class="step success"><h3>✓ Created view: vw_document_access_stats</h3></div>';
                    }

                    // Commit transaction
                    mysqli_commit($conn);

                    echo '<div class="step success">
                            <h3>🎉 Installation Complete!</h3>
                            <p>All database tables have been created successfully.</p>
                            <ul class="table-list">
                                <li>patient_documents (stores document metadata)</li>
                                <li>document_access_log (HIPAA audit trail)</li>
                                <li>document_settings (configuration)</li>
                                <li>vw_patient_documents_summary (reporting view)</li>
                                <li>vw_documents_pending_verification (workflow view)</li>
                                <li>vw_document_access_stats (analytics view)</li>
                            </ul>
                          </div>';

                    echo '<div class="step warning">
                            <h3>🔒 Important Security Step!</h3>
                            <p><strong>DELETE THIS FILE NOW!</strong></p>
                            <p>For security, you must delete <code>install_documents.php</code> from your server immediately.</p>
                            <p>You can delete it via FTP or cPanel File Manager.</p>
                          </div>';

                    echo '<div class="step info">
                            <h3>✅ Next Steps</h3>
                            <p>1. Delete this file (<code>install_documents.php</code>)</p>
                            <p>2. Test patient upload at: <code>/forms/1_patient_registration.php</code></p>
                            <p>3. Test admin dashboard at: <code>/admin/patients/view.php</code></p>
                            <p>4. Verify upload directory exists: <code>uploads/patient_documents/</code></p>
                          </div>';

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    echo '<div class="step error">
                            <h3>❌ Installation Failed</h3>
                            <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                            <p>Please check your database configuration and try again.</p>
                          </div>';
                }

                closeDBConnection($conn);
                ?>
            </div>

        <?php else: ?>

            <div class="step info">
                <h3>📋 What This Installer Does</h3>
                <p>This installer will create the following database components:</p>
                <ul class="table-list">
                    <li><strong>patient_documents</strong> - Stores uploaded insurance cards and photo IDs</li>
                    <li><strong>document_access_log</strong> - HIPAA-compliant audit trail</li>
                    <li><strong>document_settings</strong> - Configuration (file size, types, etc.)</li>
                    <li><strong>3 database views</strong> - For reporting and analytics</li>
                </ul>
            </div>

            <div class="step warning">
                <h3>⚠️ Before You Install</h3>
                <p>✓ Make sure you have backed up your database</p>
                <p>✓ Ensure the <code>uploads/patient_documents/</code> directory exists</p>
                <p>✓ Verify you have database CREATE TABLE permissions</p>
                <p style="margin-top: 15px; font-weight: bold;">
                    <?php if ($ALLOW_REINSTALL): ?>
                    ⚠️ REINSTALL MODE: This will DROP existing tables and all data!
                    <?php else: ?>
                    This installation is safe - it will not delete any existing data.
                    <?php endif; ?>
                </p>
            </div>

            <form method="POST" onsubmit="document.querySelector('button').disabled = true; document.querySelector('button').innerHTML = '<span class=\'loading\'></span>Installing...'; return true;">
                <button type="submit" name="install">
                    🚀 Install Document Upload Feature
                </button>
            </form>

            <div class="step" style="margin-top: 20px; opacity: 0.8;">
                <h3>ℹ️ Installation Info</h3>
                <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
                <p><strong>Host:</strong> <?php echo DB_HOST; ?></p>
                <p><strong>Feature:</strong> Document Upload (Insurance Cards & Photo IDs)</p>
                <p><strong>Version:</strong> 1.0.0</p>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
