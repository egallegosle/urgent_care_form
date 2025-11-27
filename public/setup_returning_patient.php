<?php
/**
 * Returning Patient Feature - Database Setup
 *
 * Run this ONCE to create the returning patient tables.
 * DELETE this file after running!
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Setup Returning Patient Feature</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; }
    .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🔄 Returning Patient Feature - Database Setup</h1>";

try {
    $conn = getDBConnection();
    echo "<div class='success'>✓ Connected to database: <strong>uc_forms</strong></div>";

    // Read SQL file
    $sqlFile = '../database/returning_patient_schema.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    echo "<div class='success'>✓ SQL file loaded</div>";

    // Split by semicolon
    $statements = explode(';', $sql);
    $successCount = 0;
    $errorCount = 0;

    echo "<h2>Installation Progress:</h2>";
    echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f8f9fa;'>";

    foreach ($statements as $statement) {
        $statement = trim($statement);

        // Skip empty or comment lines
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
            continue;
        }

        // Detect what we're doing
        $action = "Executing";
        if (stripos($statement, 'CREATE TABLE') !== false) {
            preg_match('/CREATE TABLE[^`]*`?(\w+)`?/i', $statement, $matches);
            $action = "Creating table: " . ($matches[1] ?? 'unknown');
        } elseif (stripos($statement, 'CREATE VIEW') !== false || stripos($statement, 'CREATE OR REPLACE VIEW') !== false) {
            preg_match('/VIEW[^`]*`?(\w+)`?/i', $statement, $matches);
            $action = "Creating view: " . ($matches[1] ?? 'unknown');
        } elseif (stripos($statement, 'CREATE PROCEDURE') !== false) {
            preg_match('/PROCEDURE[^`]*`?(\w+)`?/i', $statement, $matches);
            $action = "Creating procedure: " . ($matches[1] ?? 'unknown');
        } elseif (stripos($statement, 'INSERT INTO') !== false) {
            preg_match('/INSERT INTO[^`]*`?(\w+)`?/i', $statement, $matches);
            $action = "Inserting data into: " . ($matches[1] ?? 'unknown');
        }

        try {
            if ($conn->query($statement)) {
                echo "<div style='color: green; padding: 5px;'>✓ $action</div>";
                $successCount++;
            } else {
                echo "<div style='color: orange; padding: 5px;'>⚠ $action (may already exist)</div>";
            }
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div style='color: orange; padding: 5px;'>⚠ $action (already exists, skipping)</div>";
            } else {
                echo "<div style='color: red; padding: 5px;'>✗ $action - Error: " . $e->getMessage() . "</div>";
                $errorCount++;
            }
        }
    }

    echo "</div>";

    // Summary
    echo "<div class='success'>";
    echo "<h3>✓ Installation Complete!</h3>";
    echo "<p><strong>Successful operations:</strong> $successCount</p>";
    if ($errorCount > 0) {
        echo "<p style='color: orange;'><strong>Errors:</strong> $errorCount (may be normal if tables exist)</p>";
    }
    echo "</div>";

    // Verify tables
    echo "<h2>Verification:</h2>";
    $tables = ['patient_visits', 'audit_patient_lookup', 'patient_sessions', 'rate_limit_tracking'];

    echo "<div class='info'>";
    echo "<h3>Checking tables:</h3>";
    $allGood = true;

    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div style='color: green;'>✓ Table '$table' exists</div>";
        } else {
            echo "<div style='color: red;'>✗ Table '$table' NOT FOUND</div>";
            $allGood = false;
        }
    }
    echo "</div>";

    if ($allGood) {
        echo "<div class='success'>";
        echo "<h2>🎉 All Done!</h2>";
        echo "<div style='background: #e7f3ff; padding: 15px; margin: 15px 0; border-left: 4px solid #3498db;'>";
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Go to <a href='/index.php'><strong>Homepage</strong></a></li>";
        echo "<li>You'll see two options: <strong>New Patient</strong> and <strong>Returning Patient</strong></li>";
        echo "<li>Test the workflow:";
        echo "<ul>";
        echo "<li>Create a test patient (fill all forms)</li>";
        echo "<li>Go back to homepage</li>";
        echo "<li>Click 'Returning Patient'</li>";
        echo "<li>Enter the same email + DOB</li>";
        echo "<li>Watch the forms pre-fill! 🎉</li>";
        echo "</ul>";
        echo "</li>";
        echo "<li><strong style='color: red;'>DELETE THIS FILE (setup_returning_patient.php)</strong> for security!</li>";
        echo "</ol>";
        echo "</div>";
        echo "</div>";
    }

    $conn->close();

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>✗ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
