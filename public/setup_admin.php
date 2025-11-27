<?php
/**
 * Admin Database Setup Script
 *
 * This script creates all admin tables and the default admin user.
 * Run this ONCE, then DELETE this file for security.
 *
 * Access: http://your-domain/setup_admin.php
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Admin Database Setup</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; }
    .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    .step { background: #e7f3ff; padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🏥 Admin Dashboard Setup</h1>";

try {
    // Connect to database
    $conn = getDBConnection();
    echo "<div class='success'>✓ Successfully connected to database: <strong>uc_forms</strong></div>";

    // Read the clean SQL file (PHP compatible - no DELIMITER statements)
    $sqlFile = '../database/admin_schema_clean.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    echo "<div class='success'>✓ SQL file loaded successfully</div>";

    // Split the SQL file into individual queries by semicolons
    $queries = explode(';', $sql);

    // Filter out empty queries and comments
    $queries = array_filter(array_map('trim', $queries), function($query) {
        return !empty($query) &&
               strpos($query, '--') !== 0 &&
               strtoupper(substr($query, 0, 3)) !== 'SET';
    });

    echo "<div class='info'><strong>Found " . count($queries) . " SQL statements to execute</strong></div>";

    // Execute each query
    $successCount = 0;
    $errorCount = 0;

    echo "<h2>Execution Log:</h2>";
    echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f8f9fa;'>";

    foreach ($queries as $query) {
        if (empty($query)) continue;

        // Extract table/action info for display
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $query, $matches)) {
            $action = "Creating table: " . $matches[1];
        } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $query, $matches)) {
            $action = "Inserting data into: " . $matches[1];
        } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $query, $matches)) {
            $action = "Creating view: " . $matches[1];
        } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $query, $matches)) {
            $action = "Altering table: " . $matches[1];
        } else {
            $action = "Executing query";
        }

        try {
            if ($conn->query($query)) {
                echo "<div style='color: green; padding: 5px;'>✓ $action</div>";
                $successCount++;
            } else {
                echo "<div style='color: orange; padding: 5px;'>⚠ $action (may already exist)</div>";
            }
        } catch (mysqli_sql_exception $e) {
            // Check if it's a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div style='color: orange; padding: 5px;'>⚠ $action (already exists, skipping)</div>";
            } else {
                echo "<div style='color: red; padding: 5px;'>✗ $action - Error: " . $e->getMessage() . "</div>";
                $errorCount++;
            }
        }
    }

    echo "</div>";

    echo "<div class='success'>";
    echo "<h3>✓ Setup Complete!</h3>";
    echo "<p><strong>Successful operations:</strong> $successCount</p>";
    if ($errorCount > 0) {
        echo "<p style='color: orange;'><strong>Errors encountered:</strong> $errorCount (may be normal if tables already exist)</p>";
    }
    echo "</div>";

    // Verify tables were created
    echo "<h2>Verification:</h2>";
    $adminTables = [
        'admin_users',
        'admin_sessions',
        'clinic_settings',
        'patient_status',
        'admin_audit_log',
        'form_field_settings',
        'export_log'
    ];

    echo "<div class='info'>";
    echo "<h3>Checking admin tables:</h3>";
    $allTablesExist = true;
    foreach ($adminTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div style='color: green;'>✓ Table '$table' exists</div>";
        } else {
            echo "<div style='color: red;'>✗ Table '$table' NOT FOUND</div>";
            $allTablesExist = false;
        }
    }
    echo "</div>";

    // Check if default admin user exists
    echo "<h2>Default Admin User:</h2>";
    $adminCheck = $conn->query("SELECT username, email FROM admin_users WHERE username = 'admin'");

    if ($adminCheck && $adminCheck->num_rows > 0) {
        $admin = $adminCheck->fetch_assoc();
        echo "<div class='success'>";
        echo "<h3>✓ Default admin user exists!</h3>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($admin['username']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
        echo "<p><strong>Password:</strong> ChangeMe123!</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠ Default admin user not found. You may need to create one manually.</p>";
        echo "</div>";
    }

    if ($allTablesExist) {
        echo "<div class='success'>";
        echo "<h2>🎉 All Done!</h2>";
        echo "<div class='step'>";
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Go to <a href='/admin/login.php'><strong>/admin/login.php</strong></a></li>";
        echo "<li>Login with:<br>";
        echo "   - <strong>Username:</strong> admin<br>";
        echo "   - <strong>Password:</strong> ChangeMe123!</li>";
        echo "<li><strong style='color: red;'>IMPORTANT:</strong> Change the default password immediately!</li>";
        echo "<li><strong style='color: red;'>DELETE THIS FILE (setup_admin.php)</strong> for security!</li>";
        echo "</ol>";
        echo "</div>";
        echo "</div>";
    }

    $conn->close();

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>✗ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Database connection settings in config/database.php</li>";
    echo "<li>Database 'uc_forms' exists</li>";
    echo "<li>Database user has CREATE TABLE permissions</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
