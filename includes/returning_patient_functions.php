<?php
/**
 * Returning Patient Feature - Helper Functions
 * Handles patient lookup, rate limiting, data loading, and change tracking
 */

/**
 * Check if IP address is rate limited
 *
 * @param mysqli $conn Database connection
 * @param string $ip_address IP address to check
 * @param int $max_attempts Maximum attempts allowed (default: 5)
 * @param int $time_window Time window in minutes (default: 15)
 * @return array ['allowed' => bool, 'remaining' => int, 'blocked_until' => string|null]
 */
function checkRateLimit($conn, $ip_address, $max_attempts = 5, $time_window = 15) {
    // Call stored procedure if it exists, otherwise use manual check
    $stmt = $conn->prepare("CALL check_rate_limit(?, ?, ?, @allowed, @remaining)");

    if ($stmt) {
        $stmt->bind_param("sii", $ip_address, $max_attempts, $time_window);
        $stmt->execute();
        $stmt->close();

        // Get output parameters
        $result = $conn->query("SELECT @allowed as allowed, @remaining as remaining");
        $data = $result->fetch_assoc();

        return [
            'allowed' => (bool)$data['allowed'],
            'remaining' => (int)$data['remaining'],
            'blocked_until' => null
        ];
    }

    // Fallback: Manual rate limit check
    $time_cutoff = date('Y-m-d H:i:s', strtotime("-{$time_window} minutes"));

    // Check if currently blocked
    $stmt = $conn->prepare("
        SELECT blocked_until
        FROM rate_limit_tracking
        WHERE identifier = ? AND blocked_until > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'allowed' => false,
            'remaining' => 0,
            'blocked_until' => $row['blocked_until']
        ];
    }

    // Get attempt count in time window
    $stmt = $conn->prepare("
        SELECT attempt_count, first_attempt_at
        FROM rate_limit_tracking
        WHERE identifier = ? AND first_attempt_at >= ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $ip_address, $time_cutoff);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // First attempt
        $stmt = $conn->prepare("
            INSERT INTO rate_limit_tracking
            (identifier, attempt_count, first_attempt_at, last_attempt_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                attempt_count = 1,
                first_attempt_at = NOW(),
                last_attempt_at = NOW(),
                blocked_until = NULL
        ");
        $stmt->bind_param("s", $ip_address);
        $stmt->execute();

        return [
            'allowed' => true,
            'remaining' => $max_attempts - 1,
            'blocked_until' => null
        ];
    }

    $row = $result->fetch_assoc();
    $attempt_count = (int)$row['attempt_count'];

    if ($attempt_count >= $max_attempts) {
        // Block the IP
        $blocked_until = date('Y-m-d H:i:s', strtotime("+{$time_window} minutes"));
        $stmt = $conn->prepare("
            UPDATE rate_limit_tracking
            SET blocked_until = ?
            WHERE identifier = ?
        ");
        $stmt->bind_param("ss", $blocked_until, $ip_address);
        $stmt->execute();

        return [
            'allowed' => false,
            'remaining' => 0,
            'blocked_until' => $blocked_until
        ];
    }

    // Increment attempt count
    $stmt = $conn->prepare("
        UPDATE rate_limit_tracking
        SET attempt_count = attempt_count + 1, last_attempt_at = NOW()
        WHERE identifier = ?
    ");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();

    return [
        'allowed' => true,
        'remaining' => $max_attempts - $attempt_count - 1,
        'blocked_until' => null
    ];
}

/**
 * Look up patient by email and date of birth
 *
 * @param mysqli $conn Database connection
 * @param string $email Patient email
 * @param string $dob Patient date of birth (YYYY-MM-DD)
 * @return array|null Patient data or null if not found
 */
function lookupPatient($conn, $email, $dob) {
    $stmt = $conn->prepare("
        SELECT
            patient_id,
            first_name,
            middle_name,
            last_name,
            date_of_birth,
            email,
            cell_phone,
            created_at,
            updated_at
        FROM patients
        WHERE LOWER(email) = LOWER(?) AND date_of_birth = ?
        LIMIT 1
    ");

    $stmt->bind_param("ss", $email, $dob);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

/**
 * Get patient's last visit information
 *
 * @param mysqli $conn Database connection
 * @param int $patient_id Patient ID
 * @return array|null Last visit data or null
 */
function getLastVisit($conn, $patient_id) {
    $stmt = $conn->prepare("
        SELECT
            visit_id,
            visit_date,
            visit_type,
            reason_for_visit,
            all_forms_completed
        FROM patient_visits
        WHERE patient_id = ?
        ORDER BY visit_date DESC
        LIMIT 1
    ");

    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

/**
 * Log patient lookup attempt (success or failure)
 *
 * @param mysqli $conn Database connection
 * @param string $email Lookup email
 * @param string $dob Lookup date of birth
 * @param bool $found Whether patient was found
 * @param int|null $patient_id Patient ID if found
 * @param string|null $patient_name Patient name if found
 * @param string $ip_address IP address
 * @param string $user_agent User agent
 * @param string $session_id Session ID
 * @return bool Success
 */
function logLookupAttempt($conn, $email, $dob, $found, $patient_id = null, $patient_name = null, $ip_address = '', $user_agent = '', $session_id = '') {
    $stmt = $conn->prepare("
        INSERT INTO audit_patient_lookup
        (lookup_email, lookup_dob, patient_found, patient_id, patient_name, ip_address, user_agent, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssiissss", $email, $dob, $found, $patient_id, $patient_name, $ip_address, $user_agent, $session_id);

    return $stmt->execute();
}

/**
 * Load complete patient data for all forms
 *
 * @param mysqli $conn Database connection
 * @param int $patient_id Patient ID
 * @return array Patient data from all tables
 */
function loadPatientData($conn, $patient_id) {
    $data = [];

    // Load patient registration data
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['patient'] = $result->fetch_assoc();

    // Load medical history
    $stmt = $conn->prepare("SELECT * FROM medical_history WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['medical_history'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    // Load patient consents
    $stmt = $conn->prepare("SELECT * FROM patient_consents WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['consent'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    // Load financial agreement
    $stmt = $conn->prepare("SELECT * FROM financial_agreements WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['financial'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    // Load additional consents
    $stmt = $conn->prepare("SELECT * FROM additional_consents WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['additional_consents'] = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    return $data;
}

/**
 * Create new visit record for returning patient
 *
 * @param mysqli $conn Database connection
 * @param int $patient_id Patient ID
 * @param string $visit_type Visit type (new/returning)
 * @param string $ip_address IP address
 * @param string $user_agent User agent
 * @param string $session_id Session ID
 * @return int|false Visit ID or false on failure
 */
function createVisitRecord($conn, $patient_id, $visit_type = 'returning', $ip_address = '', $user_agent = '', $session_id = '') {
    $stmt = $conn->prepare("
        INSERT INTO patient_visits
        (patient_id, visit_type, ip_address, user_agent, session_id)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("issss", $patient_id, $visit_type, $ip_address, $user_agent, $session_id);

    if ($stmt->execute()) {
        return $stmt->insert_id;
    }

    return false;
}

/**
 * Track changes between old and new patient data
 *
 * @param array $old_data Old patient data
 * @param array $new_data New patient data
 * @return array ['changed_fields' => array, 'count' => int, 'json' => string]
 */
function trackDataChanges($old_data, $new_data) {
    $changed_fields = [];
    $unchanged_count = 0;

    foreach ($new_data as $key => $new_value) {
        if (!isset($old_data[$key])) {
            continue; // Skip new fields
        }

        $old_value = $old_data[$key];

        // Normalize for comparison
        $old_normalized = trim((string)$old_value);
        $new_normalized = trim((string)$new_value);

        if ($old_normalized !== $new_normalized) {
            $changed_fields[$key] = [
                'old' => $old_value,
                'new' => $new_value
            ];
        } else {
            $unchanged_count++;
        }
    }

    $result = [
        'changed_fields' => array_keys($changed_fields),
        'changes_detail' => $changed_fields,
        'count' => count($changed_fields),
        'unchanged_count' => $unchanged_count
    ];

    return [
        'changed_fields' => $changed_fields,
        'count' => count($changed_fields),
        'unchanged_count' => $unchanged_count,
        'json' => json_encode($result)
    ];
}

/**
 * Update visit record with change tracking
 *
 * @param mysqli $conn Database connection
 * @param int $visit_id Visit ID
 * @param array $changes Change tracking data
 * @param string $reason_for_visit Reason for visit
 * @return bool Success
 */
function updateVisitChanges($conn, $visit_id, $changes, $reason_for_visit = '') {
    $stmt = $conn->prepare("
        UPDATE patient_visits
        SET updated_fields = ?,
            fields_changed_count = ?,
            reason_for_visit = ?
        WHERE visit_id = ?
    ");

    $changes_json = $changes['json'];
    $count = $changes['count'];

    $stmt->bind_param("sisi", $changes_json, $count, $reason_for_visit, $visit_id);

    return $stmt->execute();
}

/**
 * Mark visit as completed
 *
 * @param mysqli $conn Database connection
 * @param int $visit_id Visit ID
 * @return bool Success
 */
function completeVisit($conn, $visit_id) {
    $stmt = $conn->prepare("
        UPDATE patient_visits
        SET all_forms_completed = TRUE,
            completed_at = NOW(),
            check_in_status = 'completed'
        WHERE visit_id = ?
    ");

    $stmt->bind_param("i", $visit_id);

    return $stmt->execute();
}

/**
 * Get user's IP address
 *
 * @return string IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Get first IP if multiple are present
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Get user agent
 *
 * @return string User agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Mask SSN for display (show only last 4 digits)
 *
 * @param string $ssn Full SSN
 * @return string Masked SSN (XXX-XX-1234)
 */
function maskSSN($ssn) {
    if (empty($ssn)) {
        return '';
    }

    // Remove any non-digit characters
    $digits = preg_replace('/\D/', '', $ssn);

    if (strlen($digits) < 4) {
        return 'XXX-XX-XXXX';
    }

    $last_four = substr($digits, -4);
    return "XXX-XX-{$last_four}";
}

/**
 * Format date for display
 *
 * @param string $date Date string
 * @param string $format Display format
 * @return string Formatted date
 */
function formatDateDisplay($date, $format = 'F j, Y') {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date($format, $timestamp);
}

/**
 * Calculate time since last visit
 *
 * @param string $visit_date Last visit date
 * @return string Human-readable time difference
 */
function timeSinceVisit($visit_date) {
    $timestamp = strtotime($visit_date);
    if ($timestamp === false) {
        return 'Unknown';
    }

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Clean up old session data
 *
 * @param mysqli $conn Database connection
 * @return int Number of sessions cleaned
 */
function cleanupExpiredSessions($conn) {
    $stmt = $conn->prepare("DELETE FROM patient_sessions WHERE expires_at < NOW() OR active = FALSE");
    $stmt->execute();
    return $stmt->affected_rows;
}

/**
 * Validate email format
 *
 * @param string $email Email address
 * @return bool Valid or not
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date format (YYYY-MM-DD)
 *
 * @param string $date Date string
 * @return bool Valid or not
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
?>
