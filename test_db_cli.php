<?php
/**
 * CLI Database Connection and Table Verification Test
 */

require_once __DIR__ . '/config/database.php';

echo "=================================================\n";
echo "DATABASE CONNECTION TEST\n";
echo "=================================================\n\n";

try {
    // Test connection
    echo "1. Testing database connection...\n";
    $conn = getDBConnection();
    echo "   ✓ Successfully connected to database: uc_forms\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Database: " . DB_NAME . "\n\n";

    // Check tables
    echo "2. Checking database tables...\n";
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result->num_rows;

    echo "   ✓ Found {$tableCount} tables in the database\n\n";

    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    echo "   Tables found:\n";
    foreach ($tables as $table) {
        echo "   - {$table}\n";
    }
    echo "\n";

    // Expected tables
    $expectedTables = [
        'patients',
        'medical_history',
        'patient_consents',
        'financial_agreements',
        'additional_consents',
        'form_submissions',
        'drchrono_sync_log',
        'audit_log'
    ];

    echo "3. Verifying expected tables...\n";
    $missingTables = [];
    foreach ($expectedTables as $expectedTable) {
        if (in_array($expectedTable, $tables)) {
            echo "   ✓ {$expectedTable}\n";
        } else {
            echo "   ✗ {$expectedTable} (MISSING)\n";
            $missingTables[] = $expectedTable;
        }
    }
    echo "\n";

    if (!empty($missingTables)) {
        echo "WARNING: The following tables are missing:\n";
        foreach ($missingTables as $table) {
            echo "   - {$table}\n";
        }
        echo "\n";
        echo "ACTION REQUIRED: Run the schema.sql file to create missing tables.\n\n";
    }

    // Check if tables have data
    echo "4. Checking table row counts...\n";
    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            $countResult = $conn->query("SELECT COUNT(*) as count FROM {$table}");
            if ($countResult) {
                $countRow = $countResult->fetch_assoc();
                $count = $countRow['count'];
                echo "   {$table}: {$count} rows\n";
            }
        }
    }
    echo "\n";

    // Test views
    echo "5. Checking database views...\n";
    $viewsResult = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    if ($viewsResult) {
        $viewCount = $viewsResult->num_rows;
        echo "   Found {$viewCount} views\n";
        while ($viewRow = $viewsResult->fetch_array()) {
            echo "   - {$viewRow[0]}\n";
        }
    } else {
        echo "   No views found or error checking views\n";
    }
    echo "\n";

    $conn->close();

    echo "=================================================\n";
    echo "✓ ALL TESTS COMPLETED\n";
    echo "=================================================\n\n";

    if (empty($missingTables)) {
        echo "STATUS: Database is properly configured\n";
        echo "NEXT: Test the form submission workflow\n\n";
    } else {
        echo "STATUS: Database setup incomplete\n";
        echo "ACTION: Create missing tables using schema.sql\n\n";
    }

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "  - Database credentials in config/database.php\n";
    echo "  - Database 'uc_forms' exists on GoDaddy server\n";
    echo "  - Network connectivity to GoDaddy server\n";
    echo "  - Firewall rules allow connection to port 3306\n\n";
    exit(1);
}
