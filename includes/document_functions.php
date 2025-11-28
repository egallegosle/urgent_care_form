<?php
/**
 * Document Upload Functions
 * Handles file uploads, validation, storage, and retrieval
 * HIPAA compliant with audit logging
 */

// Prevent direct access
if (!defined('DOCUMENT_FUNCTIONS_INCLUDED')) {
    define('DOCUMENT_FUNCTIONS_INCLUDED', true);
}

/**
 * Get document upload settings from database
 */
function getDocumentSettings($conn) {
    $settings = [];
    $sql = "SELECT setting_key, setting_value, setting_type FROM document_settings";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $value = $row['setting_value'];

            // Convert value based on type
            switch ($row['setting_type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = ($value === 'true' || $value === '1');
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }

            $settings[$row['setting_key']] = $value;
        }
    }

    // Set defaults if not in database
    $defaults = [
        'max_file_size' => 5242880, // 5MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
        'storage_path' => 'uploads/patient_documents/',
        'require_verification' => true,
        'max_documents_per_patient' => 20
    ];

    return array_merge($defaults, $settings);
}

/**
 * Validate uploaded file
 * Returns array with 'valid' (bool) and 'error' (string) keys
 */
function validateUploadedFile($file, $settings) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['valid' => false, 'error' => $errorMessages[$file['error']] ?? 'Unknown upload error'];
    }

    // Check file size
    if ($file['size'] > $settings['max_file_size']) {
        $maxSizeMB = round($settings['max_file_size'] / 1024 / 1024, 1);
        return ['valid' => false, 'error' => "File size exceeds maximum of {$maxSizeMB}MB"];
    }

    // Check file size is not zero
    if ($file['size'] === 0) {
        return ['valid' => false, 'error' => 'File is empty'];
    }

    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Check extension
    if (!in_array($extension, $settings['allowed_extensions'])) {
        $allowedList = implode(', ', $settings['allowed_extensions']);
        return ['valid' => false, 'error' => "File type not allowed. Allowed types: {$allowedList}"];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $settings['allowed_mime_types'])) {
        return ['valid' => false, 'error' => 'Invalid file type detected'];
    }

    // Verify it's actually an uploaded file
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Security validation failed'];
    }

    return ['valid' => true, 'error' => null, 'extension' => $extension, 'mime_type' => $mimeType];
}

/**
 * Generate unique filename for storage
 */
function generateUniqueFilename($patient_id, $document_type, $extension) {
    // Format: patientID_documentType_timestamp_random.ext
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $sanitizedType = preg_replace('/[^a-z0-9_]/', '_', strtolower($document_type));

    return "{$patient_id}_{$sanitizedType}_{$timestamp}_{$random}.{$extension}";
}

/**
 * Create patient document directory if it doesn't exist
 */
function ensureDocumentDirectory($patient_id, $base_path) {
    $patient_dir = $base_path . $patient_id . '/';

    if (!file_exists($patient_dir)) {
        if (!mkdir($patient_dir, 0750, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }

        // Create .htaccess to prevent direct access
        $htaccess = $patient_dir . '.htaccess';
        $htaccess_content = "Order Deny,Allow\nDeny from all\n";
        file_put_contents($htaccess, $htaccess_content);
    }

    return ['success' => true, 'path' => $patient_dir];
}

/**
 * Upload and save document
 */
function uploadDocument($conn, $file, $patient_id, $document_type, $uploaded_by = 'patient', $user_id = null, $description = null) {
    $settings = getDocumentSettings($conn);

    // Validate file
    $validation = validateUploadedFile($file, $settings);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }

    // Check patient document count
    $count_sql = "SELECT COUNT(*) as doc_count FROM patient_documents
                  WHERE patient_id = ? AND is_deleted = FALSE";
    $stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row['doc_count'] >= $settings['max_documents_per_patient']) {
        return ['success' => false, 'error' => 'Maximum documents per patient exceeded'];
    }

    // Determine document category from type
    $category_map = [
        'insurance_card_front' => 'insurance',
        'insurance_card_back' => 'insurance',
        'photo_id_front' => 'identification',
        'photo_id_back' => 'identification',
        'medical_records' => 'medical',
        'prescription' => 'medical',
        'referral' => 'medical',
        'other' => 'other'
    ];
    $document_category = $category_map[$document_type] ?? 'other';

    // Create directory
    $dir_result = ensureDocumentDirectory($patient_id, $settings['storage_path']);
    if (!$dir_result['success']) {
        return ['success' => false, 'error' => $dir_result['error']];
    }

    // Generate unique filename
    $stored_filename = generateUniqueFilename($patient_id, $document_type, $validation['extension']);
    $file_path = $dir_result['path'] . $stored_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }

    // Set appropriate permissions
    chmod($file_path, 0640);

    // Get client info
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Insert into database
    $sql = "INSERT INTO patient_documents (
                patient_id, document_type, document_category,
                original_filename, stored_filename, file_path,
                file_size, mime_type, file_extension,
                uploaded_by, uploaded_by_user_id,
                ip_address, user_agent, description, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    $status = $settings['require_verification'] ? 'pending' : 'verified';

    mysqli_stmt_bind_param(
        $stmt,
        "isssssississss",
        $patient_id,
        $document_type,
        $document_category,
        $file['name'],
        $stored_filename,
        $file_path,
        $file['size'],
        $validation['mime_type'],
        $validation['extension'],
        $uploaded_by,
        $user_id,
        $ip_address,
        $user_agent,
        $description,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        // Delete the uploaded file if database insert fails
        unlink($file_path);
        return ['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)];
    }

    $document_id = mysqli_insert_id($conn);

    // Log the upload
    logDocumentAccess($conn, $document_id, $patient_id, 'upload', $uploaded_by, $user_id);

    return [
        'success' => true,
        'document_id' => $document_id,
        'filename' => $stored_filename,
        'status' => $status
    ];
}

/**
 * Log document access for HIPAA compliance
 */
function logDocumentAccess($conn, $document_id, $patient_id, $action, $accessed_by = 'patient', $user_id = null, $notes = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "INSERT INTO document_access_log (
                document_id, patient_id, action, accessed_by,
                user_id, ip_address, user_agent, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "iissssss",
        $document_id,
        $patient_id,
        $action,
        $accessed_by,
        $user_id,
        $ip_address,
        $user_agent,
        $notes
    );

    return mysqli_stmt_execute($stmt);
}

/**
 * Get patient documents
 */
function getPatientDocuments($conn, $patient_id, $include_deleted = false) {
    $sql = "SELECT
                pd.*,
                CONCAT(v.first_name, ' ', v.last_name) AS verified_by_name
            FROM patient_documents pd
            LEFT JOIN admin_users v ON pd.verified_by = v.user_id
            WHERE pd.patient_id = ?";

    if (!$include_deleted) {
        $sql .= " AND pd.is_deleted = FALSE";
    }

    $sql .= " ORDER BY pd.upload_date DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $documents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $documents[] = $row;
    }

    return $documents;
}

/**
 * Get document by ID
 */
function getDocument($conn, $document_id) {
    $sql = "SELECT * FROM patient_documents WHERE document_id = ? AND is_deleted = FALSE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $document_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result);
}

/**
 * Serve document file (with access control)
 */
function serveDocument($conn, $document_id, $user_id = null, $accessed_by = 'staff') {
    $document = getDocument($conn, $document_id);

    if (!$document) {
        http_response_code(404);
        die('Document not found');
    }

    // Log the access
    logDocumentAccess($conn, $document_id, $document['patient_id'], 'view', $accessed_by, $user_id);

    // Check if file exists
    if (!file_exists($document['file_path'])) {
        http_response_code(404);
        die('File not found');
    }

    // Set headers
    header('Content-Type: ' . $document['mime_type']);
    header('Content-Length: ' . filesize($document['file_path']));
    header('Content-Disposition: inline; filename="' . $document['original_filename'] . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output file
    readfile($document['file_path']);
    exit;
}

/**
 * Download document file
 */
function downloadDocument($conn, $document_id, $user_id = null, $accessed_by = 'staff') {
    $document = getDocument($conn, $document_id);

    if (!$document) {
        http_response_code(404);
        die('Document not found');
    }

    // Log the download
    logDocumentAccess($conn, $document_id, $document['patient_id'], 'download', $accessed_by, $user_id);

    // Check if file exists
    if (!file_exists($document['file_path'])) {
        http_response_code(404);
        die('File not found');
    }

    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($document['file_path']));
    header('Content-Disposition: attachment; filename="' . $document['original_filename'] . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output file
    readfile($document['file_path']);
    exit;
}

/**
 * Delete document (soft delete)
 */
function deleteDocument($conn, $document_id, $user_id = null, $accessed_by = 'staff') {
    $sql = "UPDATE patient_documents
            SET is_deleted = TRUE, deleted_date = NOW(), deleted_by = ?
            WHERE document_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $document_id);

    if (mysqli_stmt_execute($stmt)) {
        // Log the deletion
        $document = getDocument($conn, $document_id);
        if ($document) {
            logDocumentAccess($conn, $document_id, $document['patient_id'], 'delete', $accessed_by, $user_id);
        }
        return ['success' => true];
    }

    return ['success' => false, 'error' => mysqli_error($conn)];
}

/**
 * Verify document (admin action)
 */
function verifyDocument($conn, $document_id, $user_id) {
    $sql = "UPDATE patient_documents
            SET status = 'verified', verified_by = ?, verified_date = NOW()
            WHERE document_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $document_id);

    if (mysqli_stmt_execute($stmt)) {
        $document = getDocument($conn, $document_id);
        if ($document) {
            logDocumentAccess($conn, $document_id, $document['patient_id'], 'verify', 'admin', $user_id);
        }
        return ['success' => true];
    }

    return ['success' => false, 'error' => mysqli_error($conn)];
}

/**
 * Reject document (admin action)
 */
function rejectDocument($conn, $document_id, $user_id, $reason) {
    $sql = "UPDATE patient_documents
            SET status = 'rejected', rejection_reason = ?, verified_by = ?, verified_date = NOW()
            WHERE document_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $reason, $user_id, $document_id);

    if (mysqli_stmt_execute($stmt)) {
        $document = getDocument($conn, $document_id);
        if ($document) {
            logDocumentAccess($conn, $document_id, $document['patient_id'], 'reject', 'admin', $user_id, $reason);
        }
        return ['success' => true];
    }

    return ['success' => false, 'error' => mysqli_error($conn)];
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Get document type display name
 */
function getDocumentTypeLabel($document_type) {
    $labels = [
        'insurance_card_front' => 'Insurance Card (Front)',
        'insurance_card_back' => 'Insurance Card (Back)',
        'photo_id_front' => 'Photo ID (Front)',
        'photo_id_back' => 'Photo ID (Back)',
        'medical_records' => 'Medical Records',
        'prescription' => 'Prescription',
        'referral' => 'Referral',
        'other' => 'Other Document'
    ];

    return $labels[$document_type] ?? $document_type;
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending Verification</span>',
        'verified' => '<span class="badge badge-success">Verified</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'archived' => '<span class="badge badge-secondary">Archived</span>'
    ];

    return $badges[$status] ?? $status;
}
