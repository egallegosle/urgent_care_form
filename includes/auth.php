<?php
/**
 * Admin Authentication System
 * Handles login, logout, session management, and access control
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Session timeout in minutes (default 30)
define('SESSION_TIMEOUT', 30);

// Maximum failed login attempts before lockout
define('MAX_FAILED_ATTEMPTS', 5);

// Lockout duration in minutes
define('LOCKOUT_DURATION', 15);

/**
 * Check if admin is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Check if session is still valid (not expired)
 * @return bool
 */
function isSessionValid() {
    if (!isLoggedIn()) {
        return false;
    }

    // Check if session has expired
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        $timeout_seconds = SESSION_TIMEOUT * 60;

        if ($inactive_time > $timeout_seconds) {
            logoutAdmin();
            return false;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Require admin authentication
 * Redirects to login page if not authenticated
 */
function requireAuth() {
    if (!isSessionValid()) {
        $current_url = $_SERVER['REQUEST_URI'];
        header('Location: /admin/login.php?redirect=' . urlencode($current_url));
        exit();
    }
}

/**
 * Authenticate admin user
 * @param string $username
 * @param string $password
 * @return array Result array with success status and message
 */
function authenticateAdmin($username, $password) {
    $conn = getDBConnection();

    // Sanitize input
    $username = sanitizeInput($username);

    // Check if user exists and is active
    $sql = "SELECT admin_id, username, password_hash, email, first_name, last_name, role,
                   failed_login_attempts, locked_until
            FROM admin_users
            WHERE username = ? AND is_active = TRUE";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        logFailedLogin($username, 'Invalid username');
        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    $admin = $result->fetch_assoc();

    // Check if account is locked
    if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
        $minutes_left = ceil((strtotime($admin['locked_until']) - time()) / 60);
        return [
            'success' => false,
            'message' => "Account is locked due to multiple failed login attempts. Try again in {$minutes_left} minutes."
        ];
    }

    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        handleFailedLogin($conn, $admin['admin_id'], $username);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    // Reset failed attempts on successful login
    resetFailedAttempts($conn, $admin['admin_id']);

    // Create session
    createAdminSession($admin);

    // Log successful login
    logAdminAction($admin['admin_id'], 'LOGIN', null, null, 'Successful login');

    // Update last login info
    updateLastLogin($conn, $admin['admin_id']);

    return [
        'success' => true,
        'message' => 'Login successful',
        'admin' => [
            'id' => $admin['admin_id'],
            'username' => $admin['username'],
            'name' => $admin['first_name'] . ' ' . $admin['last_name'],
            'role' => $admin['role']
        ]
    ];
}

/**
 * Create admin session
 * @param array $admin Admin user data
 */
function createAdminSession($admin) {
    // Regenerate session ID for security
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['session_created'] = time();

    // Create session record in database
    $conn = getDBConnection();
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $expires_at = date('Y-m-d H:i:s', time() + (SESSION_TIMEOUT * 60));

    $sql = "INSERT INTO admin_sessions (session_id, admin_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                last_activity = CURRENT_TIMESTAMP,
                expires_at = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissss", $session_id, $admin['admin_id'], $ip_address, $user_agent, $expires_at, $expires_at);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * Handle failed login attempt
 * @param mysqli $conn Database connection
 * @param int $admin_id Admin user ID
 * @param string $username Username
 */
function handleFailedLogin($conn, $admin_id, $username) {
    // Increment failed attempts
    $sql = "UPDATE admin_users
            SET failed_login_attempts = failed_login_attempts + 1
            WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    // Get updated failed attempts count
    $sql = "SELECT failed_login_attempts FROM admin_users WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    // Lock account if max attempts reached
    if ($admin['failed_login_attempts'] >= MAX_FAILED_ATTEMPTS) {
        $locked_until = date('Y-m-d H:i:s', time() + (LOCKOUT_DURATION * 60));
        $sql = "UPDATE admin_users SET locked_until = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $locked_until, $admin_id);
        $stmt->execute();
    }

    // Log failed login
    logFailedLogin($username, 'Invalid password');
}

/**
 * Reset failed login attempts
 * @param mysqli $conn Database connection
 * @param int $admin_id Admin user ID
 */
function resetFailedAttempts($conn, $admin_id) {
    $sql = "UPDATE admin_users
            SET failed_login_attempts = 0, locked_until = NULL
            WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
}

/**
 * Update last login information
 * @param mysqli $conn Database connection
 * @param int $admin_id Admin user ID
 */
function updateLastLogin($conn, $admin_id) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $sql = "UPDATE admin_users
            SET last_login = NOW(), last_login_ip = ?
            WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $ip_address, $admin_id);
    $stmt->execute();
}

/**
 * Log failed login attempt
 * @param string $username Username
 * @param string $reason Reason for failure
 */
function logFailedLogin($username, $reason) {
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $sql = "INSERT INTO admin_audit_log
            (admin_username, action_type, description, ip_address, user_agent)
            VALUES (?, 'FAILED_LOGIN', ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $reason, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * Log admin action for audit trail
 * @param int $admin_id Admin user ID
 * @param string $action_type Action type (VIEW, CREATE, UPDATE, DELETE, EXPORT)
 * @param string $table_name Table name
 * @param int $record_id Record ID
 * @param string $description Action description
 * @param int $patient_id Patient ID if PHI was accessed
 * @param array $old_values Old values (for updates)
 * @param array $new_values New values (for updates)
 */
function logAdminAction($admin_id, $action_type, $table_name = null, $record_id = null,
                       $description = '', $patient_id = null, $old_values = null, $new_values = null) {
    $conn = getDBConnection();

    $username = $_SESSION['admin_username'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $old_json = $old_values ? json_encode($old_values) : null;
    $new_json = $new_values ? json_encode($new_values) : null;

    $phi_accessed = ($patient_id !== null) ? 1 : 0;

    $sql = "INSERT INTO admin_audit_log
            (admin_id, admin_username, action_type, table_name, record_id, description,
             old_values, new_values, ip_address, user_agent, patient_id, phi_accessed)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssississii", $admin_id, $username, $action_type, $table_name, $record_id,
                     $description, $old_json, $new_json, $ip_address, $user_agent, $patient_id, $phi_accessed);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * Logout admin user
 */
function logoutAdmin() {
    if (isset($_SESSION['admin_id'])) {
        // Log logout action
        logAdminAction($_SESSION['admin_id'], 'LOGOUT', null, null, 'User logged out');

        // Remove session from database
        $conn = getDBConnection();
        $session_id = session_id();
        $sql = "DELETE FROM admin_sessions WHERE session_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }

    // Destroy session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Check if admin has specific role
 * @param string $required_role Required role
 * @return bool
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }

    $admin_role = $_SESSION['admin_role'] ?? '';

    // Super admin has all permissions
    if ($admin_role === 'super_admin') {
        return true;
    }

    return $admin_role === $required_role;
}

/**
 * Get current admin ID
 * @return int|null
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin username
 * @return string|null
 */
function getCurrentAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

/**
 * Get current admin full name
 * @return string|null
 */
function getCurrentAdminName() {
    return $_SESSION['admin_name'] ?? null;
}

/**
 * Clean up expired sessions
 * Call this periodically (e.g., via cron job)
 */
function cleanupExpiredSessions() {
    $conn = getDBConnection();
    $sql = "DELETE FROM admin_sessions WHERE expires_at < NOW()";
    $conn->query($sql);
    $conn->close();
}

/**
 * Get active sessions count for an admin
 * @param int $admin_id Admin user ID
 * @return int
 */
function getActiveSessionsCount($admin_id) {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as count FROM admin_sessions
            WHERE admin_id = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $conn->close();
    return $row['count'];
}
?>
