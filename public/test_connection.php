<?php
/**
 * Database Connection Test
 *
 * IMPORTANT: Delete this file after testing!
 */

require_once '../config/database.php';

echo "<h1>Database Connection Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }</style>";

try {
    // Test connection
    $conn = getDBConnection();
    echo "<p class='success'>✓ Successfully connected to database: <strong>uc_forms</strong></p>";

    // Check tables
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result->num_rows;

    echo "<p class='success'>✓ Found <strong>{$tableCount}</strong> tables in the database</p>";

    echo "<h2>Tables:</h2>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";

    // Check if tables are empty or have data
    echo "<h2>Table Status:</h2>";
    $tables = ['patients', 'medical_history', 'patient_consents', 'financial_agreements',
               'additional_consents', 'form_submissions', 'drchrono_sync_log', 'audit_log'];

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Table Name</th><th>Row Count</th></tr>";

    foreach ($tables as $table) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
        $countRow = $countResult->fetch_assoc();
        $count = $countRow['count'];
        $style = $count > 0 ? "background-color: #d4edda;" : "";
        echo "<tr style='$style'><td>$table</td><td>$count</td></tr>";
    }
    echo "</table>";

    $conn->close();

    echo "<div class='info'>";
    echo "<h2>✓ All Tests Passed!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='index.php'>http://localhost/</a></li>";
    echo "<li>Fill out the patient forms</li>";
    echo "<li>Submit and verify data is saved</li>";
    echo "<li><strong>DELETE THIS FILE (test_connection.php) after testing!</strong></li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Database credentials in config/database.php</li>";
    echo "<li>Database 'uc_forms' exists in GoDaddy/phpMyAdmin</li>";
    echo "<li>All tables were created from schema.sql</li>";
    echo "</ul>";
}
?>
