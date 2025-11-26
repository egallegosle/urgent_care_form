# Critical Security Fixes - Implementation Guide

This guide provides step-by-step instructions to implement the most critical security fixes identified in the security audit.

---

## Fix 1: Secure Database Credentials

### Step 1: Create Environment Configuration

Create `/home/egallegosle/projects/urgent_care_form/.env`:
```
# Database Configuration
DB_HOST=68.178.244.46
DB_PORT=3306
DB_USER=egallegosle
DB_PASS=jiujitsu4
DB_NAME=uc_forms
DB_CHARSET=utf8mb4

# Environment
ENVIRONMENT=development

# Encryption Key (generate with: openssl rand -base64 32)
ENCRYPTION_KEY=your_32_byte_base64_key_here

# Test Access Key
TEST_PASSWORD=your_secure_test_password_here
```

### Step 2: Update .gitignore

Add to `.gitignore`:
```
.env
config/database.php
```

### Step 3: Create Environment Loader

Create `/home/egallegosle/projects/urgent_care_form/config/env_loader.php`:
```php
<?php
/**
 * Environment Variable Loader
 * Loads configuration from .env file
 */

function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load environment variables
try {
    loadEnv();
} catch (Exception $e) {
    error_log("Failed to load .env file: " . $e->getMessage());
    die("Configuration error. Please contact system administrator.");
}
?>
```

### Step 4: Update database.php

Replace `/home/egallegosle/projects/urgent_care_form/config/database.php`:
```php
<?php
/**
 * Database Configuration File
 * Database: uc_forms
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database Connection Settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME') ?: 'uc_forms');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Error Reporting (based on environment)
define('DB_DEBUG', getenv('ENVIRONMENT') !== 'production');

/**
 * Get database connection
 * @return mysqli Database connection object
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        if (DB_DEBUG) {
            die("Connection failed: " . $conn->connect_error);
        } else {
            die("A system error occurred. Please try again later. [Error Code: DB001]");
        }
    }

    // Set charset
    if (!$conn->set_charset(DB_CHARSET)) {
        error_log("Error setting charset: " . $conn->error);
    }

    return $conn;
}

/**
 * Execute prepared statement safely
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (e.g., "ssi" for string, string, int)
 * @param array $params Parameters to bind
 * @return mysqli_stmt|false Prepared statement or false on error
 */
function executePreparedStatement($conn, $sql, $types, $params) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    return $stmt;
}

/**
 * Sanitize input data
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if (!is_string($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Close database connection
 * @param mysqli $conn Database connection
 */
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
```

---

## Fix 2: Secure Session Configuration

### Create `/home/egallegosle/projects/urgent_care_form/includes/session_config.php`:

```php
<?php
/**
 * Secure Session Configuration for HIPAA Compliance
 */

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // Requires HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 0); // Browser session only
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

// Session timeout (15 minutes of inactivity)
define('SESSION_TIMEOUT', 900);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();

    // Initialize session on first use
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Session expired
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['session_expired'] = true;
        header("Location: /timeout.php");
        exit();
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();

    // Regenerate session ID periodically (every 30 minutes)
    if (isset($_SESSION['created']) &&
        (time() - $_SESSION['created'] > 1800)) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Destroy session securely
 */
function destroySession() {
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}
?>
```

### Create `/home/egallegosle/projects/urgent_care_form/public/timeout.php`:

```php
<?php
require_once '../includes/session_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Timeout - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Session Expired</h1>
            <p>Your session has expired due to inactivity</p>
        </div>

        <div class="alert alert-info">
            <p>For your security, your session has been automatically closed after 15 minutes of inactivity.</p>
            <p>If you were in the middle of completing forms, please start again from the beginning.</p>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" class="btn btn-primary">Return to Home</a>
        </div>
    </div>
</body>
</html>
```

### Update all processor files:

Replace `session_start();` with:
```php
require_once '../../includes/session_config.php';
```

At the top of each processor file (after session_start), add CSRF validation:
```php
// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    error_log("CSRF token validation failed from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    die("Security validation failed. Please try again. [Error Code: SEC001]");
}
```

### Update all form files:

After opening `<form>` tag, add:
```php
<?php require_once '../../includes/session_config.php'; ?>
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
```

---

## Fix 3: HTTPS Enforcement

### Create `/home/egallegosle/projects/urgent_care_form/includes/security_headers.php`:

```php
<?php
/**
 * Security Headers and HTTPS Enforcement
 */

// Force HTTPS in production
if (getenv('ENVIRONMENT') === 'production') {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Content-Type with charset
header('Content-Type: text/html; charset=UTF-8');
?>
```

### Add to all PHP files (forms and processors):

```php
<?php
require_once '../../includes/security_headers.php';
?>
```

---

## Fix 4: Input Validation

### Create `/home/egallegosle/projects/urgent_care_form/includes/validation.php`:

```php
<?php
/**
 * Input Validation Functions
 */

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Remove formatting characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10; // US phone numbers
}

function validateSSN($ssn) {
    // Format: XXX-XX-XXXX
    return preg_match('/^\d{3}-\d{2}-\d{4}$/', $ssn);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateZipCode($zip) {
    return preg_match('/^\d{5}(-\d{4})?$/', $zip);
}

function validateMaxLength($value, $maxLength) {
    return strlen($value) <= $maxLength;
}

function validateRequired($value) {
    return !empty(trim($value));
}

function sanitizeArray($array) {
    if (!is_array($array)) {
        return [];
    }
    return array_map(function($item) {
        return is_string($item) ? sanitizeInput($item) : '';
    }, $array);
}

function validateAge($age) {
    return is_numeric($age) && $age >= 0 && $age <= 150;
}

function validateGender($gender) {
    return in_array($gender, ['Male', 'Female', 'Other']);
}
?>
```

### Update save_patient_registration.php - Add validation after sanitization:

```php
require_once '../../includes/validation.php';

// ... existing sanitization code ...

// Validate required fields
$errors = [];

if (!validateRequired($firstName)) $errors[] = "First name is required";
if (!validateRequired($lastName)) $errors[] = "Last name is required";
if (!validateDate($dateOfBirth)) $errors[] = "Invalid date of birth";
if (!validateGender($gender)) $errors[] = "Invalid gender selection";
if (!validateEmail($email)) $errors[] = "Invalid email address";
if (!validatePhone($cellPhone)) $errors[] = "Invalid cell phone number";
if (!validateZipCode($zipCode)) $errors[] = "Invalid ZIP code";

if (!empty($ssn) && !validateSSN($ssn)) {
    $errors[] = "Invalid SSN format. Use XXX-XX-XXXX";
}

if (!empty($homePhone) && !validatePhone($homePhone)) {
    $errors[] = "Invalid home phone number";
}

// Check maximum lengths
if (!validateMaxLength($firstName, 100)) $errors[] = "First name too long";
if (!validateMaxLength($lastName, 100)) $errors[] = "Last name too long";
if (!validateMaxLength($email, 255)) $errors[] = "Email too long";

if (!empty($errors)) {
    error_log("Validation errors: " . implode(', ', $errors));
    die("Validation errors:<br>" . implode('<br>', $errors));
}
```

---

## Fix 5: Audit Logging

### Create `/home/egallegosle/projects/urgent_care_form/includes/audit.php`:

```php
<?php
/**
 * Audit Logging for HIPAA Compliance
 */

function logAudit($conn, $tableName, $recordId, $action, $oldValues = null, $newValues = null) {
    $sql = "INSERT INTO audit_log (
        table_name, record_id, action, old_values, new_values, user_ip
    ) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Audit log failed to prepare: " . $conn->error);
        return false;
    }

    $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
    $newValuesJson = $newValues ? json_encode($newValues) : null;

    $stmt->bind_param(
        "sissss",
        $tableName, $recordId, $action, $oldValuesJson, $newValuesJson, $userIp
    );

    $result = $stmt->execute();

    if (!$result) {
        error_log("Audit log failed to execute: " . $stmt->error);
    }

    $stmt->close();
    return $result;
}

function logPHIAccess($conn, $patientId, $action, $details = null) {
    logAudit($conn, 'patients', $patientId, $action, null, $details);
}
?>
```

### Update all processor files - Add after successful INSERT:

```php
require_once '../../includes/audit.php';

// After successful insert
if ($stmt->execute()) {
    $patientId = $stmt->insert_id;

    // Log the action
    logAudit($conn, 'patients', $patientId, 'INSERT', null, [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // ... rest of code
}
```

---

## Fix 6: Rate Limiting

### Create `/home/egallegosle/projects/urgent_care_form/includes/rate_limit.php`:

```php
<?php
/**
 * Rate Limiting to Prevent Abuse
 */

function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }

    $data = &$_SESSION['rate_limit'][$key];

    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $data['attempts'] = 0;
        $data['first_attempt'] = time();
    }

    $data['attempts']++;

    if ($data['attempts'] > $maxAttempts) {
        $remainingTime = $timeWindow - (time() - $data['first_attempt']);
        error_log("Rate limit exceeded for $action from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(429);
        die("Too many requests. Please try again in " . ceil($remainingTime / 60) . " minutes. [Error Code: RL001]");
    }
}
?>
```

### Add to each processor file:

```php
require_once '../../includes/rate_limit.php';

// Add after CSRF validation
checkRateLimit('patient_registration', 3, 300); // 3 attempts per 5 minutes
```

---

## Testing the Fixes

### 1. Test Environment Variables
```php
// Create test_env.php in project root
<?php
require_once 'config/database.php';
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_DEBUG: " . (DB_DEBUG ? 'true' : 'false') . "\n";
?>
```

### 2. Test CSRF Protection
- Try submitting a form without CSRF token (should fail)
- Try submitting with invalid CSRF token (should fail)
- Submit with valid token (should succeed)

### 3. Test Session Timeout
- Set SESSION_TIMEOUT to 60 seconds for testing
- Wait 60 seconds after loading a page
- Try to submit form (should redirect to timeout.php)

### 4. Test Rate Limiting
- Submit same form 4 times rapidly
- 5th attempt should be blocked with 429 error

### 5. Test Audit Logging
```sql
SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 10;
```
Should show INSERT operations for all form submissions

---

## Deployment Checklist

Before deploying to production:

1. [ ] .env file created with secure values
2. [ ] ENVIRONMENT set to 'production' in .env
3. [ ] Strong database password generated and set
4. [ ] ENCRYPTION_KEY generated (32 bytes)
5. [ ] All processor files updated with new includes
6. [ ] All form files updated with CSRF tokens
7. [ ] Session timeout tested
8. [ ] CSRF protection tested
9. [ ] Rate limiting tested
10. [ ] Audit logging verified
11. [ ] HTTPS certificate installed on server
12. [ ] Test files removed or protected
13. [ ] Error logging configured
14. [ ] Database backup verified

---

## Next Steps

After implementing these critical fixes:

1. Review SECURITY_AUDIT_REPORT.md for HIGH and MEDIUM priority issues
2. Implement SSN encryption
3. Add duplicate patient detection
4. Implement data retention policy
5. Complete HIPAA compliance checklist
6. Conduct security penetration testing
7. Train staff on security procedures

---

## Support

If you encounter issues during implementation:

1. Check PHP error logs
2. Check MySQL error logs
3. Verify .env file permissions (should be 600)
4. Verify all paths are correct
5. Test each component individually

For HIPAA compliance questions, consult with a healthcare compliance attorney.
